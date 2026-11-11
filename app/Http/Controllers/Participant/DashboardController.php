<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\SleepDiary;
use App\Services\LearningPath;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LearningPath $path): View
    {
        $user = $request->user();
        $sessions = $user->isIntervention() ? $path->forUser($user) : collect();

        $lastNight = now()->subDay()->toDateString();

        return view('participant.dashboard', [
            'user' => $user,
            'sessions' => $sessions,
            'completedCount' => $sessions->filter(fn ($row) => $row['progress']?->completed_at)->count(),
            'nextSession' => $sessions->first(fn ($row) => $row['decision']->canStart()),
            'diaryFilledLastNight' => SleepDiary::where('user_id', $user->id)
                ->whereDate('diary_date', $lastNight)->where('status', 'complete')->exists(),
            'validDiaryCount' => SleepDiary::where('user_id', $user->id)->where('status', 'complete')->count(),
            'notifications' => $user->unreadNotifications()->latest()->take(5)->get(),
        ]);
    }
}
