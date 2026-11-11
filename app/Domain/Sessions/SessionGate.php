<?php

namespace App\Domain\Sessions;

use DateTimeImmutable;

/**
 * Aturan penguncian sesi. Selalu dievaluasi di SERVER.
 *
 * Syarat A (waktu)  : now >= release_at
 * Syarat B (urutan) : sesi prasyarat sudah selesai
 * Keduanya terpenuhi -> tombol "Mulai" aktif.
 *
 * Kebijakan keterlambatan:
 *  - strict   : lewat close_at dan belum selesai -> Kedaluwarsa. Sesi kedaluwarsa
 *               dianggap memenuhi prasyarat sesi berikutnya, supaya peserta tidak
 *               terkunci selamanya.
 *  - catch_up : sesi tetap bisa dikerjakan berurutan; lewat close_at ditandai terlambat.
 */
final class SessionGate
{
    public const POLICY_STRICT = 'strict';
    public const POLICY_CATCH_UP = 'catch_up';

    public function __construct(private string $policy = self::POLICY_CATCH_UP) {}

    public function evaluate(
        DateTimeImmutable $now,
        DateTimeImmutable $releaseAt,
        ?DateTimeImmutable $closeAt,
        bool $completed,
        ?GateDecision $prerequisite,
    ): GateDecision {
        $timeReached = $now >= $releaseAt;
        $pastClose = $closeAt !== null && $now > $closeAt;
        $prerequisiteMet = $this->prerequisiteMet($prerequisite);
        $secondsUntil = $timeReached ? null : $releaseAt->getTimestamp() - $now->getTimestamp();

        $status = match (true) {
            $completed => GateStatus::Completed,
            $this->policy === self::POLICY_STRICT && $pastClose => GateStatus::Expired,
            ! $timeReached => GateStatus::LockedTime,
            ! $prerequisiteMet => GateStatus::LockedPrerequisite,
            default => GateStatus::Available,
        };

        return new GateDecision(
            status: $status,
            releaseAt: $releaseAt,
            closeAt: $closeAt,
            timeReached: $timeReached,
            prerequisiteMet: $prerequisiteMet,
            isLate: $pastClose && $this->policy === self::POLICY_CATCH_UP && $status !== GateStatus::Completed,
            secondsUntilRelease: $secondsUntil,
        );
    }

    /**
     * Evaluasi seluruh jadwal secara berurutan sehingga status prasyarat ikut terhitung.
     *
     * @param  list<ScheduledSession>  $sessions  diurutkan dari sesi 1 ke 12
     * @return array<int|string, GateDecision>  dikunci dengan id sesi
     */
    public function evaluateSequence(DateTimeImmutable $now, array $sessions): array
    {
        $decisions = [];

        foreach ($sessions as $session) {
            $prerequisite = $session->prerequisiteId === null
                ? null
                : ($decisions[$session->prerequisiteId] ?? null);

            $decisions[$session->id] = $this->evaluate(
                $now,
                $session->releaseAt,
                $session->closeAt,
                $session->completed,
                $prerequisite,
            );
        }

        return $decisions;
    }

    private function prerequisiteMet(?GateDecision $prerequisite): bool
    {
        if ($prerequisite === null) {
            return true;
        }

        return $prerequisite->status === GateStatus::Completed
            || ($this->policy === self::POLICY_STRICT && $prerequisite->status === GateStatus::Expired);
    }
}
