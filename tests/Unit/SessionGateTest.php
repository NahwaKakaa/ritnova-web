<?php

namespace Tests\Unit;

use App\Domain\Sessions\CompletionRule;
use App\Domain\Sessions\GateStatus;
use App\Domain\Sessions\ScheduledSession;
use App\Domain\Sessions\SessionGate;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class SessionGateTest extends TestCase
{
    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('Asia/Makassar'));
    }

    /** @return list<ScheduledSession> */
    private function twoSessions(bool $firstCompleted = false): array
    {
        return [
            new ScheduledSession(1, $this->at('2026-11-16 19:00'), $this->at('2026-11-18 18:59'), null, $firstCompleted),
            new ScheduledSession(2, $this->at('2026-11-18 19:00'), $this->at('2026-11-20 18:59'), 1, false),
        ];
    }

    public function test_g01_locked_before_release_time(): void
    {
        $d = (new SessionGate)->evaluateSequence($this->at('2026-11-16 18:00'), $this->twoSessions());

        $this->assertSame(GateStatus::LockedTime, $d[1]->status);
        $this->assertSame(3600, $d[1]->secondsUntilRelease);
    }

    public function test_g02_locked_when_previous_session_not_completed(): void
    {
        $d = (new SessionGate)->evaluateSequence($this->at('2026-11-18 20:00'), $this->twoSessions());

        $this->assertSame(GateStatus::Available, $d[1]->status);
        $this->assertSame(GateStatus::LockedPrerequisite, $d[2]->status);
    }

    public function test_g03_available_when_time_reached_and_prerequisite_done(): void
    {
        $d = (new SessionGate)->evaluateSequence($this->at('2026-11-18 20:00'), $this->twoSessions(firstCompleted: true));

        $this->assertSame(GateStatus::Completed, $d[1]->status);
        $this->assertTrue($d[2]->canStart());
    }

    public function test_g09_strict_expired_session_does_not_block_next_forever(): void
    {
        $d = (new SessionGate(SessionGate::POLICY_STRICT))->evaluateSequence($this->at('2026-11-19 20:00'), $this->twoSessions());

        $this->assertSame(GateStatus::Expired, $d[1]->status);
        $this->assertSame(GateStatus::Available, $d[2]->status);
    }

    public function test_g09_catch_up_requires_sequential_and_marks_late(): void
    {
        $d = (new SessionGate(SessionGate::POLICY_CATCH_UP))->evaluateSequence($this->at('2026-11-19 20:00'), $this->twoSessions());

        $this->assertSame(GateStatus::Available, $d[1]->status);
        $this->assertTrue($d[1]->isLate);
        $this->assertSame(GateStatus::LockedPrerequisite, $d[2]->status);
    }

    public function test_g05_completion_requires_all_activities(): void
    {
        $rule = new CompletionRule(0.95);

        $this->assertSame(['media', 'quiz', 'action'], $rule->missing(0.5, false, false));
        $this->assertFalse($rule->canComplete(0.96, true, false));
        $this->assertTrue($rule->canComplete(0.96, true, true));
    }
}
