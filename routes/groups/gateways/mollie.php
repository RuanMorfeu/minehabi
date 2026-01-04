<?php

use App\Http\Controllers\Api\Gateways\MollieController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mollie Gateway Routes
|--------------------------------------------------------------------------
|
| Rotas para integração com o gateway de pagamento Mollie
|
*/

// Rotas da API (protegidas por autenticação JWT)
Route::group(['middleware' => 'auth.jwt'], function () {
    Route::post('create-payment', [MollieController::class, 'createPayment']);
    Route::post('create-payment-token', [MollieController::class, 'createPaymentWithToken']);
    Route::get('check-status', [MollieController::class, 'checkStatus']);
    Route::get('payment-methods', [MollieController::class, 'getPaymentMethods']);
    Route::get('saved-cards', [MollieController::class, 'getSavedCards']);
    Route::post('create-payment-with-saved-card', [MollieController::class, 'createPaymentWithSavedCard']);
    Route::delete('delete-saved-card', [MollieController::class, 'deleteSavedCard']);
});

// Rotas públicas (sem autenticação)
Route::get('config', [MollieController::class, 'getConfig']);

// Rotas públicas (webhooks e retornos)
Route::post('webhook', [MollieController::class, 'webhook']);
Route::get('return/{paymentId}', [MollieController::class, 'returnUrl']);
