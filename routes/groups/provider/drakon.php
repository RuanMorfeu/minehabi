<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

// Rotas para webhook Drakon
Route::prefix('drakon_api')->group(function () {
    Route::post('/', [GameController::class, 'webhookDrakonMethod'])->name('drakon.webhook');
    Route::post('/{method}', [GameController::class, 'webhookDrakonMethod'])->name('drakon.webhook.method');
});

// Rotas para jogos Drakon
Route::prefix('api/games/drakon')->middleware(['auth:api'])->group(function () {
    Route::get('/provider/{provider}', [GameController::class, 'getGamesByProvider'])->name('drakon.games.provider');
});
