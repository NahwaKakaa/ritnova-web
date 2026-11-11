<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\LearningPath;
use App\Services\ParticipantStatus;
use App\Services\SjlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 18 (progres 12 sesi) + Layar 14 (Insights SJL T0/T1/T2).
 */
class ProgressController extends Controller
{
    public function __invoke(Request $request, LearningPath $path, SjlService $sjl): View
    {
        $user = $request->user();
        $status = ParticipantStatus::for($user);
        $sessions = $path->forUser($user);
        $windows = $status->windows();

        $insights = collect(config('ritnova.phases'))->mapWithKeys(function (string $phase) use ($windows, $status, $sjl, $user) {
            $window = $windows->get($phase);

            // Fase yang belum dibuka belum punya data.
            if ($window === null || $window->opens_at->isFuture()) {
                return [$phase => ['minutes' => null, 'nights' => 0, 'range' => $window?->opens_at->translatedFormat('d M')]];
            }

            [$from, $to] = $status->diaryRange($window);
            $result = $sjl->calculate($user, $from, $to->min(now()));

            return [$phase => [
                'minutes' => $result->sjlMinutes,
                'nights' => $result->validWorkdays + $result->validFreeDays,
                'range' => $from->translatedFormat('d M').' – '.$to->translatedFormat('d M'),
            ]];
        });

        return view('participant.progress', [
            'sessions' => $sessions,
            'completedCount' => $sessions->filter(fn ($row) => $row['progress']?->completed_at)->count(),
            'week' => $user->programWeek(),
            'insights' => $insights->all(),
        ]);
    }
}
