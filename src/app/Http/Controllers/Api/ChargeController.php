<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChargeRequest;
use App\Models\Charge;
use App\Services\ChargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChargeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    Charge::STATUS_OPEN,
                    Charge::STATUS_PAID,
                ]),
            ],
            'payment_method' => [
                'nullable',
                Rule::in([
                    Charge::METHOD_BOLETO,
                    Charge::METHOD_CARD,
                    Charge::METHOD_PIX,
                ]),
            ],
            'client_id' => [
                'nullable',
                'integer',
                'exists:clients,id',
            ],
        ]);

        $today = now()->toDateString();

        $charges = Charge::query()
            ->with([
                'contract.client',
                'detail',
            ])
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query
                    ->where('status', $status)
            )
            ->when(
                $filters['payment_method'] ?? null,
                fn ($query, $method) => $query
                    ->where('payment_method', $method)
            )
            ->when(
                $filters['client_id'] ?? null,
                fn ($query, $clientId) => $query
                    ->whereHas(
                        'contract',
                        fn ($contractQuery) => $contractQuery
                            ->where('client_id', $clientId)
                    )
            )
            ->orderByRaw(
                'CASE
                    WHEN status = ? AND due_date < ? THEN 0
                    WHEN status = ? THEN 1
                    ELSE 2
                END',
                [
                    Charge::STATUS_OPEN,
                    $today,
                    Charge::STATUS_OPEN,
                ]
            )
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return response()->json($charges);
    }

    public function store(
        StoreChargeRequest $request,
        ChargeService $service
    ): JsonResponse {
        $charge = $service->create(
            $request->validated()
        );

        return response()->json($charge, 201);
    }

    public function show(Charge $charge): JsonResponse
    {
        return response()->json(
            $charge->load([
                'contract.client',
                'detail',
            ])
        );
    }

    public function pay(
        Charge $charge,
        ChargeService $service
    ): JsonResponse {
        return response()->json(
            $service->markAsPaid($charge)
        );
    }
    
}