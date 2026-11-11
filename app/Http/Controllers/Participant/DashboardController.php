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
        $openPhase = $phases->search(fn ($s) => $s === 'open');

        // Daftar tugas hari ini, urut prioritas.
        $tasks = collect([
            ! $user->onboarded_at ? ['label' => 'Selesaikan persiapan awal', 'route' => route('onboarding'), 'cta' => 'Lanjutkan'] : null,
            ! $status->diaryFilledLastNight() ? ['label' => 'Isi catatan tidur semalam', 'route' => route('diary.create'), 'cta' => 'Catat Sekarang'] : null,
            $openPhase ? ['label' => "Isi pengukuran {$openPhase}", 'route' => route('assessments.index'), 'cta' => 'Isi'] : null,
            $nextSession ? ['label' => 'Lanjutkan Sesi '.str_pad($nextSession['session']->number, 2, '0', STR_PAD_LEFT), 'route' => route('learning.show', $nextSession['session']), 'cta' => 'Mulai'] : null,
        ])->filter()->values();

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
            'windows' => $status->windows(),
            'phases' => $phases,
            'notifications' => $user->unreadNotifications()->latest()->take(3)->get(),
        ]);
    }
}
