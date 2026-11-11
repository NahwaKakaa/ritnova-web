<?php

namespace App\Domain\Sleep;

use DateTimeImmutable;

/**
 * Satu malam tidur dari diary. Waktu harus dalam zona waktu penelitian.
 */
final readonly class SleepEntry
{
    public const WORKDAY = 'workday';
    public const FREE_DAY = 'free_day';

    public function __construct(
        public ?DateTimeImmutable $onset,
        public ?DateTimeImmutable $offset,
        public ?string $dayType,
        public ?bool $usedAlarm = null,
        public mixed $reference = null, // id diary atau tanggal, untuk pelaporan error
    ) {}
}
