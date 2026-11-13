<?php

namespace App\Domain\Assessment;

/**
 * Skor turunan instrumen. PHP murni, tanpa framework, diuji di tests/Unit.
 *
 * Aturan (disimpan di instruments.scoring):
 *   method  : none | sum | mean
 *   items   : daftar nomor urut butir yang dihitung; kosong = semua butir numerik
 *
 * Butir "reverse" dibalik terhadap rentang nilainya sendiri: nilai' = min + max − nilai.
 * Bila ada butir yang dihitung tetapi jawabannya kosong/tidak numerik, skor = null
 * (data belum cukup), BUKAN 0.
 */
final class ScoreCalculator
{
    public const VERSION = 'score-v1';

    public const METHODS = ['none', 'sum', 'mean'];

    /**
     * @param  array{method?: string, items?: list<int>}|null  $rule
     * @param  list<array{order: int, numeric: bool, reverse: bool, min: float|int|null, max: float|int|null}>  $items
     * @param  array<int, string|int|float|null>  $answers  order => jawaban mentah
     */
    public static function score(?array $rule, array $items, array $answers): ?float
    {
        $method = $rule['method'] ?? 'none';
        if ($method === 'none' || ! in_array($method, self::METHODS, true)) {
            return null;
        }

        $selected = array_map('intval', $rule['items'] ?? []);
        $counted = array_values(array_filter($items, fn ($i) => $i['numeric'] && ($selected === [] || in_array($i['order'], $selected, true))));
        if ($counted === []) {
            return null;
        }

        $values = [];
        foreach ($counted as $item) {
            $raw = $answers[$item['order']] ?? null;
            if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                return null;
            }
            $v = (float) $raw;
            if ($item['reverse']) {
                if ($item['min'] === null || $item['max'] === null) {
                    return null; // tidak bisa dibalik tanpa rentang
                }
                $v = (float) $item['min'] + (float) $item['max'] - $v;
            }
            $values[] = $v;
        }

        $sum = array_sum($values);

        return round($method === 'mean' ? $sum / count($values) : $sum, 2);
    }
}
