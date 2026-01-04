<?php

use App\Http\Controllers\Api\Wallet\DepositController;
use Illuminate\Support\Facades\Route;

Route::prefix('deposit')

    ->group(function () {
        Route::get('/', [DepositController::class, 'index']);
        Route::get('/change/{currencyId}', [DepositController::class, 'change'])->name('change');
        Route::get('/options', [DepositController::class, 'getOptions'])->name('wallet.deposit.options');
        Route::get('/methods', [DepositController::class, 'getMethods'])->name('wallet.deposit.methods');
        Route::get('/has-deposits', [DepositController::class, 'hasDeposits'])->name('wallet.deposit.has-deposits');
        Route::post('/store', [DepositController::class, 'storePayment'])->name('wallet.deposit.store');
        Route::post('/payment', [DepositController::class, 'submitPayment']);
    });
