<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use Illuminate\View\View;

/** TODO: form ubah release_at, close_at, prasyarat (perubahan otomatis masuk audit log). */
class ScheduleController extends Controller
{
    public function index(): View
    {
        $cohorts = Cohort::with(['schedules.learningSession', 'schedules.prerequisite'])->get();

        return view('admin.schedule.index', compact('cohorts'));
    }
}
