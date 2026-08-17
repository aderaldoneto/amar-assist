<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class BillingDateService
{
    public function calculate(
        CarbonInterface $referenceDate,
        int $billingDay
    ): CarbonImmutable {
        if ($billingDay < 1 || $billingDay > 31) {
            throw new InvalidArgumentException(
                'Billing day must be between 1 and 31.'
            );
        }

        $date = CarbonImmutable::instance($referenceDate);

        $validDay = min($billingDay, $date->daysInMonth);

        return $date
            ->day($validDay)
            ->startOfDay();
    }
}