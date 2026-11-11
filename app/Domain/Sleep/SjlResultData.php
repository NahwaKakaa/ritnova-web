<?php

namespace App\Domain\Sleep;

/**
 * Hasil kalkulasi SJL. Bila data belum cukup, sjlMinutes = null (BUKAN 0).
 */
final readonly class SjlResultData
{
    public const STATUS_OK = 'ok';
    public const STATUS_INSUFFICIENT = 'insufficient';

    /**
     * @param  array<int, array{reference: mixed, reason: string}>  $rejected
     */
    public function __construct(
        public string $status,
        public int $validWorkdays,
        public int $validFreeDays,
        public ?float $mswHours,
        public ?float $msfHours,
        public ?int $sjlMinutes,
        public string $algorithmVersion,
        public array $rejected = [],
    ) {}

    public function isSufficient(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /** Format jam:menit untuk tampilan, contoh 2j 30m. */
    public function sjlLabel(): ?string
    {
        if ($this->sjlMinutes === null) {
            return null;
        }

        return intdiv($this->sjlMinutes, 60).'j '.($this->sjlMinutes % 60).'m';
    }

    public static function clockLabel(?float $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        $minutes = (int) round($hours * 60);
        $minutes = (($minutes % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
