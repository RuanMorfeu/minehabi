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

        // 1. Desconta o saldo usando o método específico do Mines (Bonus -> Balance -> Withdrawal)
        $changeBonus = \App\Helpers\Core::DiscountBalanceMines($wallet, $betAmount);

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
        // O Model tem 'mine_positions' => 'array', então passamos o array direto.
        // O Laravel vai converter para JSON automaticamente.
        $game = MinesGame::create([
            'user_id' => $user->id,
            'bet_amount' => $betAmount,
            'mines_count' => $request->mines_count,
            'mine_positions' => $minePositions, // Passando array direto
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

        // Atualiza a instância da carteira com os dados do banco
        $wallet = $wallet->fresh();

        return response()->json([
            'success' => true,
            'game_id' => $game->id,
            'mine_positions' => [], // NÃO enviar as posições das minas no início (Segurança + Lógica de Backend)
            'balance' => $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal,
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

        // Garante que minePositions seja um array, independente do casting
        $minePositionsRaw = $game->mine_positions;
        if (is_string($minePositionsRaw)) {
            $minePositions = json_decode($minePositionsRaw, true);
        } elseif (is_array($minePositionsRaw)) {
            $minePositions = $minePositionsRaw;
        } else {
            $minePositions = []; // Fallback de segurança
        }

        // Garante que são inteiros para comparação estrita se necessário, ou pelo menos consistente
        $minePositions = array_map('intval', $minePositions);
        $position = intval($request->position);

        // Lógica de Manipulação de Chance de Vitória
        // Recupera a chance do usuário ou a chance global
        $setting = \App\Models\Setting::first();
        $userChance = $user->mines_win_chance;
        $globalChance = $setting->mines_win_chance;
        $winChance = $userChance ?? $globalChance;

        $debugAction = 'none';
        $debugDice = null;

        \Log::info('MINES REVEAL START', [
            'game_id' => $game->id,
            'position' => $position,
            'mine_positions_type' => gettype($minePositionsRaw),
            'mine_positions_count' => count($minePositions),
            'has_mine_initially' => in_array($position, $minePositions),
            'win_chance' => $winChance,
        ]);

        // Se existe uma chance definida (seja user ou global) e clicou em uma mina
        if (! is_null($winChance) && in_array($position, $minePositions)) {
            $dice = rand(1, 100);
            $debugDice = $dice;

            \Log::info('MINES PROBABILITY CHECK', ['dice' => $dice, 'chance' => $winChance]);

            // Sorteia se o usuário deve ser salvo baseada na porcentagem (0-100)
            if ($dice <= $winChance) {
                // Recupera posições já reveladas
                $revealedPositionsRaw = $game->revealed_positions;
                $revealedPositions = is_string($revealedPositionsRaw) ? json_decode($revealedPositionsRaw, true) : ($revealedPositionsRaw ?? []);
                $revealedPositions = array_map('intval', $revealedPositions);

                // Todas as posições do tabuleiro
                $allPositions = range(0, 24);

                // Posições que NÃO podem receber a mina movida:
                // 1. Onde já tem mina (exceto a que ele clicou, que vai sair)
                // 2. Onde já foi revelado (estrelas)
                // 3. A posição que ele clicou agora (queremos que vire estrela)
                $occupiedOrSafePositions = array_unique(array_merge($minePositions, $revealedPositions, [$position]));

                // Posições livres para onde a mina pode ser movida
                $availablePositions = array_diff($allPositions, $occupiedOrSafePositions);

                // Se houver lugar disponível para mover a mina
                if (! empty($availablePositions)) {
                    // Remove a mina da posição clicada
                    $minePositions = array_values(array_diff($minePositions, [$position]));

                    // Escolhe uma nova posição aleatória para a mina
                    $newMinePosition = $availablePositions[array_rand($availablePositions)];
                    $minePositions[] = $newMinePosition;

                    // Atualiza as posições no banco de dados
                    // Importante: Salvar como JSON se o cast esperar array mas formos salvar raw, ou array se cast cuidar
                    // Vamos salvar como array e deixar o Laravel lidar com o cast
                    $game->mine_positions = $minePositions;
                    $game->save();

                    $debugAction = 'saved_by_system';
                    \Log::info("MINES SAVED: Mina movida de {$position} para {$newMinePosition}");
                } else {
                    $debugAction = 'failed_to_move_full_board';
                    \Log::warning('MINES SAVE FAILED: Sem posições disponíveis');
                }
            } else {
                $debugAction = 'dice_failed';
            }
        } else {
            if (in_array($position, $minePositions)) {
                $debugAction = 'hit_mine_no_protection';
            } else {
                $debugAction = 'normal_safe_hit';
            }
        }

        if (in_array($position, $minePositions)) {
            // Game Over - atingiu uma mina
            $game->update([
                'status' => 'lost',
                'revealed_positions' => $minePositions, // Array direto (Model faz cast)
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
                'debug_info' => [
                    'user_chance' => $userChance,
                    'global_chance' => $globalChance,
                    'final_win_chance' => $winChance,
                    'dice_result' => isset($debugDice) ? $debugDice : 'N/A',
                    'action' => $debugAction,
                    'is_mine_result' => true,
                    'position_clicked' => $position,
                ],
            ]);
        } else {
            // Acertou - calcular novo multiplicador
            $revealedPositionsRaw = $game->revealed_positions;
            $revealedPositions = is_string($revealedPositionsRaw) ? json_decode($revealedPositionsRaw, true) : ($revealedPositionsRaw ?? []);

            $revealedPositions[] = $request->position;

            $newMultiplier = $this->calculateMultiplier(
                $game->mines_count,
                count($revealedPositions)
            );

            $potentialWin = $game->bet_amount * $newMultiplier;

            $game->update([
                'revealed_positions' => $revealedPositions, // Array direto (Model faz cast)
                'multiplier' => $newMultiplier,
                'potential_win' => $potentialWin,
            ]);

            return response()->json([
                'success' => true,
                'is_mine' => false,
                'multiplier' => $newMultiplier,
                'potential_win' => $potentialWin,
                'revealed_count' => count($revealedPositions),
                'debug_info' => [
                    'user_chance' => $userChance,
                    'global_chance' => $globalChance,
                    'final_win_chance' => $winChance,
                    'dice_result' => isset($debugDice) ? $debugDice : 'N/A',
                    'action' => $debugAction,
                    'is_mine_result' => false,
                    'position_clicked' => $position,
                ],
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
            'mine_positions' => $game->mine_positions, // Cast do Model já retorna array
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
            21 => 5.50, // Ajustado
            22 => 6.25, // Ajustado
            23 => 7.14, // Ajustado
            24 => 8.33, // Ajustado
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
