<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\WeeklyReflection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 13 — refleksi mingguan (< 2 menit). Dukungan perilaku, bukan instrumen studi.
 */
class ReflectionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $week = $user->programWeek() ?? 1;

        return view('participant.reflection', [
            'week' => $week,
            'reflection' => WeeklyReflection::firstWhere(['user_id' => $user->id, 'week' => $week]),
            'history' => WeeklyReflection::where('user_id', $user->id)->where('week', '<>', $week)->orderByDesc('week')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ease_rating' => ['required', 'integer', 'between:1,5'],
            'barriers' => ['nullable', 'array'],
            'barriers.*' => ['in:'.implode(',', array_keys(config('ritnova.reflection_barriers')))],
            'adjustment' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        WeeklyReflection::updateOrCreate(
            ['user_id' => $user->id, 'week' => $user->programWeek() ?? 1],
            ['ease_rating' => $data['ease_rating'], 'barriers' => $data['barriers'] ?? [], 'adjustment' => $data['adjustment'] ?? null],
        );

        return back()->with('status', 'Refleksi tersimpan. Terima kasih!');
    }
}
