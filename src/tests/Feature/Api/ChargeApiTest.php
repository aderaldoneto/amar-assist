<?php

namespace Tests\Feature\Api;

use App\Models\Charge;
use App\Models\Client;
use App\Models\Contract;
use Carbon\CarbonImmutable;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Jobs\LogChargeCreated;
use Illuminate\Support\Facades\Queue;

class ChargeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            User::factory()->create()
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
    
    public function test_it_creates_overdue_pix_charge_with_fee(): void
    {
        Queue::fake();

        CarbonImmutable::setTestNow('2026-03-05 12:00:00');

        $contract = $this->createContract(31);

        $response = $this->postJson('/api/charges', [
            'contract_id' => $contract->id,
            'payment_method' => Charge::METHOD_PIX,
            'amount' => '100.00',
            'reference_month' => '2026-02',
            'pix_key' => 'financeiro@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('amount', '100.00')
            ->assertJsonPath('penalty_amount', '5.00')
            ->assertJsonPath('total_amount', '105.00')
            ->assertJsonPath('is_overdue', true)
            ->assertJsonPath(
                'detail.pix_key',
                'financeiro@example.com'
            );

        $this->assertDatabaseHas('charges', [
            'contract_id' => $contract->id,
            'penalty_amount' => '5.00',
            'status' => Charge::STATUS_OPEN,
        ]);

        Queue::assertPushed(
            LogChargeCreated::class,
            function (LogChargeCreated $job): bool {
                return $job->charge->amount === '100.00';
            }
        );

        $charge = Charge::query()
            ->where('contract_id', $contract->id)
            ->firstOrFail();

        $this->assertSame(
            '2026-02-28',
            $charge->due_date->toDateString()
        );
    }

    public function test_it_requires_detail_for_selected_method(): void
    {
        $contract = $this->createContract(10);

        $response = $this->postJson('/api/charges', [
            'contract_id' => $contract->id,
            'payment_method' => Charge::METHOD_BOLETO,
            'amount' => '100.00',
            'reference_month' => '2026-08',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('barcode');
    }

    public function test_it_orders_overdue_open_charges_first(): void
    {
        CarbonImmutable::setTestNow('2026-03-05 12:00:00');

        $contract = $this->createContract(10);

        $paid = $contract->charges()->create([
            'payment_method' => Charge::METHOD_PIX,
            'amount' => '100.00',
            'due_date' => '2026-02-01',
            'status' => Charge::STATUS_PAID,
            'paid_at' => '2026-02-01 10:00:00',
        ]);

        $future = $contract->charges()->create([
            'payment_method' => Charge::METHOD_PIX,
            'amount' => '100.00',
            'due_date' => '2026-03-20',
            'status' => Charge::STATUS_OPEN,
        ]);

        $overdue = $contract->charges()->create([
            'payment_method' => Charge::METHOD_PIX,
            'amount' => '100.00',
            'due_date' => '2026-03-01',
            'status' => Charge::STATUS_OPEN,
        ]);

        $response = $this->getJson('/api/charges');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $overdue->id)
            ->assertJsonPath('data.1.id', $future->id)
            ->assertJsonPath('data.2.id', $paid->id);
    }

    public function test_it_marks_open_charge_as_paid(): void
    {
        CarbonImmutable::setTestNow('2026-03-05 14:30:00');

        $contract = $this->createContract(10);

        $charge = $contract->charges()->create([
            'payment_method' => Charge::METHOD_PIX,
            'amount' => '100.00',
            'due_date' => '2026-03-10',
            'status' => Charge::STATUS_OPEN,
        ]);

        $response = $this->patchJson(
            "/api/charges/{$charge->id}/pay"
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', Charge::STATUS_PAID)
            ->assertJsonPath('is_overdue', false);

        $paidCharge = $charge->fresh();

        $this->assertSame(
            Charge::STATUS_PAID,
            $paidCharge->status
        );

        $this->assertNotNull($paidCharge->paid_at);

        $this->assertSame(
            '2026-03-05 14:30:00',
            $paidCharge->paid_at->format('Y-m-d H:i:s')
        );
    }

    private function createContract(int $billingDay): Contract
    {
        $client = Client::create([
            'name' => 'Cliente da cobrança',
            'document' => '12345678000190',
            'address' => 'Rua da Cobrança, 100',
            'contact' => 'cobranca@example.com',
            'status' => Client::STATUS_ACTIVE,
        ]);

        return $client->contracts()->create([
            'type' => Contract::TYPE_PJ,
            'billing_day' => $billingDay,
        ]);
    }
}
