<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentItemResponse;
use App\Models\AssessmentResponse;
use App\Models\AuditLog;
use App\Models\CohortSessionSchedule;
use App\Models\ExposureEvent;
use App\Models\Goal;
use App\Models\SessionProgress;
use App\Models\SjlResult;
use App\Models\SleepDiary;
use App\Models\SleepDiaryRevision;
use App\Models\User;
use App\Models\WeeklyReflection;
use App\Support\AdminCohort;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor dataset penelitian (Layar admin 04).
 * - Satu file CSV per tabel, dibatasi pada kohort yang sedang dipilih.
 * - Nilai yang tidak ada dibiarkan KOSONG, tidak diisi nol.
 * - Waktu dalam ISO 8601 dengan offset zona waktu (+08:00 untuk WITA).
 * - Data mentah (diary) dan hasil kalkulasi (SJL) dipisah; SJL membawa versi algoritmanya.
 */
class ExportController extends Controller
{
    /** Kamus data: dataset => [judul, keterangan, [kolom => arti]] */
    public const DATASETS = [
        'participants' => ['Peserta', 'Satu baris per peserta: kelompok, status, persetujuan, persiapan.', [
            'participant_code' => 'Kode penelitian (pseudonim)',
            'group_code' => 'intervention / comparison',
            'cohort' => 'Nama kohort',
            'status' => 'active / withdrawn',
            'consent_version' => 'Versi dokumen persetujuan terakhir',
            'consent_at' => 'Waktu menyetujui',
            'consent_withdrawn_at' => 'Waktu menarik persetujuan (kosong bila tidak)',
            'onboarded_at' => 'Waktu menyelesaikan persiapan',
            'created_at' => 'Waktu akun dibuat',
        ]],
        'sleep_diaries' => ['Catatan tidur (mentah)', 'Satu baris per malam, isian terakhir peserta. Riwayat perubahan ada di dataset revisi.', [
            'participant_code' => 'Kode penelitian',
            'group_code' => 'intervention / comparison',
            'diary_date' => 'Tanggal malam (malam dimulai)',
            'day_type' => 'workday (hari kuliah) / free_day (hari bebas), untuk hari setelah bangun',
            'bed_at' => 'Masuk tempat tidur, datetime + zona waktu',
            'sleep_onset' => 'Mulai benar-benar tidur, datetime + zona waktu',
            'sleep_offset' => 'Bangun terakhir, datetime + zona waktu',
            'used_alarm' => '1 = pakai alarm, 0 = tidak, kosong = tidak diisi',
            'nap_minutes' => 'Tidur siang (menit)',
            'status' => 'complete / partial',
            'revision_count' => 'Jumlah perubahan setelah isian pertama',
            'created_at' => 'Isian pertama',
            'updated_at' => 'Perubahan terakhir',
        ]],
        'diary_revisions' => ['Revisi catatan tidur', 'Setiap perubahan isian diary: nilai sebelum dan sesudah.', [
            'participant_code' => 'Kode penelitian',
            'diary_date' => 'Tanggal malam',
            'edited_by' => 'participant / researcher / admin',
            'before' => 'Nilai sebelum (JSON)',
            'after' => 'Nilai sesudah (JSON)',
            'created_at' => 'Waktu perubahan',
        ]],
        'sjl_results' => ['Hasil SJL', 'Output formula SJL per fase, lengkap dengan versi algoritma.', [
            'participant_code' => 'Kode penelitian',
            'group_code' => 'intervention / comparison',
            'phase' => 'T0 / T1 / T2 (kosong = perhitungan berjalan)',
            'period_start' => 'Awal rentang diary',
            'period_end' => 'Akhir rentang diary',
            'valid_workdays' => 'Malam hari kuliah yang valid',
            'valid_free_days' => 'Malam hari bebas yang valid',
            'msw_hours' => 'Midpoint hari kuliah (jam sejak 00.00)',
            'msf_hours' => 'Midpoint hari bebas (jam sejak 00.00)',
            'sjl_minutes' => 'SJL = |MSF − MSW| dalam menit; kosong = data belum cukup',
            'algorithm_version' => 'Versi rumus',
            'computed_at' => 'Waktu dihitung',
        ]],
        'session_progress' => ['Paparan per sesi', 'Dosis teramati: satu baris per peserta per sesi yang pernah dibuka.', [
            'participant_code' => 'Kode penelitian',
            'session_number' => '1–12',
            'content_version' => 'Versi materi yang diterima',
            'release_at' => 'Rilis server untuk kohort',
            'first_open_at' => 'Pertama dibuka',
            'started_at' => 'Mulai aktivitas',
            'start_delay_minutes' => 'Jeda rilis → mulai (menit)',
            'media_completion_pct' => '0–1',
            'quiz_completed_at' => 'Cek pemahaman selesai',
            'action_completed_at' => 'Aksi selesai',
            'completed_at' => 'Semua syarat terpenuhi',
            'active_seconds' => 'Waktu aktif terekam',
            'is_late' => '1 = dikerjakan setelah batas',
            'protocol_deviation' => '1 = ditandai penyimpangan protokol',
        ]],
        'exposure_events' => ['Log event paparan', 'Event mentah: open, start, media_progress, quiz_submit, action_submit, complete, heartbeat.', [
            'participant_code' => 'Kode penelitian',
            'session_number' => '1–12',
            'event' => 'Jenis event',
            'content_version' => 'Versi materi',
            'payload' => 'Detail event (JSON)',
            'occurred_at' => 'Waktu server',
        ]],
        'assessment_items' => ['Respons item instrumen', 'Format panjang: satu baris per jawaban butir.', [
            'participant_code' => 'Kode penelitian',
            'group_code' => 'intervention / comparison',
            'phase' => 'T0 / T1 / T2',
            'instrument_code' => 'Kode instrumen',
            'instrument_version' => 'Versi instrumen saat diisi',
            'item_order' => 'Nomor butir',
            'value' => 'Jawaban mentah',
            'answered_at' => 'Waktu menjawab',
        ]],
        'assessment_scores' => ['Skor instrumen', 'Satu baris per peserta per instrumen per fase.', [
            'participant_code' => 'Kode penelitian',
            'group_code' => 'intervention / comparison',
            'phase' => 'T0 / T1 / T2',
            'instrument_code' => 'Kode instrumen',
            'instrument_version' => 'Versi instrumen',
            'score' => 'Skor turunan (kosong bila belum ada aturan skoring)',
            'started_at' => 'Mulai mengisi',
            'submitted_at' => 'Dikirim',
        ]],
        'goals' => ['Target & rencana aksi', 'Kelompok intervensi.', [
            'participant_code' => 'Kode penelitian',
            'week' => 'Minggu program',
            'target' => 'Target perilaku',
            'action_plan' => 'Langkah (JSON)',
            'if_then_plan' => 'Rencana jika–maka',
            'status' => 'active / achieved / partial / not_achieved',
            'updated_at' => 'Perubahan terakhir',
        ]],
        'reflections' => ['Refleksi mingguan', 'Kelompok intervensi.', [
            'participant_code' => 'Kode penelitian',
            'week' => 'Minggu program',
            'ease_rating' => '1–5',
            'barriers' => 'Hambatan (JSON)',
            'adjustment' => 'Penyesuaian',
            'updated_at' => 'Perubahan terakhir',
        ]],
    ];

