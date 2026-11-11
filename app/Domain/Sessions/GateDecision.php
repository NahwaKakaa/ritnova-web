<?php

namespace App\Domain\Sessions;

use DateTimeImmutable;

final readonly class GateDecision
{
    public function __construct(
        public GateStatus $status,
        public DateTimeImmutable $releaseAt,
        public ?DateTimeImmutable $closeAt,
        public bool $timeReached,
        public bool $prerequisiteMet,
        public bool $isLate,
        public ?int $secondsUntilRelease,
    ) {}

    public function canStart(): bool
    {
        return $this->status->canStart();
    }
}
