<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResponse;
use App\Services\ParticipantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 04 — Persiapan awal (urutan protokol revisi):
 * 1 Persetujuan · 2 Profil dasar · 3 Kuesioner awal (T0) · 4 Catatan tidur T0 (7 malam).
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
            2 => ['label' => 'Profil dasar', 'done' => $user->reminder_time !== null, 'detail' => 'Pastikan kode peserta benar dan atur pengingat.'],
            3 => ['label' => 'Kuesioner awal', 'done' => $t0Phase === 'done', 'detail' => match ($t0Phase) {
                'done' => 'Semua kuesioner awal sudah dikirim.',
                'no_instrument' => 'Kuesioner sedang disiapkan tim peneliti.',
                'scheduled' => 'Belum tersedia. Dibuka '.($windows->get('T0')?->opens_at->translatedFormat('d M Y') ?? 'sesuai jadwal').'.',
                'closed' => 'Jendela pengukuran ditutup.',
                default => 'Isi setiap kuesioner satu kali.',
            }],
            4 => ['label' => 'Catatan tidur awal (T0)', 'done' => $status->diaryDone('T0'), 'detail' => $diary
                ? "Pencatatan tidur T0: {$diary['done']} dari {$diary['target']} malam selesai."
                : 'Jadwal pencatatan ditetapkan tim peneliti.'],
        ];

        $current = collect($steps)->search(fn ($s) => ! $s['done']) ?: 4;
        $baselineDone = $steps[3]['done'] && $steps[4]['done'];

        return view('participant.onboarding', compact('user', 'steps', 'current', 'windows', 'instruments', 'diary', 'baselineDone', 't0Phase'));
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code_confirmed' => ['accepted'],
            'reminder_opt_in' => ['boolean'],
            'reminder_channel' => ['required', 'in:web,whatsapp,both'],
            'reminder_time' => ['required', 'date_format:H:i', 'after_or_equal:06:00', 'before_or_equal:21:00'],
        ], [
            'code_confirmed.accepted' => 'Pastikan kode peserta sudah benar.',
            'reminder_time.after_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
            'reminder_time.before_or_equal' => 'Pilih jam antara 06.00 dan 21.00.',
        ]);

        $request->user()->update([
            'reminder_opt_in' => $request->boolean('reminder_opt_in'),
            'reminder_channel' => $data['reminder_channel'],
            'reminder_time' => $data['reminder_time'],
        ]);

        return redirect()->route('onboarding')->with('status', 'Profil dasar tersimpan.');
    }

    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->reminder_time === null) {
            return back()->withErrors(['onboarding' => 'Lengkapi profil dasar terlebih dahulu.']);
        }

        $user->forceFill(['onboarded_at' => $user->onboarded_at ?? now()])->save();

        return redirect()->route('dashboard')->with('status', 'Persiapan awal selesai. Selamat datang di RITNOVA!');
    }
}
