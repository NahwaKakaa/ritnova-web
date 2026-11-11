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
            ! $user->onboarded_at ? ['label' => 'Selesaikan persiapan awal', 'route' => route('onboarding'), 'cta' => 'Lanjutkan persiapan', 'hint' => 'Empat langkah singkat sebelum program dimulai.'] : null,
            ! $status->diaryFilledLastNight() ? ['label' => 'Isi catatan tidur semalam', 'route' => route('diary.create'), 'cta' => 'Catat sekarang', 'hint' => 'Kurang dari satu menit. Catatan lengkap membuat SJL-mu bisa dihitung.'] : null,
            $openPhase ? ['label' => "Isi pengukuran {$openPhase}", 'route' => route('assessments.index'), 'cta' => 'Isi pengukuran', 'hint' => 'Jendela pengukuran sedang terbuka.'] : null,
            $nextSession ? ['label' => 'Lanjutkan Sesi '.str_pad($nextSession['session']->number, 2, '0', STR_PAD_LEFT), 'route' => route('learning.show', $nextSession['session']), 'cta' => 'Mulai sesi', 'hint' => $nextSession['session']->title.', sekitar '.($nextSession['session']->estimated_minutes ?? 8).' menit.'] : null,
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
            // Umpan balik ritme hanya untuk kelompok intervensi (cegah kontaminasi).
            'rhythm' => $user->isIntervention() ? $status->rhythm() : null,
            'weekDiaries' => \App\Models\SleepDiary::where('user_id', $user->id)
                ->whereDate('diary_date', '>=', now()->subDays(7)->toDateString())
                ->get()->keyBy(fn ($d) => $d->diary_date->toDateString()),
        ]);
    }
}
