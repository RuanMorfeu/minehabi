<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\MinesGame;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class MinesController extends Controller
{
    /**
     * Exibe a página do jogo Mines
     */
    public function index()
    {
        return inertia('Games/Mines');
    }

    /**
     * Inicia uma nova partida
     */
    public function startGame(Request $request)
    {
        $request->validate([
            'bet_amount' => 'required|numeric|min:1|max:100',
            'mines_count' => 'required|integer|min:1|max:24',
        ]);

        $user = auth('api')->user();
        $wallet = $user->wallet;
        $betAmount = $request->bet_amount;

        // Verifica saldo usando Helper para consistência
        if ($wallet->total_balance < $betAmount) { // total_balance é um accessor comum, mas vamos garantir
            $totalAvailable = $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal;
            if ($totalAvailable < $betAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente',
                ], 400);
            }
        }

        // 1. Desconta o saldo usando o Helper central (Gerencia ordem: Bonus -> Balance -> Withdrawal)
        $changeBonus = \App\Helpers\Core::DiscountBalance($wallet, $betAmount);

        if ($changeBonus === 'no_balance') {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente para realizar a aposta',
            ], 400);
        }

        // 2. Contabiliza a aposta no Rollover
        \App\Helpers\Core::payWithRollover($user->id, $changeBonus, 0, $betAmount, 'bet');

        // Gerar posições das minas
        $minePositions = $this->generateMinePositions($request->mines_count);

        // Criar registro do jogo
        $game = MinesGame::create([
            'user_id' => $user->id,
            'bet_amount' => $betAmount,
            'mines_count' => $request->mines_count,
            'mine_positions' => json_encode($minePositions),
            'status' => 'playing',
            'multiplier' => 1.00,
            'potential_win' => $betAmount,
            'wallet_type' => $changeBonus, // Salva qual carteira foi usada
        ]);

        // Criar transação local para histórico
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'bet',
            'amount' => -$betAmount,
            'game_id' => $game->id,
            'description' => 'Aposta no jogo Mines',
            'wallet_type' => $changeBonus,
        ]);

        return response()->json([
            'success' => true,
            'game_id' => $game->id,
            'mine_positions' => $minePositions,
            'balance' => $wallet->fresh()->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
        ]);
    }

    /**
     * Processa um clique no grid
     */
    public function revealCell(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:mines_games,id',
            'position' => 'required|integer|min:0|max:24',
        ]);

        $user = auth('api')->user();
        $wallet = $user->wallet;
        $game = MinesGame::where('id', $request->game_id)
            ->where('user_id', $user->id)
            ->where('status', 'playing')
            ->firstOrFail();

        $minePositions = json_decode($game->mine_positions);

        // Lógica de Manipulação de Chance de Vitória
        // Recupera a chance do usuário ou a chance global
        $setting = \App\Models\Setting::first();
        $winChance = $user->mines_win_chance ?? $setting->mines_win_chance;

        // Se existe uma chance definida (seja user ou global) e clicou em uma mina
        if (! is_null($winChance) && in_array($request->position, $minePositions)) {
            // Sorteia se o usuário deve ser salvo baseada na porcentagem (0-100)
            if (rand(1, 100) <= $winChance) {
                // Recupera posições já reveladas
                $revealedPositions = $game->revealed_positions ? json_decode($game->revealed_positions) : [];

                // Todas as posições do tabuleiro
                $allPositions = range(0, 24);

                // Posições que NÃO podem receber a mina movida:
                // 1. Onde já tem mina (exceto a que ele clicou, que vai sair)
                // 2. Onde já foi revelado (estrelas)
                // 3. A posição que ele clicou agora (queremos que vire estrela)
                $occupiedOrSafePositions = array_unique(array_merge($minePositions, $revealedPositions, [$request->position]));

                // Posições livres para onde a mina pode ser movida
                $availablePositions = array_diff($allPositions, $occupiedOrSafePositions);

                // Se houver lugar disponível para mover a mina
                if (! empty($availablePositions)) {
                    // Remove a mina da posição clicada
                    $minePositions = array_values(array_diff($minePositions, [$request->position]));

                    // Escolhe uma nova posição aleatória para a mina
                    $newMinePosition = $availablePositions[array_rand($availablePositions)];
                    $minePositions[] = $newMinePosition;

                    // Atualiza as posições no banco de dados
                    $game->update([
                        'mine_positions' => json_encode($minePositions),
                    ]);

                    // Log para debug (opcional)
                    \Log::info("Mina movida (Global/User) para salvar usuário {$user->id}. De: {$request->position} Para: {$newMinePosition}. Chance: {$winChance}%");
                }
            }
        }

        if (in_array($request->position, $minePositions)) {
            // Game Over - atingiu uma mina
            $game->update([
                'status' => 'lost',
                'revealed_positions' => json_encode($minePositions),
            ]);

            // Criar transação de perda (valor zero, apenas para registro)
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'loss',
                'amount' => 0,
                'game_id' => $game->id,
                'description' => 'Perdeu no jogo Mines',
            ]);

            $wallet = $wallet->fresh();

            return response()->json([
                'success' => true,
                'is_mine' => true,
                'game_over' => true,
                'mine_positions' => $minePositions,
                'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
            ]);
        } else {
            // Acertou - calcular novo multiplicador
            $revealedPositions = $game->revealed_positions ? json_decode($game->revealed_positions) : [];
            $revealedPositions[] = $request->position;

            $newMultiplier = $this->calculateMultiplier(
                $game->mines_count,
                count($revealedPositions)
            );

            $potentialWin = $game->bet_amount * $newMultiplier;

            $game->update([
                'revealed_positions' => json_encode($revealedPositions),
                'multiplier' => $newMultiplier,
                'potential_win' => $potentialWin,
            ]);

            return response()->json([
                'success' => true,
                'is_mine' => false,
                'multiplier' => $newMultiplier,
                'potential_win' => $potentialWin,
                'revealed_count' => count($revealedPositions),
            ]);
        }
    }

    /**
     * Realiza o cashout
     */
    public function cashout(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:mines_games,id',
        ]);

        $user = auth('api')->user();
        $wallet = $user->wallet;
        $game = MinesGame::where('id', $request->game_id)
            ->where('user_id', $user->id)
            ->where('status', 'playing')
            ->firstOrFail();

        $winAmount = $game->potential_win ?? $game->bet_amount;

        // Creditar o valor ganho usando o Helper de Rollover
        // Passamos bet=0 porque o rollover da aposta já foi descontado no startGame
        \App\Helpers\Core::payWithRollover($user->id, $game->wallet_type, $winAmount, 0, 'win');

        // Atualizar status do jogo
        $game->update([
            'status' => 'won',
            'win_amount' => $winAmount,
        ]);

        // Criar transação de ganho local (apenas para registro, o saldo já foi movido pelo Helper)
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'win',
            'amount' => $winAmount,
            'game_id' => $game->id,
            'description' => 'Ganho no jogo Mines',
            'wallet_type' => $game->wallet_type,
        ]);

        $wallet = $wallet->fresh();

        return response()->json([
            'success' => true,
            'win_amount' => $winAmount,
            'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
            'mine_positions' => json_decode($game->mine_positions),
        ]);
    }

    /**
     * Gera posições aleatórias para as minas
     */
    private function generateMinePositions($count)
    {
        $positions = range(0, 24);
        shuffle($positions);

        return array_slice($positions, 0, $count);
    }

    /**
     * Calcula o multiplicador baseado na fórmula da Spribe
     * Para o primeiro clique, usa a tabela fixa
     * A partir do segundo clique, usa: Multiplicador = arredondado(0.97 × [C(25, k) / C(25 - N, k)], 2)
     */
    private function calculateMultiplier($minesCount, $revealedCount)
    {
        // Tabela de multiplicadores para o primeiro acerto (revealedCount = 1)
        $initialMultipliers = [
            1 => 1.01,
            2 => 1.05,
            3 => 1.10,
            4 => 1.15,
            5 => 1.21,
            6 => 1.28,
            7 => 1.35,
            8 => 1.43,
            9 => 1.52,
            10 => 1.62,
            11 => 1.73,
            12 => 1.87,
            13 => 2.02,
            14 => 2.20,
            15 => 2.43,
            16 => 2.69,
            17 => 3.03,
            18 => 3.46,
            19 => 4.04,
            20 => 4.85,
            21 => 6.06,
            22 => 8.08,
            23 => 12.12,
            24 => 24.25,
        ];

        // Se for o primeiro acerto, retorna o valor exato da tabela
        if ($revealedCount === 1 && isset($initialMultipliers[$minesCount])) {
            return $initialMultipliers[$minesCount];
        }

        if ($revealedCount === 0) {
            return 1.00;
        }

        // Calcula combinações: C(n, k) = n! / (k! * (n-k)!)
        $totalStars = 25 - $minesCount;

        // Se tentou revelar mais estrelas que o possível
        if ($revealedCount > $totalStars) {
            return 0.00;
        }

        // Calcula C(25, k)
        $combinations25k = $this->combination(25, $revealedCount);

        // Calcula C(25 - N, k) = C(totalStars, k)
        $combinationsStars = $this->combination($totalStars, $revealedCount);

        // Aplica a fórmula da Spribe
        $multiplier = 0.97 * ($combinations25k / $combinationsStars);

        // Arredonda para 2 casas decimais
        return round($multiplier, 2);
    }

    /**
     * Calcula combinação C(n, k)
     */
    private function combination($n, $k)
    {
        if ($k > $n || $k < 0) {
            return 0;
        }

        if ($k == 0 || $k == $n) {
            return 1;
        }

        // Otimização: usa o menor valor entre k e n-k
        $k = min($k, $n - $k);

        $result = 1;
        for ($i = 0; $i < $k; $i++) {
            $result = $result * ($n - $i) / ($i + 1);
        }

        return $result;
    }
}
