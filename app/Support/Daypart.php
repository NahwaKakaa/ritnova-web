<?php

namespace App\Support;

/**
 * Bagian hari menurut jam WITA, untuk suasana latar dan sapaan.
 */
final class Daypart
{
    public static function at(?\DateTimeInterface $time = null): string
    {
        $hour = (int) ($time ?? now(config('ritnova.timezone')))->format('G');

        return match (true) {
            $hour >= 4 && $hour < 10 => 'pagi',
            $hour >= 10 && $hour < 15 => 'siang',
            $hour >= 15 && $hour < 18 => 'sore',
            default => 'malam',
        };
    }

    public static function greeting(?\DateTimeInterface $time = null): string
    {
        return 'Selamat '.self::at($time);
    }
}
