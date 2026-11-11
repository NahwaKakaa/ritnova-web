<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Mengubah input jam (HH:MM) pada "malam tanggal X" menjadi waktu lengkap.
 *
 * - Jam sebelum 12:00 dianggap sudah lewat tengah malam (tanggal X+1).
 * - Jam bangun yang tidak lebih besar dari jam tidur digeser ke hari berikutnya.
 *   Contoh: tidur 04:00, bangun 13:00 -> bangun X+1 13:00.
 */
final class SleepTimes
{
    /**
     * @return array{onset: ?CarbonImmutable, offset: ?CarbonImmutable, bed: ?CarbonImmutable}
     */
    public static function resolve(string $nightDate, ?string $onset, ?string $offset, ?string $bed = null, ?string $tz = null): array
    {
        $tz ??= config('ritnova.timezone');
        $night = CarbonImmutable::parse($nightDate, $tz)->startOfDay();

        $at = function (?string $time) use ($night, $tz): ?CarbonImmutable {
            if ($time === null || $time === '') {
                return null;
            }
            $dt = CarbonImmutable::parse($night->toDateString().' '.$time, $tz);

            return $dt->hour < 12 ? $dt->addDay() : $dt;
        };

        $onsetAt = $at($onset);
        $offsetAt = $at($offset);

        if ($onsetAt && $offsetAt && $offsetAt <= $onsetAt) {
            $offsetAt = $offsetAt->addDay();
        }

        return ['onset' => $onsetAt, 'offset' => $offsetAt, 'bed' => $at($bed)];
    }
}
