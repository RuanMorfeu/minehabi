<?php

use App\Http\Controllers\Api\Profile\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Verification Routes
|--------------------------------------------------------------------------
|
| Rotas para verificação de documentos dos usuários
|
*/

Route::controller(VerificationController::class)->group(function () {
    Route::get('/', 'index')->name('verification.index');
    Route::post('/upload', 'uploadDocuments')->name('verification.upload');
    Route::post('/personal-info', 'storePersonalInfo')->name('verification.personal-info');
});
