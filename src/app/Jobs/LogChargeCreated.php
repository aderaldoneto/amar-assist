<?php

namespace App\Jobs;

use App\Models\Charge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogChargeCreated implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Charge $charge
    ) {
    }

    public function handle(): void
    {
        $charge = $this->charge->loadMissing(
            'contract.client'
        );

        Log::info('Charge processed by queue.', [
            'charge_id' => $charge->id,
            'client_id' => $charge->contract->client_id,
            'client_name' => $charge->contract->client->name,
            'amount' => $charge->amount,
            'payment_method' => $charge->payment_method,
        ]);
    }
}