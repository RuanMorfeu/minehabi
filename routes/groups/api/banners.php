<?php

use App\Http\Controllers\Api\BannerController;
use Illuminate\Support\Facades\Route;

Route::get('deposit-promo', [BannerController::class, 'getDepositPromoBanner']);
Route::get('login', [BannerController::class, 'getLoginBanner']);
Route::get('register', [BannerController::class, 'getRegisterBanner']);
