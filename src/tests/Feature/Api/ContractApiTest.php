<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ContractApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            User::factory()->create()
        );
    }

    public function test_it_creates_pf_contract(): void
    {
        $client = $this->createClient(
            '12345678901'
        );

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'type' => 'pf',
            'billing_day' => 31,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('type', Contract::TYPE_PF)
            ->assertJsonPath('billing_day', 31);

        $this->assertDatabaseHas('contracts', [
            'client_id' => $client->id,
            'type' => Contract::TYPE_PF,
            'billing_day' => 31,
        ]);
    }

    public function test_it_rejects_type_incompatible_with_document(): void
    {
        $client = $this->createClient(
            '12345678901'
        );

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'type' => Contract::TYPE_PJ,
            'billing_day' => 10,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_it_rejects_invalid_billing_day(): void
    {
        $client = $this->createClient(
            '12345678000190'
        );

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'type' => Contract::TYPE_PJ,
            'billing_day' => 32,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('billing_day');
    }

    private function createClient(string $document): Client
    {
        return Client::create([
            'name' => 'Cliente do contrato',
            'document' => $document,
            'address' => 'Rua do Contrato, 100',
            'contact' => 'contrato@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);
    }
}