<?php

use App\Http\Controllers\Api\Providers\VGamesController;
use Illuminate\Support\Facades\Route;

Route::get('/modalGame/{slug}', [VGamesController::class, 'modal'])->name('modal.play');

Route::prefix('vgames')
    ->group(function () {
        Route::any('/{token}/{action}', [VGamesController::class, 'vgameProvider']);
        Route::get('/openGame/', [VGamesController::class, 'index'])->name('index');
        Route::get('/openGame/{game}/{aposta}', [VGamesController::class, 'show'])->name('show');
        Route::get('/openGame/{game}', [VGamesController::class, 'show'])->name('show');
        Route::post('/callback', [VGamesController::class, 'callback'])->name('vgames.callback');
    });
