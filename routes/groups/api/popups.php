<?php

use App\Http\Controllers\Api\AuthPopupController;
use App\Http\Controllers\Api\PopupMetricsController;
use Illuminate\Support\Facades\Route;

// Rotas públicas para pop-ups
Route::get('all-active', [AuthPopupController::class, 'getAllActivePopups']);
Route::get('login', [AuthPopupController::class, 'getLoginPopup']);
Route::get('register', [AuthPopupController::class, 'getRegisterPopup']);
Route::get('by-user-type', [AuthPopupController::class, 'getPopupByUserType']);
Route::get('with-deposit', [AuthPopupController::class, 'getWithDepositPopup']);
Route::get('without-deposit', [AuthPopupController::class, 'getWithoutDepositPopup']);
Route::get('affiliate', [AuthPopupController::class, 'getAffiliatePopup']);
Route::get('by-deposit-status', [AuthPopupController::class, 'getPopupByDepositStatus']);

// Rotas para métricas de pop-ups (públicas para permitir tracking de guests)
Route::post('metrics/view', [PopupMetricsController::class, 'recordView']);
Route::post('metrics/click', [PopupMetricsController::class, 'recordClick']);
Route::post('metrics/redemption', [PopupMetricsController::class, 'recordRedemption']);

// Rota para processar freespin do popup (requer autenticação)
Route::middleware(['auth.jwt', 'check.banned'])->post('process-freespin', [AuthPopupController::class, 'processPopupFreespin']);
