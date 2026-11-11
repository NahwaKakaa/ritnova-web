<?php

namespace App\Services;

use App\Domain\Sleep\SjlResultData;
use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use App\Models\SjlResult;
use App\Models\SleepDiary;
use App\Models\User;
use Carbon\CarbonInterface;

class SjlService
{
    public function __construct(private SocialJetlagCalculator $calculator) {}

    public function calculate(User $user, CarbonInterface $from, CarbonInterface $to): SjlResultData
    {
        $entries = SleepDiary::query()
            ->where('user_id', $user->id)
            ->whereBetween('diary_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('diary_date')
            ->get()
            ->map(fn (SleepDiary $d) => new SleepEntry(
                onset: $d->sleep_onset?->toDateTimeImmutable(),
                offset: $d->sleep_offset?->toDateTimeImmutable(),
                dayType: $d->day_type,
                usedAlarm: $d->used_alarm,
                reference: $d->id,
            ));

        return $this->calculator->calculate($entries);
    }

    /** Hitung dan simpan hasil untuk fase pengukuran (T0/T1/T2). */
    public function store(User $user, string $phase, CarbonInterface $from, CarbonInterface $to): SjlResult
    {
        $result = $this->calculate($user, $from, $to);

        return SjlResult::create([
            'user_id' => $user->id,
            'phase' => $phase,
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'valid_workdays' => $result->validWorkdays,
            'valid_free_days' => $result->validFreeDays,
            'msw_hours' => $result->mswHours,
            'msf_hours' => $result->msfHours,
            'sjl_minutes' => $result->sjlMinutes,
            'algorithm_version' => $result->algorithmVersion,
            'computed_at' => now(),
        ]);
    }
}
