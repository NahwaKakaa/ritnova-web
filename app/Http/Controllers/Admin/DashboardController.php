<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\SessionProgress;
use App\Models\SleepDiary;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $participants = User::where('role', User::ROLE_PARTICIPANT);

        return view('admin.dashboard', [
            'participantCount' => (clone $participants)->count(),
            'consentCount' => Consent::whereNull('withdrawn_at')->distinct('user_id')->count('user_id'),
            'validDiaryCount' => SleepDiary::where('status', 'complete')->count(),
            'completedSessions' => SessionProgress::whereNotNull('completed_at')->count(),
            'byGroup' => (clone $participants)->selectRaw('study_group, count(*) as total')->groupBy('study_group')->pluck('total', 'study_group'),
        ]);
    }
}