    public function index(): View
    {
        return view('admin.export.index', [
            'datasets' => self::DATASETS,
            'cohort' => AdminCohort::current(),
            'recent' => AuditLog::with('user:id,username')->whereIn('action', ['export_dataset', 'export_reminders'])
                ->latest('created_at')->limit(8)->get(),
        ]);
    }

    public function download(string $dataset): StreamedResponse
    {
        abort_unless(array_key_exists($dataset, self::DATASETS), 404);
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $users = User::where('role', User::ROLE_PARTICIPANT)->where('cohort_id', $cohort->id)->get(['id', 'username', 'study_group', 'role'])->keyBy('id');
        $ids = $users->keys();

        $rows = match ($dataset) {
            'participants' => $this->participants($cohort->id, $cohort->name),
            'sleep_diaries' => SleepDiary::withCount('revisions')->whereIn('user_id', $ids)->orderBy('user_id')->orderBy('diary_date')->lazy()
                ->map(fn ($d) => [$users[$d->user_id]->username, $users[$d->user_id]->study_group, $d->diary_date->toDateString(), $d->day_type,
                    $this->ts($d->bed_at), $this->ts($d->sleep_onset), $this->ts($d->sleep_offset),
                    $d->used_alarm === null ? '' : (int) $d->used_alarm, $d->nap_minutes, $d->status, $d->revisions_count,
                    $this->ts($d->created_at), $this->ts($d->updated_at)]),
            'diary_revisions' => SleepDiaryRevision::with(['diary:id,user_id,diary_date', 'editor:id,role'])
                ->whereHas('diary', fn ($q) => $q->whereIn('user_id', $ids))->orderBy('id')->lazy()
                ->map(fn ($r) => [$users[$r->diary->user_id]->username, $r->diary->diary_date->toDateString(), $r->editor?->role ?? '',
                    json_encode($r->before, JSON_UNESCAPED_UNICODE), json_encode($r->after, JSON_UNESCAPED_UNICODE), $this->ts($r->created_at)]),
            'sjl_results' => SjlResult::whereIn('user_id', $ids)->orderBy('user_id')->orderBy('period_end')->lazy()
                ->map(fn ($r) => [$users[$r->user_id]->username, $users[$r->user_id]->study_group, $r->phase, $r->period_start->toDateString(), $r->period_end->toDateString(),
                    $r->valid_workdays, $r->valid_free_days, $r->msw_hours, $r->msf_hours, $r->sjl_minutes, $r->algorithm_version, $this->ts($r->computed_at)]),
            'session_progress' => $this->progress($cohort->id, $users),
            'exposure_events' => ExposureEvent::with('learningSession:id,number')->whereIn('user_id', $ids)->orderBy('occurred_at')->lazy()
                ->map(fn ($e) => [$users[$e->user_id]->username, $e->learningSession?->number, $e->event, $e->content_version,
                    $e->payload ? json_encode($e->payload, JSON_UNESCAPED_UNICODE) : '', $this->ts($e->occurred_at)]),
            'assessment_items' => AssessmentItemResponse::with(['response.instrument:id,code', 'item:id,order'])
                ->whereHas('response', fn ($q) => $q->whereIn('user_id', $ids))->orderBy('assessment_response_id')->lazy()
                ->map(fn ($i) => [$users[$i->response->user_id]->username, $users[$i->response->user_id]->study_group, $i->response->phase,
                    $i->response->instrument?->code, $i->response->instrument_version, $i->item?->order, $i->value, $this->ts($i->answered_at)]),
            'assessment_scores' => AssessmentResponse::with('instrument:id,code')->whereIn('user_id', $ids)->orderBy('user_id')->orderBy('phase')->lazy()
                ->map(fn ($r) => [$users[$r->user_id]->username, $users[$r->user_id]->study_group, $r->phase, $r->instrument?->code, $r->instrument_version,
                    $r->score, $this->ts($r->started_at), $this->ts($r->submitted_at)]),
            'goals' => Goal::whereIn('user_id', $ids)->orderBy('user_id')->orderBy('week')->lazy()
                ->map(fn ($g) => [$users[$g->user_id]->username, $g->week, $g->target, json_encode($g->action_plan, JSON_UNESCAPED_UNICODE), $g->if_then_plan, $g->status, $this->ts($g->updated_at)]),
            'reflections' => WeeklyReflection::whereIn('user_id', $ids)->orderBy('user_id')->orderBy('week')->lazy()
                ->map(fn ($r) => [$users[$r->user_id]->username, $r->week, $r->ease_rating, json_encode($r->barriers, JSON_UNESCAPED_UNICODE), $r->adjustment, $this->ts($r->updated_at)]),
        };

        AuditLog::record('export_dataset', $cohort, null, ['dataset' => $dataset]);

        return $this->csv(
            'ritnova-'.str_replace('_', '-', $dataset).'-'.now()->format('Ymd-Hi').'.csv',
            array_keys(self::DATASETS[$dataset][2]),
            $rows,
        );
    }

