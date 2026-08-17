<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:18'],
            'status' => [
                'nullable',
                Rule::in([
                    Client::STATUS_ACTIVE,
                    Client::STATUS_INACTIVE,
                ]),
            ],
        ]);

        $document = isset($filters['document'])
            ? preg_replace('/\D/', '', $filters['document'])
            : null;

        $clients = Client::query()
            ->withCount('contracts')
            ->when(
                $filters['name'] ?? null,
                fn ($query, $name) => $query
                    ->where('name', 'like', "%{$name}%")
            )
            ->when(
                $document,
                fn ($query, $document) => $query
                    ->where('document', 'like', "%{$document}%")
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query
                    ->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return response()->json($clients);
    }

    public function store(
        StoreClientRequest $request
    ): JsonResponse {
        $client = Client::create($request->validated());

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(
            $client->loadCount('contracts')
        );
    }

    public function update(
        UpdateClientRequest $request,
        Client $client,
        ClientService $service
    ): JsonResponse {
        $data = $request->validated();

        try {
            if (
                ($data['status'] ?? null) === Client::STATUS_INACTIVE
                && $client->status !== Client::STATUS_INACTIVE
            ) {
                $client = $service->deactivate($client);

                unset($data['status']);
            }

            $client->update($data);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json(
            $client->refresh()->loadCount('contracts')
        );
    }
}