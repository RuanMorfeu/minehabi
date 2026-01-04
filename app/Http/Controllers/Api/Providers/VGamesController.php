<?php

namespace App\Http\Controllers\Api\Providers;

use App\Helpers\Core as Helper;
use App\Http\Controllers\Controller;
use App\Models\GameExclusive;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VGamesController extends Controller
{
    public function modal($identifier)
    {
        $game = GameExclusive::where('id', $identifier)->firstOrFail();

        // Verificar se o usuário está autenticado
        $user = auth('api')->user();

        // Verificar se o usuário tem jogos de habilidade bloqueados
        if ($user && $user->block_skill_games) {
            return response()->json([
                'error' => 'Jogos de habilidade bloqueados para este usuário',
                'blocked' => true,
            ], 403);
        }

        // Verificar se o usuário tem um valor mínimo personalizado definido
        if ($user && ! is_null($user->skill_games_min_amount)) {
            // Adicionar o valor mínimo personalizado ao objeto do jogo
            $game->min_amount = $user->skill_games_min_amount;
        }

        // Verificar se o usuário tem um valor máximo personalizado definido
        if ($user && ! is_null($user->skill_games_max_amount)) {
            // Adicionar o valor máximo personalizado ao objeto do jogo
            $game->max_amount = $user->skill_games_max_amount;
        }

        return response()->json($game);
    }

    /**
     * vGame Provider
     * Store a newly created resource in storage.
     */
    public function vgameProvider(Request $request, $token, $action)
    {
        $tokenOpen = Helper::DecToken($token);
        $validEndpoints = ['session', 'icons', 'spin', 'freenum'];

        if (in_array($action, $validEndpoints)) {
            if (isset($tokenOpen['status']) && $tokenOpen['status']) {
                $game = GameExclusive::whereActive(1)->where('uuid', $tokenOpen['game'])->first();
                if (! empty($game)) {
                    $controller = Helper::createController($game->uuid);

                    switch ($action) {
                        case 'session':
                            return $controller->session($token);
                        case 'spin':
                            return $controller->spin($request, $token);
                        case 'freenum':
                            return $controller->freenum($request, $token);
                        case 'icons':
                            return $controller->icons();
                    }
                }
            }
        } else {
            return response()->json([], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('web.vgames.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug, $aposta = null)
    {

        Log::debug("Abrindo game $slug");

        $game = GameExclusive::whereActive(1)->where('uuid', $slug)->first();

        if (! empty($game)) {

            $baseurl = url('/modal', $game->id);
            $user = auth('api')->user();

            // Verificar se o usuário tem jogos de habilidade bloqueados
            if ($user && $user->block_skill_games) {
                Log::debug("Usuário {$user->id} tem jogos de habilidade bloqueados");

                return response()->json([
                    'error' => 'Jogos de habilidade bloqueados para este usuário',
                    'blocked' => true,
                ], 403);
            }

            $isInfluencer = $user && $user->is_demo_agent;

            // Define os valores padrão ou de influencer com base no tipo de usuário
            // Prioridade: 1) Dificuldade personalizada do usuário, 2) Influencer, 3) Aposta ≥ €3, 4) Padrão do jogo
            if ($user && ! is_null($user->skill_games_difficulty)) {
                $velo = $user->skill_games_difficulty; // Usar diretamente: easy/medium/hard
            } elseif ($isInfluencer && ! is_null($game->influencer_velocidade)) {
                $velo = $game->influencer_velocidade; // Influencers sempre usam configuração específica
            } elseif ($aposta && $aposta >= 3.00) {
                // Regra: Apostas de €3 ou mais iniciam no nível difícil (só para usuários normais)
                $velo = 'hard';
                Log::info('Aposta ≥ €3 detectada - Iniciando no nível difícil', [
                    'user_id' => $user->id ?? 'guest',
                    'aposta' => $aposta,
                    'difficulty_applied' => 'hard',
                    'reason' => 'high_bet_amount',
                ]);
            } else {
                $velo = $game->velocidade ?? 'medium';
            }

            if ($isInfluencer && ! is_null($game->influencer_xmeta)) {
                $xmeta = $game->influencer_xmeta;
            } else {
                $xmeta = $game->xmeta ?? 5;
            }

            if ($isInfluencer && ! is_null($game->influencer_coin_value)) {
                $coin_value = $game->influencer_coin_value;
            } else {
                $coin_value = $game->coin_value ?? 0.02;
            }

            // Regra especial: quando dificuldade é "hard", coin_value é sempre 0.01
            if ($velo === 'hard') {
                $coin_value = 0.01;
            }

            // Usar o valor real do usuário para is_demo_agent
            $is_demo_agent = $isInfluencer;

            $game->increment('views', 1); // add mais uma visualização
            $uniqueCode = 'source_'.Str::uuid();

            $token = Helper::MakeToken([
                'id' => isset(auth('api')->user()->id) ? auth('api')->user()->id : 0,
                'game' => $slug,
                'transaction_id' => $uniqueCode,
            ]);

            $id_user = auth('api')->user()->id ?? 0;

            if ($aposta && $id_user > 0) {

                $balance_type = 'balance';
                $user = User::where('id', $id_user)->first();
                $wallet = $user->wallet;

                if ($aposta > $user->wallet->total_balance) {
                    return back()->with('error', 'Saldo insuficiente!');
                }

                $valorPerdido = $aposta;
                $balanceBonus = $wallet->balance_bonus;
                $balance = $wallet->balance;
                $balanceWithdrawal = $wallet->balance_withdrawal;

                if ($balanceBonus >= $valorPerdido) {
                    $balanceBonus -= $valorPerdido;
                    $valorPerdido = 0;
                } else {
                    $valorPerdido -= $balanceBonus;
                    $balanceBonus = 0;
                }

                if ($balance >= $valorPerdido) {
                    $balance -= $valorPerdido;
                    $valorPerdido = 0;
                } else {
                    $valorPerdido -= $balance;
                    $balance = 0;
                }

                if ($balanceWithdrawal >= $valorPerdido) {
                    $balanceWithdrawal -= $valorPerdido;
                    $valorPerdido = 0;
                } else {
                    $valorPerdido -= $balanceWithdrawal;
                    $balanceWithdrawal = 0;
                }

                if ($wallet->balance > $aposta) {
                    $balance_type = 'balance';
                } elseif ($wallet->balance_withdrawal > $aposta) {
                    $balance_type = 'balance_withdrawal';
                } elseif ($wallet->balance_bonus > $aposta) {
                    $balance_type = 'balance_bonus';
                }

                $wallet->update([
                    'balance_bonus' => $balanceBonus,
                    'balance' => $balance,
                    'balance_withdrawal' => $balanceWithdrawal,
                ]);

                // $novoSaldo = $wallet->balance - (float)$aposta;
                // Wallet::where('user_id', $id_user)->decrement($balance_type, (float) $aposta);

                Order::create([
                    'user_id' => $id_user,
                    'transaction_id' => $uniqueCode,
                    'game' => $game->name,
                    'game_uuid' => $game->id,
                    'type' => 'bet',
                    'type_money' => $balance_type,
                    'amount' => $aposta,
                    'providers' => 'exclusive',
                    'refunded' => false,
                    'status' => true,
                ]);

                // Helper::generateGameHistory($user_id, "loss", 0, $aposta, $balance_type, $uniqueCode);
            }

            if ($id_user == 0) {
                // Usuário não logado - modo demo
                $is_demo_agent = true;
                $game->uuid = 'demo'.$game->uuid;
            }

            $test = compact('baseurl', 'velo', 'xmeta', 'coin_value', 'token', 'aposta', 'is_demo_agent');

            // Log para debug da dificuldade
            Log::debug('VGames - Dados enviados para o jogo', [
                'user_id' => $id_user,
                'is_influencer' => $isInfluencer,
                'is_demo_agent' => $is_demo_agent,
                'user_skill_games_difficulty' => $user->skill_games_difficulty ?? 'null',
                'final_velo' => $velo,
                'game_uuid' => $game->uuid,
                'url_params' => $test,
            ]);

            $urlGame = 'https://cdn.ganhoubet.com/'.$game->uuid.'/index.html?'.http_build_query($test);

            return response()->json([
                'game' => $game,
                'gameUrl' => $urlGame, // url('/vgames/' . $slug . '/'),
                'token' => $token,
                'velo' => $velo,
                'baseurl' => $baseurl,
                'aposta' => $aposta ?? 0,
            ]);

        }

        return back()->with('error', 'UUID Errado');
    }

    public function subprocess($valor, $token, $bet)
    {
        $decToken = Helper::DecToken($token);

        if ($token && isset($decToken['status']) && $decToken['status'] && $decToken['id']) {
            $wallet = Wallet::where('user_id', $decToken['id'])->first();
            $novoSaldo = $wallet->balance + $valor;
            $wallet = Wallet::where('user_id', $decToken['id'])->update(['balance' => $novoSaldo]);
        }

        return response()->json(['msg' => true, 'wallet' => $wallet ?? null], 200);
    }

    public function callback(Request $request)
    {
        Log::debug('Dados recebidos: val='.$_POST['val'].', bet='.$_POST['bet'].', token='.$_POST['token']);
        // error_log('Recebendo a requisição...');
        // error_log('Dados recebidos: val=' . $_POST['val'] . ', bet=' . $_POST['bet'] . ', token=' . $_POST['token']);

        if (isset($_POST['val'], $_POST['bet'], $_POST['token'])) {

            // Verifica se a operação já foi realizada
            if (isset($_SESSION['operation_completed']) && $_SESSION['operation_completed']) {
                Log::warning('Operação já foi realizada anteriormente na sessão atual.', ['user_id' => $user_id ?? null]);
                echo json_encode(['success' => false, 'message' => 'Operação já realizada.']);
                exit; // Interrompe a execução do script
            }

            $valorAcumulado = $request->input('val');
            $entrada = $request->input('bet');
            $token = $request->input('token');

            $game = Helper::DecToken($token);
            $user_id = $game['id'];
            $uuid = $game['transaction_id'];

            Log::debug("callback $user_id - $uuid");

            if ($token && $user_id > 0) {
                $changeBonus = 'balance';
                $user = User::where('id', $user_id)->first();

                // Verificar se o usuário tem jogos de habilidade bloqueados
                if ($user && $user->block_skill_games) {
                    Log::debug("Callback: Usuário {$user_id} tem jogos de habilidade bloqueados");

                    return json_encode([
                        'success' => false,
                        'message' => 'Jogos de habilidade bloqueados para este usuário',
                        'blocked' => true,
                    ]);
                }

                $wallet = $user->wallet;

                if ($wallet->balance_bonus > $entrada) {
                    $changeBonus = 'balance_bonus';
                } elseif ($wallet->balance >= $entrada) {
                    $changeBonus = 'balance';
                } elseif ($wallet->balance_withdrawal >= $entrada) {
                    $changeBonus = 'balance_withdrawal';
                } else {
                    $changeBonus = 'balance';
                }

                if ($valorAcumulado > 0) {
                    Order::create([
                        'user_id' => $user_id,
                        'transaction_id' => $uuid,
                        'game' => $game['game'],
                        'game_uuid' => $game['id'],
                        'type' => 'win',
                        'type_money' => 'balance',
                        'amount' => $valorAcumulado == 0 ? $entrada : $valorAcumulado,
                        'providers' => 'exclusive',
                        'refunded' => false,
                        'status' => true,
                    ]);

                    // Verificar se deve ativar bloqueio automático de jogos de habilidade
                    $this->checkAutoBlockOnWin($user, $valorAcumulado);
                }

                Log::debug("Chamando generateGameHistory $user_id - $uuid");
                Helper::generateGameHistory($user_id, $valorAcumulado == 0 ? 'win' : 'loss', $valorAcumulado, $entrada, $changeBonus, $uuid);

                $callback = $this->subprocess($valorAcumulado, $token, $entrada);

                // Verifica a resposta
                if ($callback) {
                    $_SESSION['operation_completed'] = true; // Marca a operação como concluída
                    Log::info('Saldo adicionado com sucesso.', ['user_id' => $user_id, 'valor' => $valorAcumulado]);

                    return json_encode(['success' => true, 'message' => 'Saldo adicionado com sucesso.']);
                } else {
                    Log::error('Falha ao adicionar saldo.', ['user_id' => $user_id, 'valor' => $valorAcumulado]);

                    return json_encode(['success' => false, 'message' => 'Falha ao adicionar saldo.']);
                }
            } else {
                // MANDA ESSA MENSAGEM PRA O JOGO NÃO TRAVAR QUANDO ABRE SEM INDICAR UM USUARIO LOGADO
                return json_encode(['success' => true, 'message' => 'Saldo adicionado com sucesso.']);
            }

        } else {
            Log::warning('Dados incompletos recebidos.', ['post_data' => $_POST]);

            return json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        }
    }

    /**
     * Verifica se deve ativar o bloqueio automático de jogos de habilidade após vitória
     */
    private function checkAutoBlockOnWin($user, $winAmount)
    {
        // Verificar se o bloqueio automático está habilitado
        if (! config('kyc.auto_block_skill_games_on_win', false)) {
            return;
        }

        // Verificar se o usuário é influencer e se eles são isentos
        if ($user->is_demo_agent && config('kyc.exempt_influencers_from_auto_block', true)) {
            Log::debug('Usuário é influencer - ignorando regras de bloqueio automático', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'is_demo_agent' => true,
                'win_amount' => $winAmount,
                'exempt_config' => true,
            ]);

            return; // Influencers são isentos das regras
        }

        // Log para confirmar que é um jogo de habilidade
        Log::debug('Verificando bloqueio automático para jogo de habilidade', [
            'user_id' => $user->id,
            'win_amount' => $winAmount,
            'controller' => 'VGamesController',
            'game_type' => 'skill_game_exclusive',
            'is_demo_agent' => false,
        ]);

        // Verificar se o usuário já está bloqueado
        if ($user->block_skill_games) {
            return;
        }

        // Verificar valor mínimo de vitória
        $minWinAmount = config('kyc.auto_block_min_win_amount', 0);
        if ($winAmount < $minWinAmount) {
            Log::debug("Vitória de {$winAmount} abaixo do mínimo {$minWinAmount} para bloqueio automático");

            return;
        }

        // Verificar vitórias consecutivas necessárias
        $consecutiveWinsRequired = config('kyc.auto_block_consecutive_wins', 1);

        if ($consecutiveWinsRequired <= 1) {
            // Bloquear imediatamente
            $this->activateAutoBlock($user, $winAmount, 1);
        } else {
            // Verificar vitórias consecutivas
            $recentWins = $this->getRecentConsecutiveWins($user->id);

            if ($recentWins >= $consecutiveWinsRequired) {
                $this->activateAutoBlock($user, $winAmount, $recentWins);
            } else {
                Log::debug("Usuário {$user->id} tem {$recentWins} vitórias consecutivas, necessárias {$consecutiveWinsRequired}");
            }
        }
    }

    /**
     * Ativa o controle automático para o usuário (progressão de dificuldade)
     */
    private function activateAutoBlock($user, $winAmount, $consecutiveWins)
    {
        // Nova lógica: aumentar dificuldade progressivamente
        $this->increaseSkillGamesDifficulty($user, $winAmount);

        Log::info('Progressão de dificuldade ativada', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'win_amount' => $winAmount,
            'consecutive_wins' => $consecutiveWins,
            'timestamp' => now(),
            'reason' => 'auto_difficulty_progression_on_skill_game_win',
            'controller' => 'VGamesController',
        ]);
    }

    /**
     * Aumenta progressivamente a dificuldade dos jogos de habilidade a cada vitória
     */
    private function increaseSkillGamesDifficulty($user, $winAmount)
    {
        $currentDifficulty = $user->skill_games_difficulty ?? 'easy';

        Log::debug('VGamesController - Aumentando dificuldade dos jogos de habilidade', [
            'user_id' => $user->id,
            'current_difficulty' => $currentDifficulty,
            'win_amount' => $winAmount,
        ]);

        switch ($currentDifficulty) {
            case 'easy': // Fácil → Médio
                $user->update(['skill_games_difficulty' => 'medium']);
                Log::info('Dificuldade dos jogos de habilidade aumentada: Fácil → Médio', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'previous_difficulty' => 'easy',
                    'new_difficulty' => 'medium',
                    'win_amount' => $winAmount,
                    'controller' => 'VGamesController',
                ]);
                break;

            case 'medium': // Médio → Difícil
                $user->update(['skill_games_difficulty' => 'hard']);
                Log::info('Dificuldade dos jogos de habilidade aumentada: Médio → Difícil', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'previous_difficulty' => 'medium',
                    'new_difficulty' => 'hard',
                    'win_amount' => $winAmount,
                    'controller' => 'VGamesController',
                ]);
                break;

            case 'hard': // Já está no máximo - BLOQUEAR USUÁRIO
                $user->update(['block_skill_games' => true]);
                Log::info('Usuário bloqueado após ganhar no nível difícil dos jogos de habilidade', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'difficulty' => 'hard',
                    'win_amount' => $winAmount,
                    'controller' => 'VGamesController',
                    'action' => 'blocked_after_hard_win',
                    'reason' => 'Ganhou no nível difícil - bloqueio total ativado',
                ]);
                break;

            default:
                Log::warning('Dificuldade dos jogos de habilidade inválida encontrada', [
                    'user_id' => $user->id,
                    'invalid_difficulty' => $currentDifficulty,
                    'controller' => 'VGamesController',
                ]);
                $user->update(['skill_games_difficulty' => 'medium']); // Reset para médio
                break;
        }
    }

    /**
     * Função antiga mantida para compatibilidade (não utilizada)
     */
    private function increaseSkillGamesMinAmount($user, $winAmount, $consecutiveWins)
    {
        // Obter valor atual ou usar padrão
        $currentMinAmount = $user->skill_games_min_amount ?? config('kyc.default_skill_games_min_amount', 10.00);

        // Calcular novo valor com multiplicador
        $multiplier = config('kyc.min_amount_increase_multiplier', 2.0);
        $newMinAmount = $currentMinAmount * $multiplier;

        // Verificar limite máximo
        $maxAmount = config('kyc.max_skill_games_min_amount', 1000.00);
        if ($maxAmount > 0 && $newMinAmount > $maxAmount) {
            $newMinAmount = $maxAmount;

            // Se já atingiu o máximo, bloquear completamente
            if ($currentMinAmount >= $maxAmount) {
                $user->update(['block_skill_games' => true]);

                Log::info('Valor mínimo já no máximo - bloqueio ativado', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'current_min_amount' => $currentMinAmount,
                    'max_amount' => $maxAmount,
                    'win_amount' => $winAmount,
                    'consecutive_wins' => $consecutiveWins,
                    'timestamp' => now(),
                    'reason' => 'max_min_amount_reached',
                ]);

                return;
            }
        }

        // Atualizar valor mínimo
        $user->update(['skill_games_min_amount' => $newMinAmount]);

        // Log da ação para auditoria
        Log::info('Valor mínimo aumentado automaticamente', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_min_amount' => $currentMinAmount,
            'new_min_amount' => $newMinAmount,
            'multiplier' => $multiplier,
            'win_amount' => $winAmount,
            'consecutive_wins' => $consecutiveWins,
            'timestamp' => now(),
            'reason' => 'auto_increase_on_skill_game_win',
        ]);
    }

    /**
     * Conta vitórias consecutivas recentes do usuário em jogos de habilidade
     */
    private function getRecentConsecutiveWins($userId)
    {
        // Buscar as últimas 10 transações do usuário APENAS em jogos de habilidade (exclusive)
        $recentOrders = Order::where('user_id', $userId)
            ->where('providers', 'exclusive')  // ← APENAS jogos de habilidade
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['type', 'providers', 'created_at']);

        $consecutiveWins = 0;

        Log::debug('Analisando vitórias consecutivas em jogos de habilidade', [
            'user_id' => $userId,
            'recent_orders_count' => $recentOrders->count(),
            'provider_filter' => 'exclusive',
        ]);

        // Contar vitórias consecutivas a partir da mais recente
        foreach ($recentOrders as $order) {
            if ($order->type === 'win') {
                $consecutiveWins++;
                Log::debug("Vitória consecutiva #{$consecutiveWins} encontrada", [
                    'provider' => $order->providers,
                    'created_at' => $order->created_at,
                ]);
            } else {
                // Parar na primeira não-vitória
                Log::debug("Sequência interrompida por: {$order->type}");
                break;
            }
        }

        return $consecutiveWins;
    }
}
