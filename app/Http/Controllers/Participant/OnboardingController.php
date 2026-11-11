<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\ParticipantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 04 — Persiapan awal dalam 4 langkah pendek:
 * 1 Persetujuan · 2 Profil dasar · 3 Pola tidur T0 · 4 Kuesioner awal.
 */
class OnboardingController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $status = ParticipantStatus::for($user);
        $windows = $status->windows();
        $t0 = $windows->get('T0');

        $requiredNights = config('ritnova.sjl.min_workdays') + config('ritnova.sjl.min_free_days');
        $t0Nights = $status->validNights($t0);
        $t0Phase = $status->phaseStatus('T0');

        $steps = [
            1 => ['label' => 'Persetujuan', 'done' => true, 'detail' => 'Consent versi aktif sudah disetujui.'],
            2 => ['label' => 'Profil dasar', 'done' => $user->reminder_time !== null, 'detail' => 'Konfirmasi kode peserta dan atur pengingat.'],
            3 => ['label' => 'Pola tidur T0', 'done' => $t0Nights >= $requiredNights, 'detail' => "{$t0Nights} dari minimal {$requiredNights} malam tercatat."],
            4 => ['label' => 'Kuesioner awal', 'done' => $t0Phase === 'done', 'detail' => match ($t0Phase) {
                'done' => 'Semua instrumen T0 sudah diisi.',
                'no_instrument' => 'Instrumen sedang disiapkan tim peneliti.',
                'scheduled' => 'Belum dibuka sesuai jadwal.',
                'closed' => 'Jendela T0 sudah ditutup.',
                default => 'Isi instrumen T0.',
            }],
        ];

        $current = collect($steps)->search(fn ($s) => ! $s['done']) ?: 4;

        return view('participant.onboarding', compact('user', 'steps', 'current', 'windows'));
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
