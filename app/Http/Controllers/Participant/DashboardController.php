<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\LearningPath;
use App\Services\ParticipantStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 05 — dashboard: pekerjaan hari ini, progres modul, diary, target, jadwal pengukuran.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, LearningPath $path): View
    {
        $user = $request->user();
        $status = ParticipantStatus::for($user);
        $sessions = $user->isIntervention() ? $path->forUser($user) : collect();
        $week = $user->programWeek();

        $nextSession = $sessions->first(fn ($row) => $row['decision']->canStart());
        $upcoming = $sessions->first(fn ($row) => $row['decision']->status->value === 'locked_time');

        $phases = collect(config('ritnova.phases'))->mapWithKeys(fn ($p) => [$p => $status->phaseStatus($p)]);
        $windows = $status->windows();

        // Fase catatan tidur yang jendelanya sedang terbuka (T0/T1/T2), dan masa program minggu 1–4.
        $diaryPhase = collect(config('ritnova.diary_phases'))->first(fn ($p) => $windows->get($p)?->isOpen());
        $diaryProgress = $diaryPhase ? $status->diaryProgress($diaryPhase) : null;
        $start = $user->cohort?->starts_on;
        // Pencatatan harian selama program berlaku setelah peserta masuk ke salah satu kelompok.
        $inProgram = $user->study_group !== null && $start !== null
            && now()->gte($start->copy()->startOfDay()) && now()->lt($start->copy()->startOfDay()->addDays(28));
        $needsDiary = ($diaryPhase !== null || $inProgram) && ! $status->diaryFilledLastNight();

        $baselineDone = $phases->get('T0') === 'done' && $status->diaryDone('T0');
        $waitingGroup = $user->onboarded_at && $user->study_group === null;
        $allDone = $phases->get('T2') === 'done' && $status->diaryDone('T2');

        // Kuesioner yang sedang terbuka dan belum dikirim.
        $openPhase = $phases->search(fn ($s) => $s === 'open');
        $phaseTask = $openPhase ? match ($openPhase) {
            'T0' => ['label' => 'Kuesioner awal sudah tersedia', 'hint' => 'Isi sekali saja. Jawabanmu tersimpan otomatis.'],
            'M2' => ['label' => 'Kuesioner minggu kedua sudah tersedia', 'hint' => 'Silakan isi untuk melanjutkan pengukuran.'],
            default => ['label' => "Pengukuran {$openPhase} sudah dimulai", 'hint' => 'Isi kuesionernya satu kali dan catat tidur setiap pagi selama tujuh hari.'],
        } : null;

        $diaryHint = $diaryProgress
            ? "Kamu sudah menyelesaikan {$diaryProgress['done']} dari {$diaryProgress['target']} catatan tidur {$diaryPhase}."
            : 'Isi setiap pagi untuk tidur semalam. Kurang dari satu menit.';

        // Daftar tugas hari ini, urut prioritas.
        $tasks = collect([
            ! $user->onboarded_at ? ['label' => 'Selesaikan persiapan awal', 'route' => route('onboarding'), 'cta' => 'Lanjutkan persiapan', 'hint' => 'Beberapa langkah singkat sebelum program dimulai.'] : null,
            $needsDiary ? ['label' => 'Jangan lupa catat tidur semalam, ya!', 'route' => route('diary.create'), 'cta' => 'Catat sekarang', 'hint' => $diaryHint] : null,
            $phaseTask ? [...$phaseTask, 'route' => route('assessments.index'), 'cta' => 'Isi kuesioner'] : null,
            $nextSession ? ['label' => 'Lanjutkan Sesi '.str_pad($nextSession['session']->number, 2, '0', STR_PAD_LEFT), 'route' => route('learning.show', $nextSession['session']), 'cta' => 'Mulai sesi', 'hint' => $nextSession['session']->title.', sekitar '.($nextSession['session']->estimated_minutes ?? 8).' menit.'] : null,
        ])->filter()->values();

        // Umpan balik ritme/SJL hanya untuk kelompok intervensi dan baru setelah program dimulai
        // (saat T0 masih pengukuran awal, nilai SJL tidak ditampilkan).
        $showRhythm = $user->isIntervention() && $start !== null && now()->gte($start->copy()->startOfDay());

        return view('participant.dashboard', [
            'user' => $user,
            'week' => $week,
            'tasks' => $tasks,
            'sessions' => $sessions,
            'completedCount' => $sessions->filter(fn ($row) => $row['progress']?->completed_at)->count(),
            'weekSessions' => $week ? $sessions->filter(fn ($row) => $row['session']->week === $week) : collect(),
            'upcoming' => $upcoming,
            'validNights' => $status->validNights(),
            'activeGoal' => $user->isIntervention() ? $status->activeGoal() : null,
            'windows' => $windows,
            'phases' => $phases,
            'diaryProgress' => $diaryProgress,
            'diaryPhase' => $diaryPhase,
            'baselineDone' => $baselineDone,
            'waitingGroup' => $waitingGroup,
            'allDone' => $allDone,
            'notifications' => $user->unreadNotifications()->latest()->take(3)->get(),
            // Umpan balik ritme hanya untuk kelompok intervensi (cegah kontaminasi).
            'rhythm' => $showRhythm ? $status->rhythm() : null,
            'weekDiaries' => \App\Models\SleepDiary::where('user_id', $user->id)
                ->whereDate('diary_date', '>=', now()->subDays(7)->toDateString())
                ->get()->keyBy(fn ($d) => $d->diary_date->toDateString()),
        ]);
    }
}
