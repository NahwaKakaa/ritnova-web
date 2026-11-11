<?php

namespace App\Domain\Sessions;

use DateTimeImmutable;

/**
 * Data satu sesi dalam jadwal kohort, dilihat dari sudut satu peserta.
 */
final readonly class ScheduledSession
{
    public function __construct(
        public int|string $id,
        public DateTimeImmutable $releaseAt,
        public ?DateTimeImmutable $closeAt,
        public int|string|null $prerequisiteId,
        public bool $completed,
    ) {}
}
