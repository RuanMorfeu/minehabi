<?php

use App\Http\Controllers\Admin\SmsSettingsController;
use Illuminate\Support\Facades\Route;

// Rotas para Configurações de SMS
Route::get('/sms-settings', [SmsSettingsController::class, 'index']);
Route::put('/sms-settings/{eventType}', [SmsSettingsController::class, 'update']);
