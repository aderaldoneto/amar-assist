<?php

namespace Tests\Feature\Models;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class BillingRelationshipsTest extends TestCase
{
    public function test_client_can_have_contracts(): void
    {
        $client = Client::create([
            'name' => 'Cliente Teste',
            'document' => '12345678901',
            'address' => 'Rua de Teste, 100',
            'contact' => 'teste@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $contract = $client->contracts()->create([
            'type' => Contract::TYPE_PF,
            'billing_day' => 10,
        ]);

        $this->assertTrue($contract->client->is($client));
        $this->assertTrue($client->contracts->contains($contract));
    }

    public function test_contract_can_have_charge_with_detail(): void
    {
        $client = Client::create([
            'name' => 'Empresa Teste',
            'document' => '12345678000190',
            'address' => 'Avenida de Teste, 200',
            'contact' => 'empresa@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $contract = $client->contracts()->create([
            'type' => Contract::TYPE_PJ,
            'billing_day' => 31,
        ]);

        $charge = $contract->charges()->create([
            'payment_method' => Charge::METHOD_PIX,
            'amount' => 100,
            'penalty_amount' => 0,
            'due_date' => '2026-08-31',
            'status' => Charge::STATUS_OPEN,
        ]);

        $detail = $charge->detail()->create([
            'pix_key' => 'financeiro@example.com',
        ]);

        $this->assertTrue($charge->contract->is($contract));
        $this->assertTrue($charge->detail->is($detail));
        $this->assertTrue($detail->charge->is($charge));
    }

    public function test_client_with_contract_cannot_be_deleted(): void
    {
        $client = Client::create([
            'name' => 'Cliente Protegido',
            'document' => '98765432100',
            'address' => 'Rua Protegida, 300',
            'contact' => 'protegido@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $client->contracts()->create([
            'type' => Contract::TYPE_PF,
            'billing_day' => 15,
        ]);

        $this->expectException(QueryException::class);

        $client->delete();
    }
}