    /** Kamus data semua dataset dalam satu CSV. */
    public function dictionary(): StreamedResponse
    {
        $rows = [];
        foreach (self::DATASETS as $key => [$title, $desc, $fields]) {
            foreach ($fields as $field => $meaning) {
                $rows[] = [$key, $field, $meaning];
            }
        }

        return $this->csv('ritnova-kamus-data.csv', ['dataset', 'field', 'description'], $rows);
    }

    /**
     * Daftar target pengingat untuk blast WhatsApp oleh klien.
     * Jenis: diary (belum mengisi diary semalam). Kelompok dipisah lewat ?group=.
     */
    public function reminders(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:diary'],
            'group' => ['nullable', 'in:intervention,comparison'],
        ]);
        $cohort = AdminCohort::current();
        $lastNight = now()->subDay()->toDateString();

        $users = User::where('role', User::ROLE_PARTICIPANT)
            ->where('status', 'active')
            ->when($cohort, fn ($q) => $q->where('cohort_id', $cohort->id))
            ->when($data['group'] ?? null, fn ($q, $g) => $q->where('study_group', $g))
            ->whereHas('consents', fn ($q) => $q->whereNull('withdrawn_at'))
            ->whereDoesntHave('sleepDiaries', fn ($q) => $q->whereDate('diary_date', $lastNight)->where('status', 'complete'))
            ->orderBy('username')
            ->get(['username', 'study_group']);

        AuditLog::record('export_reminders', $cohort, null, ['type' => $data['type'], 'group' => $data['group'] ?? null, 'count' => $users->count()]);

        return $this->csv('pengingat-'.$data['type'].'-'.now()->format('Ymd-Hi').'.csv',
            ['participant_code', 'group', 'reminder'],
            $users->map(fn ($u) => [$u->username, $u->study_group, 'Lengkapi catatan tidur semalam']));
    }

    private function participants(int $cohortId, string $cohortName): iterable
    {
        return User::with(['consents' => fn ($q) => $q->with('version:id,version')->latest('agreed_at')])
            ->where('role', User::ROLE_PARTICIPANT)->where('cohort_id', $cohortId)->orderBy('username')->lazy()
            ->map(function (User $u) use ($cohortName) {
                $c = $u->consents->first();

                return [$u->username, $u->study_group, $cohortName, $u->status, $c?->version?->version, $this->ts($c?->agreed_at),
                    $this->ts($c?->withdrawn_at), $this->ts($u->onboarded_at), $this->ts($u->created_at)];
            });
    }

    private function progress(int $cohortId, $users): iterable
    {
        $schedules = CohortSessionSchedule::where('cohort_id', $cohortId)->get()->keyBy('learning_session_id');

        return SessionProgress::with('learningSession:id,number')->whereIn('user_id', $users->keys())
            ->orderBy('user_id')->orderBy('learning_session_id')->lazy()
            ->map(function ($p) use ($users, $schedules) {
                $release = $schedules->get($p->learning_session_id)?->release_at;

                return [$users[$p->user_id]->username, $p->learningSession?->number, $p->content_version, $this->ts($release),
                    $this->ts($p->first_open_at), $this->ts($p->started_at),
                    $release && $p->started_at ? (int) $release->diffInMinutes($p->started_at, false) : '',
                    $p->media_completion_pct, $this->ts($p->quiz_completed_at), $this->ts($p->action_completed_at), $this->ts($p->completed_at),
                    $p->active_seconds, (int) $p->is_late, (int) $p->protocol_deviation];
            });
    }

    private function ts(?CarbonInterface $t): string
    {
        return $t?->copy()->setTimezone(config('ritnova.timezone'))->toIso8601String() ?? '';
    }

    private function csv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => $v ?? '', $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
