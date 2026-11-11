<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 12 — RITNOVA Goals: satu target spesifik per minggu + rencana aksi + review akhir minggu.
 */
class GoalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $week = $user->programWeek() ?? 1;

        return view('participant.goals', [
            'week' => $week,
            'goal' => Goal::where('user_id', $user->id)->where('week', $week)->first(),
            'history' => Goal::where('user_id', $user->id)->where('week', '<>', $week)->orderByDesc('week')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'max:255'],
            'action_plan' => ['nullable', 'array', 'max:3'],
            'action_plan.*' => ['nullable', 'string', 'max:255'],
            'if_then_plan' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        Goal::updateOrCreate(
            ['user_id' => $user->id, 'week' => $user->programWeek() ?? 1],
            [
                'target' => $data['target'],
                'action_plan' => array_values(array_filter($data['action_plan'] ?? [])),
                'if_then_plan' => $data['if_then_plan'] ?? null,
                'status' => 'active',
            ],
        );

        return back()->with('status', 'Target tersimpan.');
    }

    /** Review akhir minggu tanpa menghakimi. */
    public function review(Request $request, Goal $goal): RedirectResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 404);

        $goal->update($request->validate(['status' => ['required', 'in:achieved,partial,not_achieved']]));

        return back()->with('status', 'Evaluasi target tersimpan.');
    }
}
