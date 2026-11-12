<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StudyMetrics;
use App\Support\AdminCohort;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExposureController extends Controller
{
    public function index(Request $request): View
    {
        $cohort = AdminCohort::current();
        abort_if($cohort === null, 404);
        $current = $cohort->weekAt(now()) ?? 1;
        $week = (int) $request->integer('minggu', $current);
        $week = max(1, min(4, $week));

        return view('admin.exposure.index', [
            'cohort' => $cohort,
            'week' => $week,
            'currentWeek' => $current,
            'data' => (new StudyMetrics($cohort))->exposure($week),
        ]);
    }
}
