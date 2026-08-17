<?php

namespace App\Services;

use App\Models\Client;
use DomainException;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function deactivate(Client $client): Client
    {
        return DB::transaction(function () use ($client): Client {
            $lockedClient = Client::query()
                ->lockForUpdate()
                ->findOrFail($client->getKey());

            if ($lockedClient->contracts()->exists()) {
                throw new DomainException(
                    'Client with contracts cannot be deactivated.'
                );
            }

            $lockedClient->update([
                'status' => Client::STATUS_INACTIVE,
            ]);

            return $lockedClient->refresh();
        });
    }
}