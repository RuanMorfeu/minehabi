<?php

use App\Http\Controllers\Api\Games\GameController;
use Illuminate\Support\Facades\Route;

Route::any('callback', [GameController::class, 'webhookAggrTrait']);

Route::prefix('games')
    ->group(function () {
        Route::get('all', [GameController::class, 'index']);
        Route::get('single/{id}', [GameController::class, 'show']);
        Route::post('favorite/{id}', [GameController::class, 'toggleFavorite']);
        Route::post('like/{id}', [GameController::class, 'toggleLike']);
    });

Route::prefix('aggrGames')
    ->group(function () {
        Route::get('single_aggr/{id}', [GameController::class, 'show_aggr']);
        Route::any('games', [GameController::class, 'aggrGames']);
    });

Route::prefix('featured')
    ->group(function () {
        Route::any('/games', [GameController::class, 'featured']);
    });

Route::prefix('source')
    ->group(function () {
        Route::any('/games', [GameController::class, 'source']);
    });

Route::prefix('exclusive')
    ->group(function () {
        Route::any('/games', [GameController::class, 'exclusive']);
    });

Route::prefix('exclusive2')
    ->group(function () {
        Route::any('/games', [GameController::class, 'exclusive2']);
    });

Route::prefix('slots')
    ->group(function () {
        Route::any('/games', [GameController::class, 'slots']);
    });

Route::prefix('vgames')
    ->group(function () {
        Route::any('/{token}/{action}', [GameController::class, 'sourceProvider']);
    });

Route::prefix('casinos')
    ->group(function () {
        Route::get('games', [GameController::class, 'allGames']);
    });
