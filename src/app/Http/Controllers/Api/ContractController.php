<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);

        $contracts = Contract::query()
            ->with('client')
            ->when(
                $filters['client_id'] ?? null,
                fn ($query, $clientId) => $query
                    ->where('client_id', $clientId)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json($contracts);
    }

    public function store(
        StoreContractRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $client = Client::findOrFail($data['client_id']);

        $expectedType = strlen($client->document) === 11
            ? Contract::TYPE_PF
            : Contract::TYPE_PJ;

        if ($data['type'] !== $expectedType) {
            return response()->json([
                'message' => 'Contract type does not match client document.',
                'errors' => [
                    'type' => [
                        "Expected contract type: {$expectedType}.",
                    ],
                ],
            ], 422);
        }

        $contract = $client->contracts()->create([
            'type' => $data['type'],
            'billing_day' => $data['billing_day'],
        ]);

        return response()->json(
            $contract->load('client'),
            201
        );
    }

    public function show(Contract $contract): JsonResponse
    {
        return response()->json(
            $contract->load(['client', 'charges'])
        );
    }
}