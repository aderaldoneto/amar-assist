<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Contract;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ChargeService
{
    public function __construct(
        private BillingDateService $billingDateService,
        private LateFeeCalculator $lateFeeCalculator
    ) {
    }

    public function create(
        array $data,
        ?CarbonInterface $generatedAt = null
    ): Charge {
        return DB::transaction(function () use (
            $data,
            $generatedAt
        ): Charge {
            $contract = Contract::findOrFail(
                $data['contract_id']
            );

            $referenceDate = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $data['reference_month'].'-01'
            )->startOfDay();

            $dueDate = $this->billingDateService->calculate(
                $referenceDate,
                $contract->billing_day
            );

            $currentDate = $generatedAt
                ? CarbonImmutable::instance($generatedAt)
                : CarbonImmutable::now();

            $amount = (string) $data['amount'];

            $penaltyAmount = $this->lateFeeCalculator->calculate(
                $amount,
                $dueDate,
                $currentDate
            );

            $charge = $contract->charges()->create([
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'penalty_amount' => $penaltyAmount,
                'due_date' => $dueDate,
                'status' => Charge::STATUS_OPEN,
            ]);

            $detailData = match ($data['payment_method']) {
                Charge::METHOD_BOLETO => [
                    'barcode' => $data['barcode'],
                ],
                Charge::METHOD_PIX => [
                    'pix_key' => $data['pix_key'],
                ],
                Charge::METHOD_CARD => [
                    'card_holder_name' => $data['card_holder_name'],
                    'card_brand' => $data['card_brand'],
                    'card_last_four' => $data['card_last_four'],
                ],
            };

            $charge->detail()->create($detailData);

            return $charge->load([
                'contract.client',
                'detail',
            ]);
        });
    }

    public function markAsPaid(
        Charge $charge,
        ?CarbonInterface $paidAt = null
    ): Charge {
        return DB::transaction(function () use (
            $charge,
            $paidAt
        ): Charge {
            $lockedCharge = Charge::query()
                ->lockForUpdate()
                ->findOrFail($charge->getKey());

            if ($lockedCharge->status === Charge::STATUS_PAID) {
                return $lockedCharge->load([
                    'contract.client',
                    'detail',
                ]);
            }

            $lockedCharge->update([
                'status' => Charge::STATUS_PAID,
                'paid_at' => $paidAt
                    ? CarbonImmutable::instance($paidAt)
                    : CarbonImmutable::now(),
            ]);

            return $lockedCharge
                ->refresh()
                ->load([
                    'contract.client',
                    'detail',
                ]);
        });
    }

}