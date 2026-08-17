<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ClientApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            User::factory()->create()
        );
    }

    public function test_it_creates_client_and_normalizes_document(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Maria da Silva',
            'document' => '123.456.789-01',
            'address' => 'Rua Principal, 100',
            'contact' => 'maria@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Maria da Silva')
            ->assertJsonPath('document', '12345678901')
            ->assertJsonPath('status', Client::STATUS_ACTIVE);

        $this->assertDatabaseHas('clients', [
            'document' => '12345678901',
        ]);
    }

    public function test_it_filters_clients(): void
    {
        Client::create([
            'name' => 'Maria Ativa',
            'document' => '11111111111',
            'address' => 'Rua A',
            'contact' => 'maria@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        Client::create([
            'name' => 'Maria Inativa',
            'document' => '22222222222',
            'address' => 'Rua B',
            'contact' => 'maria2@example.com',
            'status' => Client::STATUS_INACTIVE,
        ]);

        Client::create([
            'name' => 'João Ativo',
            'document' => '33333333333',
            'address' => 'Rua C',
            'contact' => 'joao@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $response = $this->getJson(
            '/api/clients?name=Maria&status=active&document=111'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Maria Ativa');
    }

    public function test_it_rejects_duplicate_document(): void
    {
        Client::create([
            'name' => 'Cliente existente',
            'document' => '44444444444',
            'address' => 'Rua D',
            'contact' => 'existente@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/clients', [
            'name' => 'Cliente duplicado',
            'document' => '444.444.444-44',
            'address' => 'Rua E',
            'contact' => 'duplicado@example.com',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');
    }

    public function test_it_blocks_deactivation_when_client_has_contract(): void
    {
        $client = Client::create([
            'name' => 'Cliente contratado',
            'document' => '55555555555',
            'address' => 'Rua F',
            'contact' => 'contratado@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $client->contracts()->create([
            'type' => Contract::TYPE_PF,
            'billing_day' => 10,
        ]);

        $response = $this->patchJson(
            "/api/clients/{$client->id}",
            ['status' => Client::STATUS_INACTIVE]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Client with contracts cannot be deactivated.'
            );

        $this->assertSame(
            Client::STATUS_ACTIVE,
            $client->fresh()->status
        );
    }
}