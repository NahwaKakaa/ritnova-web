<?php

namespace Tests\Unit;

use App\Domain\Sleep\SjlResultData;
use App\Domain\Sleep\SleepEntry;
use App\Domain\Sleep\SocialJetlagCalculator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class SocialJetlagCalculatorTest extends TestCase
{
    private function at(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('Asia/Makassar'));
    }

    public function test_u03_cross_midnight_duration_and_midpoint(): void
    {
        $c = new SocialJetlagCalculator;

        $this->assertSame(420, $c->durationMinutes($this->at('2026-11-10 23:30'), $this->at('2026-11-11 06:30')));
        $this->assertSame('03:00', $c->midpoint($this->at('2026-11-10 23:30'), $this->at('2026-11-11 06:30'))->format('H:i'));
    }

    public function test_u04_sjl_is_two_and_a_half_hours(): void
    {
        $result = (new SocialJetlagCalculator)->calculate([
            new SleepEntry($this->at('2026-11-09 23:30'), $this->at('2026-11-10 06:30'), 'workday'),
            new SleepEntry($this->at('2026-11-10 23:30'), $this->at('2026-11-11 06:30'), 'workday'),
            new SleepEntry($this->at('2026-11-14 01:30'), $this->at('2026-11-14 09:30'), 'free_day'),
        ]);

        $this->assertTrue($result->isSufficient());
        $this->assertSame(150, $result->sjlMinutes);
        $this->assertSame('2j 30m', $result->sjlLabel());
        $this->assertSame('03:00', SjlResultData::clockLabel($result->mswHours));
        $this->assertSame('05:30', SjlResultData::clockLabel($result->msfHours));
    }

    public function test_u05_missing_data_is_insufficient_not_zero(): void
    {
        $result = (new SocialJetlagCalculator)->calculate([
            new SleepEntry($this->at('2026-11-09 23:30'), $this->at('2026-11-10 06:30'), 'workday'),
        ]);

        $this->assertFalse($result->isSufficient());
        $this->assertNull($result->sjlMinutes);
    }

    public function test_incomplete_and_implausible_entries_are_rejected(): void
    {
        $result = (new SocialJetlagCalculator)->calculate([
            new SleepEntry($this->at('2026-11-09 23:30'), null, 'workday', reference: 1),
            new SleepEntry($this->at('2026-11-09 23:30'), $this->at('2026-11-09 23:40'), 'workday', reference: 2),
            new SleepEntry($this->at('2026-11-09 23:30'), $this->at('2026-11-10 06:30'), null, reference: 3),
        ]);

        $this->assertSame(['incomplete', 'implausible_duration', 'unclassified_day'], array_column($result->rejected, 'reason'));
    }

    public function test_midpoints_around_midnight_are_averaged_correctly(): void
    {
        $result = (new SocialJetlagCalculator)->calculate([
            new SleepEntry($this->at('2026-11-09 20:00'), $this->at('2026-11-10 04:00'), 'workday'), // midpoint 00:00
            new SleepEntry($this->at('2026-11-10 19:00'), $this->at('2026-11-11 05:00'), 'workday'), // midpoint 00:00
            new SleepEntry($this->at('2026-11-13 22:00'), $this->at('2026-11-14 06:00'), 'free_day'), // midpoint 02:00
        ]);

        $this->assertSame(120, $result->sjlMinutes);
    }
}
