<?php

use App\Http\Controllers\Api\Wallet\DepositController;
use Illuminate\Support\Facades\Route;

Route::prefix('sibs')
    ->group(function () {
        Route::post('callback', [DepositController::class, 'callbackSibs']);
    });
