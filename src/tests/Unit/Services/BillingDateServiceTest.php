<?php

namespace Tests\Unit\Services;

use App\Services\BillingDateService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BillingDateServiceTest extends TestCase
{
    public function test_it_uses_the_requested_day_when_month_supports_it(): void
    {
        $service = new BillingDateService();

        $dueDate = $service->calculate(
            CarbonImmutable::parse('2026-01-01'),
            15
        );

        $this->assertSame('2026-01-15', $dueDate->toDateString());
    }

    public function test_it_uses_last_day_for_shorter_month(): void
    {
        $service = new BillingDateService();

        $dueDate = $service->calculate(
            CarbonImmutable::parse('2026-02-01'),
            31
        );

        $this->assertSame('2026-02-28', $dueDate->toDateString());
    }

    public function test_it_respects_leap_year(): void
    {
        $service = new BillingDateService();

        $dueDate = $service->calculate(
            CarbonImmutable::parse('2024-02-01'),
            31
        );

        $this->assertSame('2024-02-29', $dueDate->toDateString());
    }

    public function test_it_rejects_invalid_billing_day(): void
    {
        $service = new BillingDateService();

        $this->expectException(InvalidArgumentException::class);

        $service->calculate(
            CarbonImmutable::parse('2026-01-01'),
            32
        );
    }
}