<?php

namespace App\Domain\Sleep;

use DateTimeImmutable;

/**
 * Kalkulator Social Jetlag (SJL).
 *
 *   midpoint = onset + (offset - onset) / 2       (aman untuk tidur lintas tengah malam)
 *   MSW      = rata-rata midpoint hari kuliah
 *   MSF      = rata-rata midpoint hari bebas
 *   SJL      = |MSF - MSW|
 *
 * Rumus ini adalah versi dasar (tanpa koreksi sleep debt). Versi algoritma
 * disimpan bersama hasil agar setiap angka dapat diaudit.
 */
final class SocialJetlagCalculator
{
    public const VERSION = 'sjl-midpoint-v1';

    /**
     * Jam sebelum titik ini dianggap "malam sebelumnya" (dikurangi 24 jam)
     * agar rata-rata midpoint sekitar tengah malam tidak rusak.
     * Contoh: 23:00 -> -1.0, 03:00 -> 3.0, 13:00 -> 13.0.
     */
    private const PIVOT_HOUR = 18.0;

    public function __construct(
        private int $minWorkdays = 2,
        private int $minFreeDays = 1,
        private int $minSleepMinutes = 60,
        private int $maxSleepMinutes = 960,
    ) {}

    /** Durasi tidur dalam menit. */
    public function durationMinutes(DateTimeImmutable $onset, DateTimeImmutable $offset): int
    {
        return intdiv($offset->getTimestamp() - $onset->getTimestamp(), 60);
    }

    public function midpoint(DateTimeImmutable $onset, DateTimeImmutable $offset): DateTimeImmutable
    {
        $half = intdiv($offset->getTimestamp() - $onset->getTimestamp(), 2);

        return $onset->modify(sprintf('+%d seconds', $half));
    }

    /** Midpoint sebagai jam desimal ternormalisasi (rentang -6 .. 18). */
    public function midpointHours(DateTimeImmutable $onset, DateTimeImmutable $offset): float
    {
        $mid = $this->midpoint($onset, $offset);
        $hours = (int) $mid->format('G') + ((int) $mid->format('i')) / 60 + ((int) $mid->format('s')) / 3600;

        return $hours >= self::PIVOT_HOUR ? $hours - 24 : $hours;
    }

    /**
     * Periksa satu entri. Mengembalikan alasan penolakan, atau null bila valid.
     */
    public function validate(SleepEntry $entry): ?string
    {
        if ($entry->onset === null || $entry->offset === null) {
            return 'incomplete';          // jam tidur/bangun belum diisi
        }

        if (! in_array($entry->dayType, [SleepEntry::WORKDAY, SleepEntry::FREE_DAY], true)) {
            return 'unclassified_day';    // jenis hari belum dipilih
        }

        $duration = $this->durationMinutes($entry->onset, $entry->offset);

        if ($duration < $this->minSleepMinutes || $duration > $this->maxSleepMinutes) {
            return 'implausible_duration'; // "Periksa kembali waktu tidur dan bangun"
        }

        return null;
    }

    /**
     * @param  iterable<SleepEntry>  $entries
     */
    public function calculate(iterable $entries): SjlResultData
    {
        $work = [];
        $free = [];
        $rejected = [];

        foreach ($entries as $entry) {
            $reason = $this->validate($entry);

            if ($reason !== null) {
                $rejected[] = ['reference' => $entry->reference, 'reason' => $reason];

                continue;
            }

            $mid = $this->midpointHours($entry->onset, $entry->offset);

            if ($entry->dayType === SleepEntry::WORKDAY) {
                $work[] = $mid;
            } else {
                $free[] = $mid;
            }
        }

        $msw = $work === [] ? null : array_sum($work) / count($work);
        $msf = $free === [] ? null : array_sum($free) / count($free);

        $sufficient = count($work) >= $this->minWorkdays && count($free) >= $this->minFreeDays;

        return new SjlResultData(
            status: $sufficient ? SjlResultData::STATUS_OK : SjlResultData::STATUS_INSUFFICIENT,
            validWorkdays: count($work),
            validFreeDays: count($free),
            mswHours: $msw === null ? null : round($msw, 4),
            msfHours: $msf === null ? null : round($msf, 4),
            sjlMinutes: $sufficient ? (int) round(abs($msf - $msw) * 60) : null,
            algorithmVersion: self::VERSION,
            rejected: $rejected,
        );
    }
}
