<?php

namespace Tests\Unit\Services;

use App\Services\LateFeeCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class LateFeeCalculatorTest extends TestCase
{
    public function test_it_returns_zero_before_due_date(): void
    {
        $calculator = new LateFeeCalculator();

        $fee = $calculator->calculate(
            '100.00',
            CarbonImmutable::parse('2026-08-20'),
            CarbonImmutable::parse('2026-08-19')
        );

        $this->assertSame('0.00', $fee);
    }

    public function test_it_returns_zero_on_due_date(): void
    {
        $calculator = new LateFeeCalculator();

        $fee = $calculator->calculate(
            '100.00',
            CarbonImmutable::parse('2026-08-20'),
            CarbonImmutable::parse('2026-08-20')
        );

        $this->assertSame('0.00', $fee);
    }

    public function test_it_adds_one_percent_for_each_late_day(): void
    {
        $calculator = new LateFeeCalculator();

        $fee = $calculator->calculate(
            '100.00',
            CarbonImmutable::parse('2026-08-20'),
            CarbonImmutable::parse('2026-08-25')
        );

        $this->assertSame('5.00', $fee);
    }

    public function test_it_rounds_the_fee_to_two_decimal_places(): void
    {
        $calculator = new LateFeeCalculator();

        $fee = $calculator->calculate(
            '99.99',
            CarbonImmutable::parse('2026-08-20'),
            CarbonImmutable::parse('2026-08-21')
        );

        $this->assertSame('1.00', $fee);
    }
}