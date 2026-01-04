<?php

use App\Http\Controllers\Api\Profile\WalletController;
use App\Http\Controllers\Api\Wallet\WithdrawController;
use Illuminate\Support\Facades\Route;

// Rota para verificar se o usuário já fez depósitos
Route::get('/check-deposits', [WalletController::class, 'checkUserDeposits']);

Route::prefix('withdraw')
    ->group(function () {
        Route::get('/', [WithdrawController::class, 'index']);
        Route::post('/request', [WalletController::class, 'requestWithdrawal']);
    });
