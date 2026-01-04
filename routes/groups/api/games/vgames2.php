<?php

use App\Http\Controllers\Api\Providers\VGames2Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('vgames2')->group(function () {
    Route::get('/modal/{slug}', [VGames2Controller::class, 'modal'])->name('modal.play2');
    Route::get('/show/{game}/{aposta}', [VGames2Controller::class, 'show'])->name('show2');
    Route::get('/show/{game}', [VGames2Controller::class, 'show'])->name('show2');
});

// Rotas para demo dos jogos exclusive2
Route::prefix('demo-game2')->group(function () {
    Route::get('/{game}/info', [VGames2Controller::class, 'info'])->name('demo.games2.info');
    Route::post('/{game}/win', [VGames2Controller::class, 'win'])->name('demo.games2.win');
    Route::post('/{game}/lost', [VGames2Controller::class, 'lost'])->name('demo.games2.lost');
});

// Endpoints com rate limiting básico para prevenir spam
Route::prefix('vgames2')->middleware(['throttle:120,1'])->group(function () {
    Route::get('/{game}/info', [VGames2Controller::class, 'info'])->name('vgames2.info');
    Route::post('/{game}/win', [VGames2Controller::class, 'win'])->name('vgames2.win');
    Route::post('/{game}/lost', [VGames2Controller::class, 'lost'])->name('vgames2.lost');

    // Endpoints no padrão dos jogos vgames originais
    Route::post('/callback', [VGames2Controller::class, 'callback'])->name('vgames2.callback');
    Route::post('/subprocess', [VGames2Controller::class, 'subprocess'])->name('vgames2.subprocess');
});

// Rotas para integração com os jogos (similar à plataforma original)
Route::prefix('games2')->group(function () {
    Route::get('/{game}/info', [VGames2Controller::class, 'info'])->name('games2.info');
    Route::post('/{game}/win', [VGames2Controller::class, 'win'])->name('games2.win');
    Route::post('/{game}/lost', [VGames2Controller::class, 'lost'])->name('games2.lost');
});
