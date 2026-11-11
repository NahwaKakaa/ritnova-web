<?php

namespace App\Services;

use App\Domain\Sleep\SjlResultData;
use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use App\Support\SleepTimes;

/**
 * Simulasi SJL dari satu malam hari kuliah dan satu malam hari bebas.
 * Dipakai kalkulator interaktif (slider) dan form fallback. Perhitungan tetap
 * lewat SocialJetlagCalculator di server.
 */
class SjlSimulator
{
    /**
     * @param  array{work_onset: string, work_offset: string, free_onset: string, free_offset: string}  $times  format H:i
     * @return array{result: SjlResultData, work: ?array, free: ?array}
     */
    public function simulate(array $times): array
    {
        $work = SleepTimes::resolve('2026-01-05', $times['work_onset'], $times['work_offset']);
        $free = SleepTimes::resolve('2026-01-09', $times['free_onset'], $times['free_offset']);

        $result = (new SocialJetlagCalculator(minWorkdays: 1, minFreeDays: 1))->calculate([
            new SleepEntry($work['onset']->toDateTimeImmutable(), $work['offset']->toDateTimeImmutable(), SleepEntry::WORKDAY),
            new SleepEntry($free['onset']->toDateTimeImmutable(), $free['offset']->toDateTimeImmutable(), SleepEntry::FREE_DAY),
        ]);

        $dial = fn (string $onset, string $offset, ?float $midHours) => $midHours === null ? null : [
            'onset' => self::minutes($onset),
            'offset' => self::minutes($offset),
            'mid' => (int) round(fmod(fmod($midHours * 60, 1440) + 1440, 1440)),
        ];

        return [
            'result' => $result,
            'work' => $result->isSufficient() ? $dial($times['work_onset'], $times['work_offset'], $result->mswHours) : null,
            'free' => $result->isSufficient() ? $dial($times['free_onset'], $times['free_offset'], $result->msfHours) : null,
        ];
    }

    public static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    public static function time(int $minutes): string
    {
        $m = (($minutes % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    public static function human(?int $minutes): ?string
    {
        return $minutes === null ? null : intdiv($minutes, 60).' jam '.($minutes % 60).' menit';
    }
}
