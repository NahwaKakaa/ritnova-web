<?php

namespace Tests\Unit;

use App\Domain\Assessment\ScoreCalculator;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    private function items(): array
    {
        return [
            ['order' => 1, 'numeric' => true, 'reverse' => false, 'min' => 1, 'max' => 5],
            ['order' => 2, 'numeric' => true, 'reverse' => true, 'min' => 1, 'max' => 5],
            ['order' => 3, 'numeric' => true, 'reverse' => false, 'min' => 1, 'max' => 5],
            ['order' => 4, 'numeric' => false, 'reverse' => false, 'min' => null, 'max' => null], // teks
        ];
    }

    public function test_sum_with_reverse_item(): void
    {
        // 4 + (1+5-2=4) + 3 = 11; butir teks tidak dihitung
        $this->assertSame(11.0, ScoreCalculator::score(['method' => 'sum'], $this->items(), [1 => '4', 2 => '2', 3 => '3', 4 => 'catatan']));
    }

    public function test_mean_of_selected_items(): void
    {
        $this->assertSame(3.5, ScoreCalculator::score(['method' => 'mean', 'items' => [1, 3]], $this->items(), [1 => 4, 2 => 1, 3 => 3]));
    }

    public function test_missing_answer_gives_null_not_zero(): void
    {
        $this->assertNull(ScoreCalculator::score(['method' => 'sum'], $this->items(), [1 => '4', 2 => '', 3 => '3']));
        $this->assertNull(ScoreCalculator::score(['method' => 'sum'], $this->items(), [1 => '4', 3 => '3']));
    }

    public function test_no_rule_or_no_numeric_items_gives_null(): void
    {
        $this->assertNull(ScoreCalculator::score(null, $this->items(), [1 => 4, 2 => 2, 3 => 3]));
        $this->assertNull(ScoreCalculator::score(['method' => 'none'], $this->items(), [1 => 4, 2 => 2, 3 => 3]));
        $this->assertNull(ScoreCalculator::score(['method' => 'sum', 'items' => [4]], $this->items(), [4 => 'x']));
    }

    public function test_reverse_without_range_gives_null(): void
    {
        $items = [['order' => 1, 'numeric' => true, 'reverse' => true, 'min' => null, 'max' => null]];
        $this->assertNull(ScoreCalculator::score(['method' => 'sum'], $items, [1 => 3]));
    }
}
