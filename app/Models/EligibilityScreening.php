<?php

namespace App\Models;

use App\Domain\Screening\EligibilityRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityScreening extends Model
{
    public const RESULTS = [
        EligibilityRules::ELIGIBLE => ['Memenuhi kriteria', 'pill-done'],
        EligibilityRules::CLARIFY => ['Perlu klarifikasi', 'pill-warn'],
        EligibilityRules::INELIGIBLE => ['Tidak memenuhi kriteria', 'pill-muted'],
    ];

    protected $fillable = ['user_id', 'answers', 'result', 'flags', 'age', 'rules_version', 'submitted_at'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'flags' => 'array', 'submitted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resultLabel(): string
    {
        return self::RESULTS[$this->result][0] ?? $this->result;
    }

    public function resultClass(): string
    {
        return self::RESULTS[$this->result][1] ?? 'pill-muted';
    }

    /** Ringkasan penanda untuk petugas, mis. "Tidak: no. 1, 4 · Klarifikasi: no. 6 · Minta dihubungi". */
    public function flagSummary(): string
    {
        $f = $this->flags ?? [];

        return implode(' · ', array_filter([
            ! empty($f['ineligible']) ? 'Tidak: no. '.implode(', ', $f['ineligible']) : null,
            ! empty($f['clarify']) ? 'Klarifikasi: no. '.implode(', ', $f['clarify']) : null,
            ! empty($f['contact']) ? 'Minta dihubungi' : null,
            ! empty($f['minor']) ? 'Di bawah 18 tahun' : null,
        ]));
    }

    public function answerLabel(int $n): string
    {
        $key = $this->answers[$n] ?? $this->answers[(string) $n] ?? null;

        return EligibilityRules::QUESTIONS[$n][1][$key] ?? '—';
    }
}
