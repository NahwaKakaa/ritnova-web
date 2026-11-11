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
        return view('participant.sjl', $this->diaryData($request, $service) + ['manual' => null, 'manualInput' => [], 'manualDial' => null]);
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
        $manualDial = [
            'work' => ['onset' => $this->minutes($input['work_onset']), 'offset' => $this->minutes($input['work_offset']), 'mid' => null],
            'free' => ['onset' => $this->minutes($input['free_onset']), 'offset' => $this->minutes($input['free_offset']), 'mid' => null],
        ];
        $manual = $simulator->calculate([
            new SleepEntry($work['onset']->toDateTimeImmutable(), $work['offset']->toDateTimeImmutable(), SleepEntry::WORKDAY),
            new SleepEntry($free['onset']->toDateTimeImmutable(), $free['offset']->toDateTimeImmutable(), SleepEntry::FREE_DAY),
        ]);

        if ($manual->isSufficient()) {
            $manualDial['work']['mid'] = (int) round(fmod(fmod($manual->mswHours * 60, 1440) + 1440, 1440));
            $manualDial['free']['mid'] = (int) round(fmod(fmod($manual->msfHours * 60, 1440) + 1440, 1440));
        }

        return view('participant.sjl', $this->diaryData($request, $service) + [
            'manual' => $manual,
            'manualInput' => $input,
            'manualDial' => $manual->isSufficient() ? $manualDial : null,
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

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}
