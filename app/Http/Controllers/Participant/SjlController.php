<?php

namespace App\Http\Controllers\Participant;

use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use App\Http\Controllers\Controller;
use App\Services\SjlService;
use App\Support\SleepTimes;
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
        return view('participant.sjl', $this->diaryData($request, $service) + ['manual' => null, 'manualInput' => []]);
    }

    public function calculate(Request $request, SjlService $service, SocialJetlagCalculator $calculator): View
    {
        $input = $request->validate([
            'work_onset' => ['required', 'date_format:H:i'],
            'work_offset' => ['required', 'date_format:H:i'],
            'free_onset' => ['required', 'date_format:H:i'],
            'free_offset' => ['required', 'date_format:H:i'],
        ]);

        // Simulasi: satu malam kuliah dan satu malam bebas pada tanggal acuan.
        $work = SleepTimes::resolve('2026-01-05', $input['work_onset'], $input['work_offset']);
        $free = SleepTimes::resolve('2026-01-09', $input['free_onset'], $input['free_offset']);

        $simulator = new SocialJetlagCalculator(minWorkdays: 1, minFreeDays: 1);
        $manual = $simulator->calculate([
            new SleepEntry($work['onset']->toDateTimeImmutable(), $work['offset']->toDateTimeImmutable(), SleepEntry::WORKDAY),
            new SleepEntry($free['onset']->toDateTimeImmutable(), $free['offset']->toDateTimeImmutable(), SleepEntry::FREE_DAY),
        ]);

        return view('participant.sjl', $this->diaryData($request, $service) + ['manual' => $manual, 'manualInput' => $input]);
    }

    private function diaryData(Request $request, SjlService $service): array
    {
        $to = now();
        $from = now()->subDays(6)->startOfDay();

        return [
            'result' => $service->calculate($request->user(), $from, $to),
            'from' => $from,
            'to' => $to,
        ];
    }
}
