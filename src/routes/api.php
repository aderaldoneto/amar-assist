<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChargeController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContractController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [AuthController::class, 'me'])
        ->name('me');

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::apiResource('clients', ClientController::class)
        ->only([
            'index',
            'store',
            'show',
            'update',
        ]);

    Route::apiResource('contracts', ContractController::class)
        ->only([
            'index',
            'store',
            'show',
        ]);

    Route::apiResource('charges', ChargeController::class)
        ->only([
            'index',
            'store',
            'show',
        ]);

    Route::patch(
        'charges/{charge}/pay',
        [ChargeController::class, 'pay']
    )->name('charges.pay');
});