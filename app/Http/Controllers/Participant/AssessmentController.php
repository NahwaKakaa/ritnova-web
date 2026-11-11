<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\AssessmentWindow;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * T0/T1/T2 dibuka berdasarkan jendela pengukuran, TIDAK terkunci oleh progres modul.
 * TODO: form pengisian per instrumen setelah instrumen final dari peneliti tersedia.
 */
class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $windows = AssessmentWindow::where('cohort_id', $request->user()->cohort_id)
            ->orderBy('opens_at')->get();

        return view('participant.assessments.index', compact('windows'));
    }
}
