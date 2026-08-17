<?php

use App\Http\Controllers\Api\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ChargeController;

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