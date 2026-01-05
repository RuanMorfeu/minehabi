<?php

use App\Http\Controllers\Api\Profile\WalletController;
use App\Http\Controllers\Api\Providers\VGamesController;
use App\Http\Controllers\Games\MinesController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|Sme
*/

Route::get('clear', function () {
    Artisan::call('optimize:clear');

    return back()->with('status', 'Sistema otimizado com sucesso!');
});

Route::get('clear-cache', function () {
    // Limpa todas as caches principais
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    // Redefine a versão do cache global para garantir que seja atualizada
    // Primeiro, limpa qualquer valor existente
    Cache::forget('global_cache_version');

    // Depois, define um novo valor (incrementado)
    $newVersion = time(); // Usa o timestamp atual como versão para garantir unicidade
    Cache::forever('global_cache_version', $newVersion);

    // Exibe uma notificação Filament
    \Filament\Notifications\Notification::make()
        ->title('Cache limpo com sucesso!')
        ->body('Nova versão do cache: '.$newVersion)
        ->success()
        ->send();

    return back();
})->name('clear.cache');

Route::get('/withdrawal/{id}', [WalletController::class, 'withdrawalFromModal'])->name('withdrawal');
Route::get('/cancelwithdrawal/{id}', [WalletController::class, 'cancelWithdrawal'])->name('cancelwithdrawal');
Route::post('/modal/{slug}/vgames/game/sub', [VGamesController::class, 'callback'])->name('callback');

// Rotas removidas - deixar o frontend SPA (Vue.js) tratar /modal2/{slug}

// Rotas para demo dos jogos exclusive2
Route::get('/demo-play2/{gameId?}', function ($gameId = null) {
    return view('layouts.app', [
        'page' => 'DemoPlayModal2',
        'gameId' => $gameId,
    ]);
})->name('demo.play2');

// Rota para página de vitória dos jogos demo
Route::get('/win', function () {
    return view('layouts.app', [
        'page' => 'HomePage',
    ]);
})->name('win.page');

// Rota pública para métricas do cassino (com URL aleatória para segurança)
Route::get('/d9dVHha05FEf4cH1r1F4', [\App\Http\Controllers\PublicMetricsController::class, 'show'])->name('public.metrics');

// Rota para download de arquivos ZIP
Route::get('/download-zip', [\App\Http\Controllers\DownloadController::class, 'downloadZip'])->name('download.zip');

// Rotas do jogo Mines
Route::get('/mines', [MinesController::class, 'index'])->name('games.mines');

// GAMES PROVIDER
// include_once(__DIR__ . '/groups/provider/apiPragmatic40.php');
include_once __DIR__.'/groups/provider/playFiver.php';
include_once __DIR__.'/groups/provider/fivers.php';
include_once __DIR__.'/groups/provider/drakon.php';

// GATEWAYS
include_once __DIR__.'/groups/gateways/suitpay.php';
include_once __DIR__.'/groups/gateways/digitopay.php';
include_once __DIR__.'/groups/gateways/ezzepay.php';
include_once __DIR__.'/groups/gateways/eupago.php';
include_once __DIR__.'/groups/gateways/sibs.php';
include_once __DIR__.'/groups/gateways/mollie.php';

// / SOCIAL

// Rotas para a página de análise de IPs
Route::get('/admin/ip-analysis', [\App\Http\Controllers\Admin\IpAnalysisController::class, 'index'])->name('admin.ip-analysis');
Route::get('/admin/ip-analysis/search', [\App\Http\Controllers\Admin\IpAnalysisController::class, 'searchByIp'])->name('admin.ip-analysis.search');
Route::get('/admin/ip-analysis/check-suspicious', [\App\Http\Controllers\Admin\IpAnalysisController::class, 'checkSuspicious'])->name('admin.ip-analysis.check-suspicious');
Route::post('/admin/ip-analysis/block', [\App\Http\Controllers\Admin\IpAnalysisController::class, 'blockIp'])->name('admin.ip-analysis.block');
Route::get('/admin/ip-analysis/unblock/{id}', [\App\Http\Controllers\Admin\IpAnalysisController::class, 'unblockIp'])->name('admin.ip-analysis.unblock');
include_once __DIR__.'/groups/auth/social.php';

// Proxy para imagens do R2
Route::get('/r2-image/{path}', [\App\Http\Controllers\ImageProxyController::class, 'serve'])
    ->name('r2.image.proxy')
    ->middleware('auth');

// APP
include_once __DIR__.'/groups/layouts/app.php';
