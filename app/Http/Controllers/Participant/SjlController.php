<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Services\SjlService;
use App\Services\SjlSimulator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar 10–11 — SJL Check: hasil dari diary 7 hari terakhir dan kalkulator manual.
 * Perhitungan selalu lewat SocialJetlagCalculator (server), bukan JavaScript.
 */
class SjlController extends Controller
{
    public function show(Request $request, SjlService $service): View
    {
        return view('participant.sjl', $this->diaryData($request, $service) + ['manual' => null, 'manualInput' => [], 'manualDial' => null]);
    }

    /** Fallback tanpa JavaScript untuk simulator. */
    public function calculate(Request $request, SjlService $service, SjlSimulator $simulator): View
    {
        $input = $request->validate([
            'work_onset' => ['required', 'date_format:H:i'],
            'work_offset' => ['required', 'date_format:H:i'],
            'free_onset' => ['required', 'date_format:H:i'],
            'free_offset' => ['required', 'date_format:H:i'],
        ]);

        $sim = $simulator->simulate($input);

        return view('participant.sjl', $this->diaryData($request, $service) + [
            'manual' => $sim['result'],
            'manualInput' => $input,
            'manualDial' => $sim['result']->isSufficient() ? ['work' => $sim['work'], 'free' => $sim['free']] : null,
        ]);
    }

    private function diaryData(Request $request, SjlService $service): array
    {
        $to = now();
        $from = now()->subDays(6)->startOfDay();

        $rhythm = \App\Services\ParticipantStatus::for($request->user())->rhythm();

        return [
            'result' => $rhythm['result'],
            'rhythm' => $rhythm,
            'from' => $from,
            'to' => $to,
        ];
    }
}
