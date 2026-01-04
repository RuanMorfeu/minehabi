<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->as('auth.')
    ->group(function () {
        Route::get('/redirect/{driver}', [SocialAuthController::class, 'redirectToProvider']);
        Route::get('/callback/{driver}', [SocialAuthController::class, 'handleProviderCallback']);
    });
