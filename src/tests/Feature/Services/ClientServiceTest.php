<?php

namespace Tests\Feature\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Services\ClientService;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_client_without_contract_can_be_deactivated(): void
    {
        $client = Client::create([
            'name' => 'Cliente sem contrato',
            'document' => '11122233344',
            'address' => 'Rua A, 10',
            'contact' => 'cliente@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $service = new ClientService();

        $service->deactivate($client);

        $this->assertSame(
            Client::STATUS_INACTIVE,
            $client->fresh()->status
        );
    }

    public function test_client_with_contract_cannot_be_deactivated(): void
    {
        $client = Client::create([
            'name' => 'Cliente com contrato',
            'document' => '11222333000181',
            'address' => 'Rua B, 20',
            'contact' => 'empresa@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $client->contracts()->create([
            'type' => Contract::TYPE_PJ,
            'billing_day' => 10,
        ]);

        $service = new ClientService();

        try {
            $service->deactivate($client);

            $this->fail('A desativação deveria ter sido bloqueada.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Client with contracts cannot be deactivated.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            Client::STATUS_ACTIVE,
            $client->fresh()->status
        );
    }
}