<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\ChickenGame;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ChickenController extends Controller
{
    /**
     * Coeficientes de multiplicador por dificuldade
     */
    private $coefficients = [
        'easy' => [1.03, 1.07, 1.12, 1.17, 1.23, 1.29, 1.36, 1.44, 1.53, 1.63, 1.74, 1.86, 1.99, 2.13, 2.29, 2.46, 2.64, 2.84, 3.06, 3.30, 3.56, 3.84, 4.15, 4.48, 4.84, 5.23, 5.65, 6.11, 6.61, 7.15],
        'medium' => [1.15, 1.32, 1.51, 1.73, 1.98, 2.27, 2.60, 2.98, 3.41, 3.90, 4.46, 5.11, 5.85, 6.70, 7.67, 8.78, 10.05, 11.51, 13.19, 15.11, 17.32, 19.85, 22.75, 26.08, 29.90, 34.28, 39.30, 45.05, 51.63, 59.20],
        'hard' => [1.25, 1.45, 1.68, 1.95, 2.26, 2.62, 3.04, 3.52, 4.08, 4.73, 5.48, 6.35, 7.36, 8.53, 9.89, 11.47, 13.30, 15.42, 17.89, 20.75, 24.07, 27.92, 32.39, 37.57, 43.58, 50.55, 58.64, 68.02, 78.89, 91.50],
        'hardcore' => [1.35, 1.60, 1.90, 2.25, 2.67, 3.17, 3.76, 4.46, 5.30, 6.30, 7.48, 8.89, 10.57, 12.57, 14.95, 17.78, 21.15, 25.15, 29.90, 35.56, 42.30, 50.28, 59.77, 71.06, 84.51, 100.45, 119.43, 141.96, 168.76, 200.50],
    ];

    /**
     * Ranges de armadilha por dificuldade (baseado no server.js do jogo original)
     */
    private $trapRanges = [
        'easy' => [1, 20],
        'medium' => [1, 12],
        'hard' => [1, 8],
        'hardcore' => [1, 6],
    ];

    public function index()
    {
        return inertia('Games/Chicken');
    }

    public function startGame(Request $request)
    {
        $request->validate([
            'bet_amount' => 'required|numeric|min:0.5|max:100',
            'difficulty' => 'required|in:easy,medium,hard,hardcore',
        ]);

        $user = auth('api')->user();
        $wallet = $user->wallet;
        $betAmount = $request->bet_amount;
        $difficulty = $request->difficulty;

        // Verifica saldo
        if ($wallet->total_balance < $betAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente',
            ], 400);
        }

        // Desconta saldo (Reutilizando a lógica do Mines que prioriza Bonus -> Balance -> Withdrawal)
        // Podemos criar um Helper genérico depois, mas por hora vamos usar o DiscountBalanceMines que já faz exatamente o que queremos
        // ou criar um novo método no Helper se quisermos separar os nomes.
        // Dado que a lógica é idêntica (jogos "in-house"), vou usar o mesmo método ou replicar a lógica aqui se o nome incomodar.
        // Vou usar o Core::DiscountBalanceMines pois ele encapsula a lógica de prioridade de carteiras corretamente.
        $changeBonus = \App\Helpers\Core::DiscountBalanceMines($wallet, $betAmount);

        if ($changeBonus === 'no_balance') {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente para realizar a aposta',
            ], 400);
        }

        // Contabiliza Rollover
        \App\Helpers\Core::payWithRollover($user->id, $changeBonus, 0, $betAmount, 'bet');

        // Gera posição da armadilha
        $trapPosition = $this->generateTrapPosition($difficulty);

        // Cria o jogo
        $game = ChickenGame::create([
            'user_id' => $user->id,
            'bet_amount' => $betAmount,
            'difficulty' => $difficulty,
            'trap_position' => $trapPosition,
            'current_step' => 0,
            'status' => 'playing',
            'multiplier' => 1.00,
            'potential_win' => $betAmount, // Começa com o valor da aposta (se sair no 0, mas na verdade só ganha se passar do 1)
            'wallet_type' => $changeBonus,
        ]);

        // Transação
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'bet',
            'amount' => -$betAmount,
            'game_id' => $game->id,
            'description' => 'Aposta no jogo Chicken',
            'wallet_type' => $changeBonus,
        ]);

        $wallet = $wallet->fresh();

        return response()->json([
            'success' => true,
            'game_id' => $game->id,
            'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
            'difficulty' => $difficulty,
        ]);
    }

    public function playStep(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:chicken_games,id',
        ]);

        $user = auth('api')->user();
        $game = ChickenGame::where('id', $request->game_id)
            ->where('user_id', $user->id)
            ->where('status', 'playing')
            ->firstOrFail();

        $nextStep = $game->current_step + 1;

        // Lógica de Manipulação de Chance de Vitória
        // Recupera a chance do usuário ou a chance global
        $setting = \App\Models\Setting::first();
        $userChance = $user->chicken_win_chance;
        $globalChance = $setting->chicken_win_chance ?? 50; // Default 50% se não definido
        $winChance = $userChance ?? $globalChance;

        $debugAction = 'none';
        $debugDice = null;

        \Log::info('CHICKEN PLAY STEP START', [
            'game_id' => $game->id,
            'next_step' => $nextStep,
            'trap_position' => $game->trap_position,
            'win_chance' => $winChance,
        ]);

        // Verifica se atingiu a armadilha E se deve aplicar manipulação
        $shouldLose = $nextStep == $game->trap_position;

        if ($shouldLose && ! is_null($winChance)) {
            $dice = rand(1, 100);
            $debugDice = $dice;

            \Log::info('CHICKEN PROBABILITY CHECK', ['dice' => $dice, 'chance' => $winChance]);

            // Sorteia se o usuário deve ser salvo baseada na porcentagem (0-100)
            if ($dice <= $winChance) {
                // Salva o jogador - move a armadilha para frente
                $debugAction = 'trap_moved';

                // Move a armadilha para uma posição posterior
                $maxPosition = max($game->trap_position + 1, 30);
                $newTrapPosition = rand($game->trap_position + 1, $maxPosition);

                $game->update([
                    'trap_position' => $newTrapPosition,
                ]);

                \Log::info('CHICKEN TRAP MOVED', [
                    'old_position' => $game->trap_position,
                    'new_position' => $newTrapPosition,
                ]);

                $shouldLose = false;
            }
        }

        // Se ainda deve perder (não foi salvo ou não tem chance definida)
        if ($shouldLose) {
            // Perdeu
            $game->update([
                'current_step' => $nextStep,
                'status' => 'lost',
                'multiplier' => 0,
                'win_amount' => 0,
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'loss',
                'amount' => 0,
                'game_id' => $game->id,
                'description' => 'Perdeu no jogo Chicken',
            ]);

            return response()->json([
                'success' => true,
                'status' => 'lost',
                'message' => 'Você encontrou um osso!',
                'step' => $nextStep,
                'trap_position' => $game->trap_position, // Revela onde estava
                'debug_info' => [
                    'user_chance' => $userChance,
                    'global_chance' => $globalChance,
                    'final_win_chance' => $winChance,
                    'dice_result' => isset($debugDice) ? $debugDice : 'N/A',
                    'action' => $debugAction,
                    'trap_hit' => true,
                ],
            ]);
        }

        // Ganhou o passo
        // Pega o multiplicador baseado no passo (índice array é passo - 1)
        // Se exceder o array (30 passos), usa o último ou finaliza (vamos assumir limite de 30)
        $coeffs = $this->coefficients[$game->difficulty];
        if ($nextStep >= count($coeffs)) {
            // Se chegou ao fim (passo 30), força cashout automático
            // Primeiro atualiza o jogo para o último passo válido
            $lastMultiplier = $coeffs[count($coeffs) - 1];
            $lastPotentialWin = $game->bet_amount * $lastMultiplier;

            $game->update([
                'current_step' => count($coeffs),
                'multiplier' => $lastMultiplier,
                'potential_win' => $lastPotentialWin,
            ]);

            // Agora faz o cashout automático diretamente (sem chamar o método para evitar problemas com request)
            $winAmount = $lastPotentialWin;

            \Log::info('CHICKEN AUTO CASHOUT - Iniciando', [
                'game_id' => $game->id,
                'user_id' => $user->id,
                'win_amount' => $winAmount,
                'wallet_type' => $game->wallet_type,
            ]);

            // Credita valor na carteira
            \App\Helpers\Core::payWithRollover($user->id, $game->wallet_type, $winAmount, 0, 'win');

            \Log::info('CHICKEN AUTO CASHOUT - Após payWithRollover');

            // Atualiza status do jogo
            $game->update([
                'status' => 'won',
                'win_amount' => $winAmount,
            ]);

            \Log::info('CHICKEN AUTO CASHOUT - Após update do jogo');

            // Cria transação
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'win',
                'amount' => $winAmount,
                'game_id' => $game->id,
                'description' => 'Ganho no jogo Chicken',
                'wallet_type' => $game->wallet_type,
            ]);

            \Log::info('CHICKEN AUTO CASHOUT - Após criar transação');

            $wallet = $user->wallet->fresh();

            \Log::info('CHICKEN AUTO CASHOUT - Saldo final', [
                'balance' => $wallet->balance,
                'balance_bonus' => $wallet->balance_bonus,
                'balance_withdrawal' => $wallet->balance_withdrawal,
                'total' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
            ]);

            return response()->json([
                'success' => true,
                'status' => 'won',
                'win_amount' => $winAmount,
                'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
                'message' => 'Parabéns! Você completou todos os 30 passos!',
                'debug_info' => [
                    'user_chance' => $userChance,
                    'global_chance' => $globalChance,
                    'final_win_chance' => $winChance,
                    'dice_result' => isset($debugDice) ? $debugDice : 'N/A',
                    'action' => 'auto_cashout_complete',
                    'trap_hit' => false,
                ],
            ]);
        }

        $multiplier = $coeffs[$nextStep - 1];
        $potentialWin = $game->bet_amount * $multiplier;

        $game->update([
            'current_step' => $nextStep,
            'multiplier' => $multiplier,
            'potential_win' => $potentialWin,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'playing',
            'step' => $nextStep,
            'multiplier' => $multiplier,
            'potential_win' => $potentialWin,
            'debug_info' => [
                'user_chance' => $userChance,
                'global_chance' => $globalChance,
                'final_win_chance' => $winChance,
                'dice_result' => isset($debugDice) ? $debugDice : 'N/A',
                'action' => $debugAction,
                'trap_hit' => false,
            ],
        ]);
    }

    public function cashout(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:chicken_games,id',
        ]);

        $user = auth('api')->user();
        $game = ChickenGame::where('id', $request->game_id)
            ->where('user_id', $user->id)
            ->where('status', 'playing')
            ->firstOrFail();

        // Se tentar cashout no passo 0, devolve aposta? Ou não permite?
        // Geralmente precisa jogar pelo menos 1 vez.
        if ($game->current_step == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Jogue pelo menos uma rodada antes de sacar.',
            ], 400);
        }

        $winAmount = $game->potential_win;

        // Credita valor
        \App\Helpers\Core::payWithRollover($user->id, $game->wallet_type, $winAmount, 0, 'win');

        $game->update([
            'status' => 'won',
            'win_amount' => $winAmount,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'win',
            'amount' => $winAmount,
            'game_id' => $game->id,
            'description' => 'Ganho no jogo Chicken',
            'wallet_type' => $game->wallet_type,
        ]);

        $wallet = $user->wallet->fresh();

        return response()->json([
            'success' => true,
            'status' => 'won',
            'win_amount' => $winAmount,
            'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
        ]);
    }

    private function generateTrapPosition($difficulty)
    {
        $range = $this->trapRanges[$difficulty];
        // Sorteia entre min e max. Ex: easy [1, 20].
        // Mas a lógica original parece mais complexa (zonas).
        // Para simplificar e manter justo/aleatório:
        // O jogo tem 30 passos.
        // Se a armadilha for sorteada no passo X, o jogador perde ao tentar ir para X.
        // O range [1, 20] significa que a armadilha pode estar logo no começo ou até o 20.
        // Isso significa que é IMPOSSÍVEL ganhar se a armadilha for < 30?
        // No original, parece que o jogo é infinito até bater na armadilha? Ou tem fim?
        // Os arrays de coeficientes têm 30 posições.
        // Se a armadilha for > 30, o jogador ganha o jogo "completo"?
        // Vamos assumir que a armadilha SEMPRE existe dentro dos limites possíveis.

        return rand($range[0], $range[1]);
    }
}
