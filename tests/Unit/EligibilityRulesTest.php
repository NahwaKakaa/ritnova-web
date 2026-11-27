<?php

namespace Tests\Unit;

use App\Domain\Screening\EligibilityRules;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EligibilityRulesTest extends TestCase
{
    private const SAFE = [1 => 'ya', 2 => 'ya', 3 => 'ya', 4 => 'ya', 5 => 'ya', 6 => 'tidak', 7 => 'tidak', 8 => 'tidak'];

    public function test_all_safe_answers_are_eligible(): void
    {
        $r = EligibilityRules::evaluate(self::SAFE, 19);

        $this->assertSame(EligibilityRules::ELIGIBLE, $r['result']);
        $this->assertSame([], $r['ineligible']);
        $this->assertSame([], $r['clarify']);
        $this->assertFalse($r['contact']);
        $this->assertFalse($r['minor']);
    }

    public function test_no_on_required_items_is_ineligible_even_with_clarify_items(): void
    {
        $r = EligibilityRules::evaluate([2 => 'tidak', 5 => 'tidak', 6 => 'ya'] + self::SAFE, 19);

        $this->assertSame(EligibilityRules::INELIGIBLE, $r['result']);
        $this->assertSame([2, 5], $r['ineligible']);
        $this->assertSame([6], $r['clarify']);
    }

    public function test_uncertain_day_types_shift_or_travel_need_clarification(): void
    {
        $r = EligibilityRules::evaluate([3 => 'belum_pasti', 7 => 'tidak_pasti'] + self::SAFE, 19);

        $this->assertSame(EligibilityRules::CLARIFY, $r['result']);
        $this->assertSame([3, 7], $r['clarify']);
    }

    public function test_contact_request_and_minor_are_flags_only(): void
    {
        $r = EligibilityRules::evaluate([8 => 'ya_hubungi'] + self::SAFE, 17);

        $this->assertSame(EligibilityRules::ELIGIBLE, $r['result']);
        $this->assertTrue($r['contact']);
        $this->assertTrue($r['minor']);
    }

    public function test_age_counts_full_years(): void
    {
        $birth = new DateTimeImmutable('2008-11-28');

        $this->assertSame(17, EligibilityRules::age($birth, new DateTimeImmutable('2026-11-27')));
        $this->assertSame(18, EligibilityRules::age($birth, new DateTimeImmutable('2026-11-28')));
    }
}
