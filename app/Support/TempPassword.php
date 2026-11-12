<?php

namespace App\Support;

/**
 * Kata sandi sementara yang mudah dibacakan: tanpa huruf/angka yang mirip (0/O, 1/l/I).
 */
final class TempPassword
{
    public static function make(int $letters = 4, int $digits = 4): string
    {
        $alpha = 'abcdefghjkmnpqrstuvwxyz';
        $nums = '23456789';
        $out = '';
        for ($i = 0; $i < $letters; $i++) {
            $out .= $alpha[random_int(0, strlen($alpha) - 1)];
        }
        for ($i = 0; $i < $digits; $i++) {
            $out .= $nums[random_int(0, strlen($nums) - 1)];
        }

        return $out;
    }
}
