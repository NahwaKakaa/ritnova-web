<?php

namespace App\Http\Controllers\Participant;

use App\Domain\Screening\EligibilityRules;
use App\Http\Controllers\Controller;
use App\Models\AssessmentResponse;
use App\Models\EligibilityScreening;
use App\Models\ParticipantProfile;
use App\Services\ParticipantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Layar 04 — Persiapan awal (urutan protokol revisi):
 * 1 Persetujuan · 2 Profil dasar · 3 Skrining kelayakan · 4 Kuesioner awal (T0) · 5 Catatan tidur T0 (7 malam).
 */
class OnboardingController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $status = ParticipantStatus::for($user);
        $windows = $status->windows();
        $t0Phase = $status->phaseStatus('T0');
        $diary = $status->diaryProgress('T0');

        // Kuesioner awal per instrumen (mis. SSE dan Chronotype), status masing-masing.
        $submitted = AssessmentResponse::where('user_id', $user->id)->where('phase', 'T0')
            ->whereNotNull('submitted_at')->pluck('instrument_id')->flip();
        $instruments = $status->instrumentsFor('T0')->map(fn ($i) => [
            'name' => $i->name,
            'code' => $i->code,
            'done' => $submitted->has($i->id),
            'url' => route('assessments.show', ['phase' => 'T0', 'instrument' => $i->code]),
        ]);

        $steps = [
            1 => ['label' => 'Persetujuan penelitian', 'done' => true, 'detail' => 'Dokumen persetujuan versi aktif sudah kamu setujui.'],
            2 => ['label' => 'Profil dasar', 'done' => $this->profileDone($user), 'detail' => 'Lengkapi data diri, pastikan kode peserta benar, dan atur pengingat.'],
            3 => ['label' => 'Skrining kelayakan', 'done' => $user->screening !== null, 'detail' => $user->screening
                ? 'Jawaban skrining sudah terkirim.'
                : 'Delapan pertanyaan singkat untuk memastikan kamu bisa mengikuti prosedur penelitian.'],
            4 => ['label' => 'Kuesioner awal', 'done' => $t0Phase === 'done', 'detail' => match ($t0Phase) {
                'done' => 'Semua kuesioner awal sudah dikirim.',
                'no_instrument' => 'Kuesioner sedang disiapkan tim peneliti.',
                'scheduled' => 'Belum tersedia. Dibuka '.($windows->get('T0')?->opens_at->translatedFormat('d M Y') ?? 'sesuai jadwal').'.',
                'closed' => 'Jendela pengukuran ditutup.',
                default => 'Isi setiap kuesioner satu kali.',
            }],
            5 => ['label' => 'Catatan tidur awal (T0)', 'done' => $status->diaryDone('T0'), 'detail' => $diary
                ? "Pencatatan tidur T0: {$diary['done']} dari {$diary['target']} malam selesai."
                : 'Jadwal pencatatan ditetapkan tim peneliti.'],
        ];

        $current = collect($steps)->search(fn ($s) => ! $s['done']) ?: 5;
        $baselineDone = $steps[4]['done'] && $steps[5]['done'];
        $questions = EligibilityRules::QUESTIONS;

        return view('participant.onboarding', compact('user', 'steps', 'current', 'windows', 'instruments', 'diary', 'baselineDone', 't0Phase', 'questions'));
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code_confirmed' => ['accepted'],
            'reminder_opt_in' => ['boolean'],
            'reminder_channel' => ['required', 'in:web,whatsapp,both'],
            'reminder_time' => ['required', 'date_format:H:i', 'after_or_equal:06:00', 'before_or_equal:21:00'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'after:1970-01-01', 'before:-10 years'],
            'sex' => ['required', Rule::in(array_keys(ParticipantProfile::SEXES))],
            'class_group' => ['required', Rule::in(array_keys(ParticipantProfile::CLASSES))],
            'residence' => ['required', Rule::in(array_keys(ParticipantProfile::RESIDENCES))],
            'residence_other' => ['required_if:residence,lainnya', 'nullable', 'string', 'max:100'],
            'whatsapp' => ['required', 'regex:/^08[0-9]{8,11}$/'],
        ], [
            'code_confirmed.accepted' => 'Pastikan kode peserta sudah benar.',
            'birth_place.required' => 'Isi tempat lahir.',
            'birth_date.required' => 'Isi tanggal lahir.',
            'birth_date.*' => 'Periksa kembali tanggal lahir.',
            'sex.required' => 'Pilih jenis kelamin.',
            'class_group.required' => 'Pilih kelas.',
            'residence.required' => 'Pilih tempat tinggal.',
            'residence_other.required_if' => 'Tuliskan tempat tinggalmu.',
            'whatsapp.required' => 'Isi nomor WhatsApp.',
            'whatsapp.regex' => 'Nomor WhatsApp diawali 08, hanya angka, 10–13 digit. Contoh: 081234567890.',
            'reminder_time.after_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
            'reminder_time.before_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
        ]);

        $user = $request->user();
        $user->update([
            'reminder_opt_in' => $request->boolean('reminder_opt_in'),
            'reminder_channel' => $data['reminder_channel'],
            'reminder_time' => $data['reminder_time'],
        ]);
        $user->profile()->updateOrCreate([], [
            ...collect($data)->only(['birth_place', 'birth_date', 'sex', 'class_group', 'residence', 'whatsapp'])->all(),
            'residence_other' => $data['residence'] === 'lainnya' ? $data['residence_other'] : null,
        ]);

        return redirect()->route('onboarding')->with('status', 'Profil dasar tersimpan.');
    }

    public function saveScreening(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $this->profileDone($user)) {
            return back()->withErrors(['onboarding' => 'Lengkapi profil dasar terlebih dahulu.']);
        }
        if ($user->screening) {
            return redirect()->route('onboarding')->with('status', 'Jawaban skrining sudah terkirim sebelumnya.');
        }

        $rules = [];
        $messages = [];
        foreach (EligibilityRules::QUESTIONS as $n => [, $options]) {
            $rules["q.$n"] = ['required', Rule::in(array_keys($options))];
            $messages["q.$n.*"] = "Pilih jawaban untuk pertanyaan nomor $n.";
        }
        $answers = $request->validate($rules, $messages)['q'];
        ksort($answers);

        $age = $user->profile->ageOn(now());
        $result = EligibilityRules::evaluate($answers, $age);
        EligibilityScreening::create([
            'user_id' => $user->id,
            'answers' => $answers,
            'result' => $result['result'],
            'flags' => collect($result)->except('result')->all(),
            'age' => max(0, $age),
            'rules_version' => EligibilityRules::VERSION,
            'submitted_at' => now(),
        ]);

        return redirect()->route('onboarding')->with('status', 'Terima kasih, jawaban skrining tersimpan.');
    }

    private function profileDone($user): bool
    {
        return $user->reminder_time !== null && $user->profile !== null;
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->profileDone($user) || ! $user->screening) {
            return back()->withErrors(['onboarding' => 'Lengkapi profil dasar dan skrining kelayakan terlebih dahulu.']);
        }

        $user->forceFill(['onboarded_at' => $user->onboarded_at ?? now()])->save();

        return redirect()->route('dashboard')->with('status', 'Persiapan awal selesai. Selamat datang di RITNOVA!');
    }
}
