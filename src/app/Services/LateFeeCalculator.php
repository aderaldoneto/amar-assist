<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class LateFeeCalculator
{
    public function calculate(
        string $amount,
        CarbonInterface $dueDate,
        CarbonInterface $currentDate
    ): string {
        if (! is_numeric($amount) || bccomp($amount, '0', 2) < 0) {
            throw new InvalidArgumentException(
                'Amount must be a non-negative number.'
            );
        }

        $due = CarbonImmutable::instance($dueDate)->startOfDay();
        $current = CarbonImmutable::instance($currentDate)->startOfDay();

        if ($current->lessThanOrEqualTo($due)) {
            return '0.00';
        }

        $lateDays = $due->diffInDays($current);

        // Multa simples: valor original × dias em atraso ÷ 100.
        $unroundedFee = bcmul($amount, (string) $lateDays, 4);

        // Adiciona meio centavo antes da divisão para arredondar.
        $roundedFee = bcadd($unroundedFee, '0.50', 4);

        return bcdiv($roundedFee, '100', 2);
    }
}