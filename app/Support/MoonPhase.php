<?php

namespace App\Support;

/**
 * Bentuk bulan untuk visual progres (tampilan saja).
 * Fraksi 0 = bulan baru, 1 = purnama, menerang dari sisi kanan.
 */
final class MoonPhase
{
    public static function path(float $fraction, float $c = 20, float $r = 16): string
    {
        $f = max(0.0, min(1.0, $fraction));

        if ($f <= 0.0) {
            return '';
        }

        if ($f >= 1.0) {
            return sprintf('M %1$s %2$s A %3$s %3$s 0 1 1 %4$s %2$s Z', $c, $c - $r, $r, $c - 0.01);
        }

        $rx = round(abs(1 - 2 * $f) * $r, 2);
        $sweep = $f < 0.5 ? 0 : 1;

        return sprintf(
            'M %1$s %2$s A %3$s %3$s 0 0 1 %1$s %4$s A %5$s %3$s 0 0 %6$d %1$s %2$s Z',
            $c, $c - $r, $r, $c + $r, $rx, $sweep,
        );
    }
}
