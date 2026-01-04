<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\Games\Angry;
use App\Http\Controllers\Panel\Games\Jetpack;
use App\Http\Controllers\Panel\Games\Pacman;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Info endpoint - retorna configurações do jogo (igual plataforma original)
     * Aceita parâmetro de aposta para manter compatibilidade
     */
    public function info($game, $betAmount = null)
    {
        $userId = Auth::id();
        $lastTransaction = null;
        $fakeUser = false;

        if ($userId) {
            // Usuário autenticado - busca última transação
            $lastTransaction = Order::where([
                ['user_id', $userId],
                ['game', $this->getGameName($game)],
                ['type', 'bet'],
            ])->orderBy('id', 'DESC')->first();

            $fakeUser = Auth::user()->is_demo_agent ?? false;
        }

        $gameObj = $this->getGameObj($game);
        $settings = [];

        if ($gameObj) {
            $settings = $fakeUser ? $gameObj->getPresellSettings() : $gameObj->getRealSettings();
        }

        // Determina o valor da aposta - prioriza banco, depois parâmetro
        $betValue = 1.0; // Valor padrão
        if ($lastTransaction && $lastTransaction->type == 'bet') {
            $betValue = $lastTransaction->amount;
        } elseif ($betAmount !== null) {
            // Validação rigorosa do parâmetro
            $betAmount = (float) $betAmount;
            if ($betAmount < 0.1 || $betAmount > 1000 || ! is_numeric($betAmount)) {
                Log::warning('Tentativa de valor de aposta inválido', [
                    'bet_amount' => $betAmount,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                $betValue = 1.0; // Valor padrão seguro
            } else {
                $betValue = $betAmount;
            }
        }

        Log::info('GameController - Info chamado:', [
            'game' => $game,
            'user_id' => $userId,
            'fake_user' => $fakeUser,
            'settings' => $settings,
            'bet_amount' => $betValue,
            'bet_source' => $lastTransaction ? 'database' : 'parameter',
        ]);

        return response()->json([
            'last_balance' => [
                'amount' => $betValue,
            ],
            'settings' => $settings,
            'fake' => $fakeUser ? 1 : 0,
        ]);
    }

    /**
     * Get game object by ID
     */
    public function getGameObj($game_id)
    {
        $gameClasses = $this->getGameClasses();

        foreach ($gameClasses as $gameClass) {
            $game = new $gameClass;

            if ($game->getId() == $game_id) {
                return $game;
            }
        }

        return null;
    }

    /**
     * Get available game classes
     */
    private function getGameClasses()
    {
        return [
            Angry::class,
            Jetpack::class,
            Pacman::class,
        ];
    }

    /**
     * Get game name by ID
     */
    private function getGameName($game_id)
    {
        $gameObj = $this->getGameObj($game_id);

        return $gameObj ? $gameObj->getName() : $game_id;
    }
}
