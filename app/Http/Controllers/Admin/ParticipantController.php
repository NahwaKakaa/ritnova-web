<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** TODO: impor kode peserta dari CSV dan penetapan kelompok berwenang. */
class ParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $participants = User::where('role', User::ROLE_PARTICIPANT)
            ->withCount(['sessionProgress as completed_sessions' => fn ($q) => $q->whereNotNull('completed_at')])
            ->withCount(['sleepDiaries as valid_diaries' => fn ($q) => $q->where('status', 'complete')])
            ->when($request->query('group'), fn ($q, $g) => $q->where('study_group', $g))
            ->orderBy('username')
            ->paginate(50);

        return view('admin.participants.index', compact('participants'));
    }
}
