<?php

namespace Database\Seeders;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Contract;
use App\Services\LateFeeCalculator;
use Illuminate\Database\Seeder;

class DemoBillingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Client::updateOrCreate(
            ['document' => '12345678000190'],
            [
                'name' => 'Empresa Exemplo Ltda.',
                'address' => 'Avenida Principal, 1000',
                'contact' => 'financeiro@exemplo.test',
                'status' => Client::STATUS_ACTIVE,
            ]
        );

        $person = Client::updateOrCreate(
            ['document' => '12345678901'],
            [
                'name' => 'Maria da Silva',
                'address' => 'Rua das Flores, 120',
                'contact' => 'maria@exemplo.test',
                'status' => Client::STATUS_ACTIVE,
            ]
        );

        Client::updateOrCreate(
            ['document' => '98765432100'],
            [
                'name' => 'João sem contrato',
                'address' => 'Rua Secundária, 50',
                'contact' => 'joao@exemplo.test',
                'status' => Client::STATUS_INACTIVE,
            ]
        );

        $companyContract = Contract::updateOrCreate(
            [
                'client_id' => $company->id,
                'type' => Contract::TYPE_PJ,
            ],
            ['billing_day' => 31]
        );

        $personContract = Contract::updateOrCreate(
            [
                'client_id' => $person->id,
                'type' => Contract::TYPE_PF,
            ],
            ['billing_day' => 10]
        );

        $calculator = app(LateFeeCalculator::class);

        $overdueDate = today()->subDays(5);

        $overdue = Charge::updateOrCreate(
            [
                'contract_id' => $companyContract->id,
                'due_date' => $overdueDate,
                'payment_method' => Charge::METHOD_PIX,
            ],
            [
                'amount' => '250.00',
                'penalty_amount' => $calculator->calculate(
                    '250.00',
                    $overdueDate,
                    today()
                ),
                'status' => Charge::STATUS_OPEN,
                'paid_at' => null,
            ]
        );

        $overdue->detail()->updateOrCreate(
            ['charge_id' => $overdue->id],
            ['pix_key' => 'financeiro@exemplo.test']
        );

        $future = Charge::updateOrCreate(
            [
                'contract_id' => $personContract->id,
                'due_date' => today()->addDays(10),
                'payment_method' => Charge::METHOD_BOLETO,
            ],
            [
                'amount' => '120.00',
                'penalty_amount' => '0.00',
                'status' => Charge::STATUS_OPEN,
                'paid_at' => null,
            ]
        );

        $future->detail()->updateOrCreate(
            ['charge_id' => $future->id],
            ['barcode' => '00190500954014481606906809350314337370000000100']
        );

        $paid = Charge::updateOrCreate(
            [
                'contract_id' => $companyContract->id,
                'due_date' => today()->subMonth(),
                'payment_method' => Charge::METHOD_CARD,
            ],
            [
                'amount' => '180.00',
                'penalty_amount' => '0.00',
                'status' => Charge::STATUS_PAID,
                'paid_at' => now()->subMonth(),
            ]
        );

        $paid->detail()->updateOrCreate(
            ['charge_id' => $paid->id],
            [
                'card_holder_name' => 'EMPRESA EXEMPLO',
                'card_brand' => 'Visa',
                'card_last_four' => '4242',
            ]
        );
    }
}