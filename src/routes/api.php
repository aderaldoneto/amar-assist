<?php

use App\Http\Controllers\Api\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContractController;

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