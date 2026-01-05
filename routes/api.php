<?php

use App\Http\Controllers\Api\InfluencerBonusController;
use App\Http\Controllers\Api\Wallet\DepositController;
use App\Http\Controllers\Games\MinesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
 * Mines Game API Routes
 */
Route::middleware(['auth.jwt'])->prefix('mines')->group(function () {
    Route::post('start', [MinesController::class, 'startGame']);
    Route::post('reveal', [MinesController::class, 'revealCell']);
    Route::post('cashout', [MinesController::class, 'cashout']);
});

/*
 * Cache Routes
 */
Route::get('cache/version', [\App\Http\Controllers\Api\CacheController::class, 'getVersion']);
Route::get('cache/increment', [\App\Http\Controllers\Api\CacheController::class, 'incrementVersion']);

/*
 * Settings Routes
 */
Route::get('settings/influencer-bonus', [\App\Http\Controllers\Api\SettingsController::class, 'getBonusSettings']);

/*
 * Influencer Bonus Routes
 */
Route::apiResource('influencer-bonuses', InfluencerBonusController::class);
Route::get('influencer-bonuses/code/{code}', [InfluencerBonusController::class, 'findByCode']);
Route::middleware(['auth.jwt'])->get('influencer-bonuses/check-redemption/{code}', [InfluencerBonusController::class, 'checkRedemptionStatus']);

/*
 * Support Routes
 */
Route::get('check-support-status', [\App\Http\Controllers\Api\SupportController::class, 'checkStatus']);

/*
 * Banner Routes
 */
Route::prefix('banners')->group(function () {
    include_once __DIR__.'/groups/api/banners.php';
});

/*
 * Pop-up Routes
 */
Route::prefix('popups')->group(function () {
    include_once __DIR__.'/groups/api/popups.php';
});

/*
 * Auth Route with JWT
 */
Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    include_once __DIR__.'/groups/api/auth/auth.php';
});

Route::group(['middleware' => ['auth.jwt', 'check.banned']], function () {
    Route::prefix('profile')
        ->group(function () {
            include_once __DIR__.'/groups/api/profile/profile.php';
            include_once __DIR__.'/groups/api/profile/affiliates.php';
            include_once __DIR__.'/groups/api/profile/wallet.php';
            include_once __DIR__.'/groups/api/profile/likes.php';
            include_once __DIR__.'/groups/api/profile/favorites.php';
            include_once __DIR__.'/groups/api/profile/recents.php';
            include_once __DIR__.'/groups/api/profile/vip.php';

            Route::prefix('verification')->group(function () {
                include_once __DIR__.'/groups/api/profile/verification.php';
            });
        });

    Route::prefix('wallet')
        ->group(function () {
            include_once __DIR__.'/groups/api/wallet/deposit.php';
            include_once __DIR__.'/groups/api/wallet/withdraw.php';
        });

    include_once __DIR__.'/groups/api/missions/mission.php';

    include_once __DIR__.'/groups/api/missions/missionuser.php';

});

Route::prefix('categories')
    ->group(function () {
        include_once __DIR__.'/groups/api/categories/index.php';

    });

include_once __DIR__.'/groups/api/games/index.php';
include_once __DIR__.'/groups/api/games/vgames.php';
include_once __DIR__.'/groups/api/games/vgames2.php';
include_once __DIR__.'/groups/api/gateways/suitpay.php';
include_once __DIR__.'/groups/api/gateways/digitopay.php';

Route::prefix('mollie')->group(function () {
    include_once __DIR__.'/groups/gateways/mollie.php';
});

// TBS API Routes
Route::prefix('tbs')
    ->group(function () {
        // Rotas públicas
        Route::post('webhook', [\App\Http\Controllers\Api\TbsController::class, 'webhook'])->name('tbs.webhook');
        Route::get('games', [\App\Http\Controllers\Api\TbsController::class, 'games'])->name('tbs.games');
        Route::get('test', [\App\Http\Controllers\Api\TbsController::class, 'test'])->name('tbs.test');

        // Rotas autenticadas
        Route::middleware(['auth.jwt', 'check.banned'])->group(function () {
            Route::post('game/open', [\App\Http\Controllers\Api\TbsController::class, 'open'])->name('tbs.game.open');
        });

        // Rotas de admin
        Route::middleware(['auth.jwt', 'check.banned', 'check.admin'])->group(function () {
            Route::post('games/sync', [\App\Http\Controllers\Api\TbsController::class, 'sync'])->name('tbs.games.sync');
            Route::get('logs', [\App\Http\Controllers\Api\TbsController::class, 'logs'])->name('tbs.logs');
        });

    });

Route::prefix('admin')
    ->middleware(['auth.jwt', 'check.banned', 'check.admin'])
    ->group(function () {
        include_once __DIR__.'/groups/api/admin.php';
    });

Route::prefix('search')
    ->group(function () {
        include_once __DIR__.'/groups/api/search/search.php';
    });

Route::prefix('profile')
    ->group(function () {
        Route::get('/settings/gateway', [\App\Http\Controllers\Api\GatewayController::class, 'getGateway']);
        Route::post('/settings/gateway/update', [\App\Http\Controllers\Api\GatewayController::class, 'updateGateway']);

        Route::post('/getLanguage', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'getLanguage']);
        Route::put('/updateLanguage', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'updateLanguage']);
    });

Route::prefix('providers')
    ->group(function () {});

Route::prefix('settings')
    ->group(function () {
        include_once __DIR__.'/groups/api/settings/settings.php';
        include_once __DIR__.'/groups/api/settings/banners.php';
        include_once __DIR__.'/groups/api/settings/currency.php';
        include_once __DIR__.'/groups/api/settings/bonus.php';
    });

Route::prefix('deposit')->name('deposit.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\DepositController::class, 'store'])->name('store');
});

Route::get('/run-build', function () {
    try {
        // Caminho absoluto para o script setup.sh
        $scriptPath = base_path('setup.sh');

        // Executar o script e capturar a saída
        $output = shell_exec("bash $scriptPath 2>&1");

        return response()->json([
            'status' => 'success',
            'output' => $output,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});
Route::post('deposit-sibs', [DepositController::class, 'callbackSibs']);

// Admin Stats Routes
Route::prefix('admin/stats')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/top-games', [\App\Http\Controllers\Admin\StatsController::class, 'getTopGames'])->middleware('can:admin');
});
// LANDING SPIN
// Route::prefix('spin')
//     ->group(function ()
//     {
//         include_once(__DIR__ . '/groups/api/spin/index.php');
//     })
//     ->name('landing.spin.');
