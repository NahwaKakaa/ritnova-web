<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $cohort = AdminCohort::current();
        if ($cohort === null) {
            return view('admin.empty');
        }
        $metrics = new StudyMetrics($cohort);

        return view('admin.dashboard', [
            'cohort' => $cohort,
            'overview' => $metrics->overview(),
            'diary' => $metrics->diaryRate(),
            'sessions' => $metrics->sessionRate(),
            'weeks' => $metrics->weekCompletion(),
            'phases' => $metrics->phaseCompletion(),
            'followUps' => $metrics->followUps(),
            'week' => $cohort->weekAt(now()),
            'target' => config('ritnova.target_population'),
            'cronStale' => app()->isProduction() && (($beat = \Illuminate\Support\Facades\Cache::get('ritnova:scheduler-heartbeat')) === null || now()->diffInMinutes($beat, true) > 10),
        ]);
    }
}
