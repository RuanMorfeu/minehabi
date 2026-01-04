<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\Providers\TbsService;
use App\Traits\TbsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TbsController extends Controller
{
    use TbsTrait;

    /**
     * Lista todos os jogos TBS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function games()
    {
        try {
            $games = Game::where('distribution', 'tbs')
                ->with('provider')
                ->where('active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $games,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar jogos TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar jogos',
            ], 500);
        }
    }

    /**
     * Abre um jogo TBS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function open(Request $request)
    {
        try {
            $user = Auth::user();
            $gameId = $request->input('game_id');
            $demo = $request->input('demo', false);

            // Verifica se o jogo existe
            $game = Game::where('game_code', $gameId)->first();
            if (! $game) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jogo não encontrado',
                ], 404);
            }

            // Lança o jogo
            $result = $this->tbsGameLaunch($user, $gameId, $demo);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erro ao abrir jogo TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao abrir o jogo',
            ], 500);
        }
    }

    /**
     * Sincroniza jogos da TBS API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync()
    {
        try {
            $tbsService = new TbsService;
            $result = $tbsService->sync(false);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar jogos TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar jogos',
            ], 500);
        }
    }

    /**
     * Processa o webhook da TBS API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('Webhook TBS recebido', $data);

            $result = $this->webhookTbs($data);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erro no webhook TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno',
            ], 500);
        }
    }

    /**
     * Testa a conexão com a API TBS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function test()
    {
        try {
            $tbsService = new TbsService;
            $games = $tbsService->getAllGames();

            $totalGames = count($games);
            $providers = [];

            foreach ($games as $game) {
                $providerCode = strtolower($game['provider'] ?? '');

                if (! isset($providers[$providerCode])) {
                    $providers[$providerCode] = [
                        'name' => $game['provider'] ?? '',
                        'games' => 1,
                    ];
                } else {
                    $providers[$providerCode]['games']++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_games' => $totalGames,
                    'providers' => $providers,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao testar conexão TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar conexão',
            ], 500);
        }
    }

    /**
     * Obtém logs da TBS API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs()
    {
        try {
            // Busca os últimos 100 logs relacionados ao TBS
            $logs = [];
            $logFile = storage_path('logs/laravel.log');

            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*?TBS.*?\n/s', $logContent, $matches);

                if (! empty($matches[0])) {
                    $logs = array_slice($matches[0], -100);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter logs TBS: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter logs',
            ], 500);
        }
    }
}
