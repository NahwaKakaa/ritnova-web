<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LearningSession;
use App\Models\SessionProgress;
use App\Models\User;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use App\Support\TempPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    /** Filter fase studi untuk daftar peserta. */
    private const STAGES = [
        'consent' => 'Belum memberi persetujuan',
        'onboarding' => 'Belum menyelesaikan persiapan',
        'T0' => 'T0 belum lengkap',
        'T1' => 'T1 belum lengkap',
        'T2' => 'T2 belum lengkap',
        'withdrawn' => 'Mengundurkan diri',
    ];

    public function index(Request $request): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $metrics = new StudyMetrics($cohort);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:30'],
            'group' => ['nullable', Rule::in(['intervention', 'comparison', 'none'])],
            'stage' => ['nullable', Rule::in(array_keys(self::STAGES))],
        ]);

        $released = $metrics->releasedSchedules()->count();
        $participants = $metrics->participants()
            ->withCount(['sessionProgress as completed_sessions' => fn ($q) => $q->whereNotNull('completed_at')])
            ->withCount(['sleepDiaries as valid_diaries' => fn ($q) => $q->where('status', 'complete')])
            ->withExists(['consents as has_consent' => fn ($q) => $q->whereNull('withdrawn_at')])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('username', 'like', '%'.$term.'%'))
            ->when($filters['group'] ?? null, fn ($q, $g) => $g === 'none' ? $q->whereNull('study_group') : $q->where('study_group', $g))
            ->orderBy('username')
            ->get();

        $phaseDone = $metrics->phaseDoneMap($participants->pluck('id'));
        $stage = $filters['stage'] ?? null;
        if ($stage) {
            $participants = $participants->filter(fn (User $u) => match ($stage) {
                'consent' => $u->status === 'active' && ! $u->has_consent,
                'onboarding' => $u->status === 'active' && $u->has_consent && ! $u->onboarded_at,
                'withdrawn' => $u->status === 'withdrawn',
                default => $u->status === 'active' && $u->has_consent && ! ($phaseDone[$u->id][$stage] ?? false),
            })->values();
        }

        return view('admin.participants.index', [
            'cohort' => $cohort,
            'participants' => $participants,
            'phaseDone' => $phaseDone,
            'released' => $released,
            'filters' => $filters,
            'stages' => self::STAGES,
            'instrumentCounts' => $metrics->instrumentsByPhase()->map->count(),
            'credentials' => session('credentials'),
            'canManage' => $request->user()->role === User::ROLE_ADMIN,
        ]);
    }

    /**
     * Data penelitian milik peserta. Peserta yang sudah punya salah satunya tidak boleh dihapus,
     * cukup dicatat mengundurkan diri, supaya data yang terkumpul tetap utuh.
     *
     * @return array<string, int>
     */
    private function researchData(User $participant): array
    {
        return array_filter([
            'catatan tidur' => $participant->sleepDiaries()->count(),
            'progres sesi' => $participant->sessionProgress()->count(),
            'pengukuran' => $participant->assessmentResponses()->count(),
            'hasil SJL' => $participant->sjlResults()->count(),
            'target' => $participant->goals()->count(),
            'refleksi' => $participant->weeklyReflections()->count(),
        ]);
    }

    public function show(Request $request, User $participant): View
    {
        abort_unless($participant->isParticipant(), 404);
        $participant->load(['cohort', 'consents.version']);
        $metrics = $participant->cohort ? new StudyMetrics($participant->cohort) : null;
        $schedules = $metrics?->schedules()->keyBy('learning_session_id') ?? collect();
        $progress = SessionProgress::where('user_id', $participant->id)->get()->keyBy('learning_session_id');

        $logs = AuditLog::with('user:id,username,role')
            ->where(fn ($q) => $q->where('user_id', $participant->id)
                ->orWhere(fn ($q) => $q->where('auditable_type', $participant->getMorphClass())->where('auditable_id', $participant->id)))
            ->latest('created_at')->limit(30)->get();

        return view('admin.participants.show', [
            'p' => $participant,
            'sessions' => LearningSession::orderBy('number')->get(),
            'schedules' => $schedules,
            'progress' => $progress,
            'diaries' => $participant->sleepDiaries()->withCount('revisions')->orderByDesc('diary_date')->limit(21)->get(),
            'sjl' => $participant->sjlResults()->orderBy('period_end')->get(),
            'assessments' => $participant->assessmentResponses()->with('instrument:id,code,name')->orderBy('phase')->get(),
            'goals' => $participant->goals()->orderBy('week')->get(),
            'reflections' => $participant->weeklyReflections()->orderBy('week')->get(),
            'logs' => $logs,
            'credentials' => session('credentials'),
            'canManage' => $request->user()->role === User::ROLE_ADMIN,
            'researchData' => $this->researchData($participant),
        ]);
    }

    /**
     * Impor kode peserta: satu baris per peserta, format "kode,kelompok[,kata_sandi]".
     * Kode yang sudah ada dilewati. Kata sandi kosong dibuatkan otomatis dan ditampilkan SEKALI.
     */
    public function import(Request $request): RedirectResponse
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:512'],
            'rows' => ['nullable', 'string', 'max:20000'],
        ]);
        $text = $request->file('file')?->get() ?? (string) $request->input('rows');
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $groupAliases = ['intervention' => 'intervention', 'intervensi' => 'intervention', 'comparison' => 'comparison', 'pembanding' => 'comparison', '' => null];

        $created = [];
        $skipped = [];
        $errors = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($text));

        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = array_map('trim', str_getcsv($line, str_contains($line, ';') && ! str_contains($line, ',') ? ';' : ','));
            $code = Str::upper($cols[0] ?? '');
            if ($i === 0 && in_array(Str::lower($code), ['participant_code', 'kode', 'kode_peserta'], true)) {
                continue; // baris judul
            }
            $groupKey = Str::lower($cols[1] ?? '');
            if (! preg_match('/^[A-Z0-9-]{3,20}$/', $code)) {
                $errors[] = 'Baris '.($i + 1).': kode "'.Str::limit($cols[0] ?? '', 20).'" tidak valid (huruf, angka, tanda hubung, 3–20 karakter).';

                continue;
            }
            if (! array_key_exists($groupKey, $groupAliases)) {
                $errors[] = 'Baris '.($i + 1).': kelompok "'.$cols[1].'" tidak dikenal (pakai intervensi atau pembanding).';

                continue;
            }
            if (User::where('username', $code)->exists()) {
                $skipped[] = $code;

                continue;
            }
            $password = ($cols[2] ?? '') !== '' ? $cols[2] : TempPassword::make();
            $created[] = ['code' => $code, 'group' => $groupAliases[$groupKey], 'password' => $password];
        }

        if ($created === [] && $errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($created, $cohort) {
            foreach ($created as $row) {
                User::create([
                    'username' => $row['code'], 'role' => User::ROLE_PARTICIPANT, 'cohort_id' => $cohort->id,
                    'study_group' => $row['group'], 'status' => 'active', 'password' => $row['password'],
                ]);
            }
        });
        AuditLog::record('participants_imported', $cohort, null, [
            'created' => array_column($created, 'code'), 'skipped' => $skipped, 'errors' => count($errors),
        ]);

        $message = count($created).' peserta ditambahkan'.($skipped ? ', '.count($skipped).' kode sudah ada dan dilewati' : '').'.';

        return redirect()->route('admin.participants.index')
            ->with('status', $message)
            ->with('credentials', array_map(fn ($r) => [$r['code'], $r['password']], $created))
            ->withErrors($errors);
    }

    public function updateGroup(Request $request, User $participant): RedirectResponse
    {
        abort_unless($participant->isParticipant(), 404);
        $data = $request->validate([
            'study_group' => ['required', Rule::in(['intervention', 'comparison'])],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ], [], ['study_group' => 'kelompok', 'reason' => 'alasan']);

        if ($participant->study_group === $data['study_group']) {
            return back()->with('status', 'Kelompok tidak berubah.');
        }
        $old = $participant->study_group;
        $participant->update(['study_group' => $data['study_group']]);
        AuditLog::record('group_assigned', $participant, ['study_group' => $old], ['study_group' => $data['study_group'], 'reason' => $data['reason']]);

        return back()->with('status', 'Kelompok '.$participant->username.' ditetapkan: '.config('ritnova.groups')[$data['study_group']].'.');
    }

    public function resetPassword(User $participant): RedirectResponse
    {
        abort_unless($participant->isParticipant(), 404);
        $password = TempPassword::make();
        $participant->update(['password' => $password]);
        DB::table('sessions')->where('user_id', $participant->id)->delete();
        AuditLog::record('password_reset', $participant);

        return back()->with('status', 'Kata sandi baru untuk '.$participant->username.' dibuat. Berikan langsung ke peserta.')
            ->with('credentials', [[$participant->username, $password]]);
    }

    public function updateStatus(Request $request, User $participant): RedirectResponse
    {
        abort_unless($participant->isParticipant(), 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'withdrawn'])],
            'reason' => ['required_if:status,withdrawn', 'nullable', 'string', 'max:300'],
        ], ['reason.required_if' => 'Isi alasan pengunduran diri.']);
        $old = $participant->status;
        $participant->update(['status' => $data['status']]);
        if ($data['status'] === 'withdrawn') {
            DB::table('sessions')->where('user_id', $participant->id)->delete();
        }
        AuditLog::record('participant_status', $participant, ['status' => $old], ['status' => $data['status'], 'reason' => $data['reason'] ?? null]);

        return back()->with('status', $data['status'] === 'withdrawn'
            ? $participant->username.' dicatat mengundurkan diri. Data yang sudah ada tetap disimpan.'
            : $participant->username.' diaktifkan kembali.');
    }

    /** Ubah kode peserta (mis. salah ketik saat impor). Data penelitian tetap melekat ke peserta yang sama. */
    public function updateCode(Request $request, User $participant): RedirectResponse
    {
        abort_unless($participant->isParticipant(), 404);
        $request->merge(['username' => Str::upper(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'username' => ['required', 'regex:/^[A-Z0-9-]{3,20}$/', Rule::unique('users', 'username')->ignore($participant->id)],
        ], ['username.regex' => 'Kode hanya huruf, angka, dan tanda hubung, 3–20 karakter.', 'username.unique' => 'Kode itu sudah dipakai peserta lain.'], ['username' => 'kode']);

        $old = $participant->username;
        if ($old === $data['username']) {
            return back()->with('status', 'Kode tidak berubah.');
        }
        $participant->update(['username' => $data['username']]);
        AuditLog::record('participant_code_changed', $participant, ['username' => $old], ['username' => $data['username']]);

        return back()->with('status', "Kode {$old} diubah menjadi {$data['username']}. Peserta login dengan kode baru.");
    }

    /** Hapus peserta yang belum punya data penelitian (mis. akun uji atau salah impor). */
    public function destroy(User $participant): RedirectResponse
    {
        abort_unless($participant->isParticipant(), 404);
        if ($data = $this->researchData($participant)) {
            return back()->withErrors(['delete' => $participant->username.' sudah punya data penelitian ('.implode(', ', array_keys($data)).'), jadi tidak dihapus. Catat sebagai mengundurkan diri bila tidak melanjutkan.']);
        }
        $code = $participant->username;
        DB::table('sessions')->where('user_id', $participant->id)->delete();
        AuditLog::record('participant_deleted', $participant, ['username' => $code, 'study_group' => $participant->study_group], null);
        $participant->delete();

        return redirect()->route('admin.participants.index')->with('status', "Peserta {$code} dihapus.");
    }
}
