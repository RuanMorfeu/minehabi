<?php

namespace App\Http\Controllers\Api\Providers;

use App\Helpers\Core as Helper;
use App\Http\Controllers\Controller;
use App\Models\GameExclusive2;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VGames2Controller extends Controller
{
    /**
     * Show game - com sistema de token seguro (igual VGames original)
     */
    public function show(string $slug, $aposta = null)
    {
        $game = GameExclusive2::whereActive(1)->where('uuid', $slug)->first();

        if (! $game) {
            return back()->with('error', 'UUID Errado');
        }

        $user = auth('api')->user();
        $userId = $user ? $user->id : 0;

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

        $isInfluencer = $user && $user->is_demo_agent;
        $isDemoAgent = false; // Keep false for influencers to use normal URL

        // Configurações do jogo baseadas no tipo de usuário e tipo de jogo
        if ($game->game_type === 'pacman') {
            $lives = $isInfluencer && ! is_null($game->influencer_lives) ? $game->influencer_lives : ($game->lives ?? 3);
            $coinRate = $isInfluencer && ! is_null($game->influencer_coin_rate) ? $game->influencer_coin_rate : ($game->coin_rate ?? 0.01);
            $metaMultiplier = $isInfluencer && ! is_null($game->influencer_meta_multiplier) ? $game->influencer_meta_multiplier : ($game->meta_multiplier ?? 4);
            $ghostPoints = $isInfluencer && ! is_null($game->influencer_ghost_points) ? $game->influencer_ghost_points : ($game->ghost_points ?? 0.1);

            // Prioridade para dificuldade: 1) Usuário personalizado, 2) Influencer, 3) Aposta ≥ €3, 4) Jogo padrão
            // Converter skill_games_difficulty (easy/medium/hard) para números (1/2/3) para compatibilidade
            $userDifficulty = null;
            if ($user && ! is_null($user->skill_games_difficulty)) {
                $userDifficulty = match ($user->skill_games_difficulty) {
                    'easy' => 1,
                    'medium' => 2,
                    'hard' => 3,
                    default => null
                };
            }

            $difficulty = $userDifficulty
                ?? ($isInfluencer && ! is_null($game->influencer_difficulty)
                    ? $game->influencer_difficulty
                    : (($aposta && $aposta >= 3.00) ? 3 : ($game->difficulty ?? 1)));
        } elseif ($game->game_type === 'jetpack') {
            $coinRate = $isInfluencer && ! is_null($game->influencer_coin_rate) ? $game->influencer_coin_rate : ($game->coin_rate ?? 0.01);
            $metaMultiplier = $isInfluencer && ! is_null($game->influencer_meta_multiplier) ? $game->influencer_meta_multiplier : ($game->meta_multiplier ?? 4);

            // Prioridade para dificuldade: 1) Usuário personalizado, 2) Influencer, 3) Aposta ≥ €3, 4) Jogo padrão
            $userJetpackDifficulty = null;
            if ($user && ! is_null($user->skill_games_difficulty)) {
                $userJetpackDifficulty = $user->skill_games_difficulty; // Usar diretamente (easy/medium/hard)
            }

            $jetpackDifficulty = $userJetpackDifficulty
                ?? ($isInfluencer && ! is_null($game->influencer_jetpack_difficulty)
                    ? $game->influencer_jetpack_difficulty
                    : (($aposta && $aposta >= 3.00) ? 'hard' : ($game->jetpack_difficulty ?? 'medium')));

            // Não mapear para valores específicos - deixar o frontend decidir
            // O sistema dinâmico no JavaScript vai usar essas configurações
            $playerSpeed = 600; // Valor padrão, será sobrescrito pelo sistema dinâmico
            $missileSpeed = 1000; // Valor padrão, será sobrescrito pelo sistema dinâmico
            $spawnRateObstacles = 10; // Valor padrão, será sobrescrito pelo sistema dinâmico
        } elseif ($game->game_type === 'angry') {
            $coinMultiplier = $isInfluencer && ! is_null($game->influencer_coin_multiplier) ? $game->influencer_coin_multiplier : ($game->coin_multiplier ?? 1);
            $metaMultiplier = $isInfluencer && ! is_null($game->influencer_meta_multiplier) ? $game->influencer_meta_multiplier : ($game->meta_multiplier ?? 4);

            // Prioridade para dificuldade: 1) Usuário personalizado, 2) Influencer, 3) Aposta ≥ €3, 4) Jogo padrão
            $userAngryDifficulty = null;
            if ($user && ! is_null($user->skill_games_difficulty)) {
                $userAngryDifficulty = match ($user->skill_games_difficulty) {
                    'easy' => 1,
                    'medium' => 2,
                    'hard' => 3,
                    default => null
                };
            }

            $gameDifficulty = $userAngryDifficulty
                ?? ($isInfluencer && ! is_null($game->influencer_game_difficulty)
                    ? $game->influencer_game_difficulty
                    : (($aposta && $aposta >= 3.00) ? 3 : ($game->game_difficulty ?? 1)));
        } else {
            // Fallback para compatibilidade
            $coinMultiplier = $isInfluencer && ! is_null($game->influencer_coin_multiplier) ? $game->influencer_coin_multiplier : ($game->coin_multiplier ?? 1);
            $metaMultiplier = $isInfluencer && ! is_null($game->influencer_meta_multiplier) ? $game->influencer_meta_multiplier : ($game->meta_multiplier ?? 4);
            $gameDifficulty = $isInfluencer && ! is_null($game->influencer_game_difficulty) ? $game->influencer_game_difficulty : ($game->game_difficulty ?? 1);
        }

        $game->increment('views', 1);
        $uniqueCode = 'source_'.Str::uuid();

        // Criar token seguro com dados do usuário
        $token = Helper::MakeToken([
            'id' => $userId,
            'game' => $slug,
            'transaction_id' => $uniqueCode,
            'aposta' => $aposta ?? 0, // Incluir valor da aposta no token
        ]);

        // Processar aposta se usuário autenticado
        if ($aposta && $userId > 0) {
            $user = User::where('id', $userId)->first();
            $wallet = $user->wallet;

            // Verificar se o usuário tem jogos de habilidade bloqueados
            if ($user->block_skill_games) {
                return response()->json([
                    'error' => 'Jogos de habilidade bloqueados para este usuário',
                    'blocked' => true,
                ], 403);
            }

            // Verificar valor mínimo - usar o valor do jogo ou personalizado do usuário
            $minAmount = $user->skill_games_min_amount ?? $game->min_amount;
            if ($aposta < $minAmount) {
                return response()->json([
                    'error' => 'Valor mínimo para apostas é € '.number_format($minAmount, 2, ',', '.'),
                ], 400);
            }

            // Verificar valor máximo - usar o valor do jogo ou personalizado do usuário
            $maxAmount = $user->skill_games_max_amount ?? $game->max_amount;
            if ($maxAmount && $aposta > $maxAmount) {
                return response()->json([
                    'error' => 'Valor máximo para apostas é € '.number_format($maxAmount, 2, ',', '.'),
                ], 400);
            }

            if ($aposta > $wallet->total_balance) {
                return response()->json(['error' => 'Saldo insuficiente!'], 400);
            }

            // Lógica de débito da carteira (simplificada)
            $balanceType = 'balance';
            if ($wallet->balance >= $aposta) {
                $balanceType = 'balance';
            } elseif ($wallet->balance_withdrawal >= $aposta) {
                $balanceType = 'balance_withdrawal';
            } elseif ($wallet->balance_bonus >= $aposta) {
                $balanceType = 'balance_bonus';
            }

            Wallet::where('user_id', $userId)->decrement($balanceType, (float) $aposta);

            Order::create([
                'user_id' => $userId,
                'transaction_id' => $uniqueCode,
                'game' => $game->name,
                'game_uuid' => $game->id,
                'type' => 'bet',
                'type_money' => $balanceType,
                'amount' => $aposta,
                'providers' => 'exclusive2',
                'refunded' => false,
                'status' => true,
            ]);
        }

        // Se não autenticado, modo demo
        if ($userId == 0) {
            $isDemoAgent = true;
            // Modificar UUID do jogo para versão demo
            $game->uuid = 'demo'.$game->uuid;
        }

        // Monta URL com token e parâmetros seguros
        $indexFile = $game->game_type === 'pacman' ? 'index.html' : 'index.php';

        // Se é modo demo, usar pasta demo-exclusive-games-2, senão usar exclusive-games-2
        $gameFolder = $isDemoAgent ? 'demo-exclusive-games-2' : 'exclusive-games-2';
        $baseUrl = url('/'.$gameFolder.'/'.$game->game_type.'/'.$indexFile);

        // Parâmetros base
        $params = [
            'baseurl' => $isDemoAgent ? url('/demo-game2') : url('/api/vgames2'),
            'token' => $token,
            'aposta' => $aposta ?? 0,
            'is_demo_agent' => $isDemoAgent ? 1 : 0,
            'disable_over_meta' => $isInfluencer ? 1 : 0, // Separate parameter for influencers
        ];

        // Adicionar parâmetros específicos por tipo de jogo
        if ($game->game_type === 'pacman') {
            $params['coin_rate'] = $coinRate;
            $params['meta_multiplier'] = $metaMultiplier;
            $params['ghost_points'] = $ghostPoints;
            $params['lives'] = $lives;
            $params['difficulty'] = $difficulty;
        } elseif ($game->game_type === 'jetpack') {
            $params['coin_rate'] = $coinRate;
            $params['meta_multiplier'] = $metaMultiplier;
            $params['player_speed'] = $playerSpeed;
            $params['missile_speed'] = $missileSpeed;
            $params['spawn_rate_obstacles'] = $spawnRateObstacles;
            $params['jetpack_difficulty'] = $jetpackDifficulty;
            $params['is_demo_agent'] = $isInfluencer ? '1' : '0';
        } elseif ($game->game_type === 'angry') {
            $params['coin_multiplier'] = $coinMultiplier;
            $params['meta_multiplier'] = $metaMultiplier;
            $params['game_difficulty'] = $gameDifficulty;
        }

        $gameUrl = $baseUrl.'?'.http_build_query($params);

        return response()->json([
            'game' => $game,
            'gameUrl' => $gameUrl,
            'token' => $token,
            'aposta' => $aposta ?? 0,
        ]);
    }

    /**
     * Info endpoint - com validação de token flexível
     */
    public function info(string $slug, Request $request)
    {
        $game = GameExclusive2::whereActive(1)->where('uuid', $slug)->first();
        if (! $game) {
            return response()->json(['error' => 'Jogo não encontrado'], 404);
        }

        // Tentar validar token se fornecido
        $token = $request->query('token');
        $userId = 0;
        $fakeUser = true;
        $tokenValidated = false;

        if ($token) {
            $tokenData = Helper::DecToken($token);
            if (isset($tokenData['status']) && $tokenData['status']) {
                $userId = $tokenData['id'] ?? 0;
                $gameSlug = $tokenData['game'] ?? '';

                // Se token válido e para este jogo, usar dados do token
                if ($gameSlug === $slug && $userId > 0) {
                    $user = User::find($userId);
                    $fakeUser = $user ? $user->is_demo_agent : true;
                    $tokenValidated = true;
                }
            }
        }

        // Se não tem token válido, usar autenticação padrão
        if (! $tokenValidated) {
            $user = auth('api')->user();
            $userId = $user ? $user->id : 0;
            $fakeUser = $userId == 0 || ($user && $user->is_demo_agent);
        }

        // Configurações dinâmicas baseadas na tabela game_exclusive2s
        if ($game->game_type === 'angry') {
            // Detectar se o usuário é influencer/demo (mesma lógica do show)
            $isInfluencer = false;
            if ($userId > 0) {
                $userRecord = User::find($userId);
                $isInfluencer = $userRecord ? (bool) $userRecord->is_demo_agent : false;
            }

            // Preservar casas decimais dos multiplicadores
            $coinMultiplier = $isInfluencer
                ? (float) ($game->influencer_coin_multiplier ?? $game->coin_multiplier ?? 1)
                : (float) ($game->coin_multiplier ?? 1);

            $metaMultiplier = $isInfluencer
                ? (float) ($game->influencer_meta_multiplier ?? $game->meta_multiplier ?? 4)
                : (float) ($game->meta_multiplier ?? 4);

            $gameDifficulty = $isInfluencer
                ? (float) ($game->influencer_game_difficulty ?? $game->game_difficulty ?? 1)
                : (float) ($game->game_difficulty ?? 1);

            $settings = [
                'coin_multiplier' => $coinMultiplier,
                'meta_multiplier' => $metaMultiplier,
                'game_difficulty' => $gameDifficulty,
            ];

            // Log detalhado das configurações do Angry Birds
            Log::info('Angry Birds - Configurações carregadas:', [
                'game_id' => $game->id,
                'game_name' => $game->name,
                'coin_multiplier_raw' => $game->coin_multiplier,
                'meta_multiplier_raw' => $game->meta_multiplier,
                'game_difficulty_raw' => $game->game_difficulty,
                'settings_final' => $settings,
                'is_influencer' => $isInfluencer,
            ]);
        } else {
            // Configurações padrão para outros jogos
            $settings = [
                'coin_multiplier' => $fakeUser ? 1 : 4,
                'meta_multiplier' => $fakeUser ? 1 : 4,
                'game_difficulty' => 1,
            ];
        }

        // Replicar exatamente como a plataforma original
        $lastBalance = null;
        if ($userId > 0) {
            $lastTransaction = Order::where([
                ['user_id', $userId],
                ['game', $game->name],
                ['type', 'bet'],
            ])->orderBy('id', 'DESC')->first();

            if ($lastTransaction) {
                // Criar objeto similar ao Balance da plataforma original
                $lastBalance = (object) [
                    'id' => $lastTransaction->id,
                    'user_id' => $lastTransaction->user_id,
                    'amount' => $lastTransaction->amount,
                    'origin' => 'game',
                    'type' => 'money_out',
                    'status' => 'paid',
                    'game' => $lastTransaction->game,
                    'created_at' => $lastTransaction->created_at,
                    'updated_at' => $lastTransaction->updated_at,
                ];
            }
        }

        // Se não há transação anterior, usar valor da aposta do token
        if ($lastBalance === null) {
            // Extrair aposta do token (como na plataforma original)
            $defaultAmount = 1.0;
            if ($token && $tokenValidated) {
                $tokenData = Helper::DecToken($token);
                // Usar valor da aposta que está no token
                $defaultAmount = $tokenData['aposta'] ?? 1.0;
            }

            $lastBalance = (object) [
                'amount' => (float) $defaultAmount,
            ];
        }

        Log::info('VGames2Controller - Info como plataforma original:', [
            'game' => $slug,
            'user_id' => $userId,
            'fake_user' => $fakeUser,
            'last_balance_found' => $lastBalance !== null,
            'last_balance_amount' => $lastBalance ? $lastBalance->amount : null,
            'token_provided' => ! empty($token),
            'token_validated' => $tokenValidated,
            'auth_method' => $tokenValidated ? 'token' : 'session',
        ]);

        return response()->json([
            'last_balance' => $lastBalance,
            'settings' => $settings,
            'fake' => 0, // Sempre 0 como na plataforma original
        ]);
    }

    /**
     * Win endpoint - com validação de token seguro
     */
    public function win(Request $request, string $slug)
    {
        // Validar token
        $token = $request->input('token');
        if (! $token) {
            return response()->json(['error' => 'Token obrigatório'], 401);
        }

        $tokenData = Helper::DecToken($token);
        if (! isset($tokenData['status']) || ! $tokenData['status']) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $game = GameExclusive2::whereActive(1)->where('uuid', $slug)->first();
        if (! $game) {
            return response()->json(['error' => 'Jogo não encontrado'], 404);
        }

        $userId = $tokenData['id'] ?? 0;
        $gameSlug = $tokenData['game'] ?? '';

        if ($gameSlug !== $slug) {
            return response()->json(['error' => 'Token inválido para este jogo'], 401);
        }

        $ganho = $request->input('ganho', 0);

        // Verificar se é modo demo (usuário não autenticado)
        $isDemoMode = ($userId == 0);

        if ($ganho > 0 && $userId > 0 && ! $isDemoMode) {
            $user = User::find($userId);
            if ($user) {
                $wallet = $user->wallet;
                $wallet->increment('balance', $ganho);

                // Verificar se usuário é influencer
                $isInfluencer = $user->is_demo_agent ?? false;

                $settings = [
                    'player_speed' => $playerSpeed ?? 600,
                    'missile_speed' => $missileSpeed ?? 1000,
                    'coin_rate' => $coinRate ?? 0.01,
                    'meta_multiplier' => $metaMultiplier ?? 4,
                    'spawn_rate_obstacles' => $spawnRateObstacles ?? 10,
                    'jetpack_difficulty' => $jetpackDifficulty ?? 'medium', // Enviar dificuldade para o frontend
                    'is_demo_agent' => $isInfluencer, // Para sistema dinâmico
                ];

                Order::create([
                    'user_id' => $user->id,
                    'transaction_id' => 'win_'.Str::uuid(),
                    'game' => $game->name,
                    'game_uuid' => $game->id,
                    'type' => 'win',
                    'type_money' => 'balance',
                    'amount' => $ganho,
                    'providers' => 'exclusive2',
                    'refunded' => false,
                    'status' => true,
                ]);
            }
        }

        // Verificar se deve ativar bloqueio automático após vitória (fora da condição)
        if ($ganho > 0 && $userId > 0) {
            $user = $user ?? User::find($userId);
            if ($user) {
                Log::debug('VGames2Controller - Chamando checkAutoBlockOnWin', [
                    'user_id' => $user->id,
                    'ganho' => $ganho,
                    'user_email' => $user->email,
                    'current_skill_games_min_amount' => $user->skill_games_min_amount,
                ]);
                $this->checkAutoBlockOnWin($user, $ganho);
            }
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Lost endpoint - com validação de token seguro
     */
    public function lost(Request $request, string $slug)
    {
        // Validar token
        $token = $request->input('token');
        if (! $token) {
            return response()->json(['error' => 'Token obrigatório'], 401);
        }

        $tokenData = Helper::DecToken($token);
        if (! isset($tokenData['status']) || ! $tokenData['status']) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        $game = GameExclusive2::whereActive(1)->where('uuid', $slug)->first();
        if (! $game) {
            return response()->json(['error' => 'Jogo não encontrado'], 404);
        }

        $userId = $tokenData['id'] ?? 0;
        $gameSlug = $tokenData['game'] ?? '';

        if ($gameSlug !== $slug) {
            return response()->json(['error' => 'Token inválido para este jogo'], 401);
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Modal para carregar dados do jogo
     */
    public function modal(string $slug)
    {
        $game = GameExclusive2::whereActive(1)->where('uuid', $slug)->first();

        if (! $game) {
            // Se for requisição AJAX/API, retornar JSON
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['error' => 'Jogo não encontrado'], 404);
            }

            // Se for navegador, redirecionar para home
            return redirect('/');
        }

        // Verificar se o usuário está autenticado
        $user = auth('api')->user();

        // Verificar se o usuário tem jogos de habilidade bloqueados
        if ($user && $user->block_skill_games) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'error' => 'Jogos de habilidade bloqueados para este usuário',
                    'blocked' => true,
                ], 403);
            }

            return redirect('/');
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

        // Se for requisição AJAX/API, retornar JSON
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'game' => $game,
                'controller' => 'VGames2Controller',
            ]);
        }

        // Se for navegador (quando jogo redireciona após terminar),
        // redirecionar para a interface SPA do jogo específico
        return redirect("/modal2/{$slug}");
    }

    /**
     * Verifica se deve aplicar bloqueio automático após vitória
     */
    private function checkAutoBlockOnWin($user, $winAmount)
    {
        Log::debug('VGames2Controller - Iniciando checkAutoBlockOnWin', [
            'user_id' => $user->id,
            'win_amount' => $winAmount,
            'user_email' => $user->email,
        ]);

        // Verificar se o bloqueio automático está habilitado
        $autoBlockEnabled = config('kyc.auto_block_skill_games_on_win', false);
        Log::debug('VGames2Controller - Config auto_block_skill_games_on_win', [
            'enabled' => $autoBlockEnabled,
        ]);

        if (! $autoBlockEnabled) {
            Log::debug('VGames2Controller - Bloqueio automático desabilitado');

            return;
        }

        // Verificar se o usuário é influencer e se eles são isentos
        $isInfluencer = $user->is_demo_agent;
        $exemptInfluencers = config('kyc.exempt_influencers_from_auto_block', true);

        Log::debug('VGames2Controller - Verificando influencer', [
            'is_demo_agent' => $isInfluencer,
            'exempt_config' => $exemptInfluencers,
        ]);

        if ($isInfluencer && $exemptInfluencers) {
            Log::debug('Usuário é influencer - ignorando regras de bloqueio automático', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'is_demo_agent' => true,
                'win_amount' => $winAmount,
                'exempt_config' => true,
            ]);

            return; // Influencers são isentos das regras
        }

        // Log para confirmar que é um jogo da plataforma (exclusive2)
        Log::debug('Verificando bloqueio automático para jogo da plataforma', [
            'user_id' => $user->id,
            'win_amount' => $winAmount,
            'controller' => 'VGames2Controller',
            'game_type' => 'exclusive2',
            'is_demo_agent' => false,
        ]);

        // Verificar se o usuário já está bloqueado
        Log::debug('VGames2Controller - Verificando se usuário já está bloqueado', [
            'user_id' => $user->id,
            'block_skill_games' => $user->block_skill_games,
        ]);

        if ($user->block_skill_games) {
            Log::debug('VGames2Controller - Usuário já está bloqueado, saindo');

            return;
        }

        // Verificar valor mínimo de vitória
        $minWinAmount = config('kyc.auto_block_min_win_amount', 0);
        Log::debug('VGames2Controller - Verificando valor mínimo de vitória', [
            'win_amount' => $winAmount,
            'min_win_amount' => $minWinAmount,
        ]);

        if ($winAmount < $minWinAmount) {
            Log::debug("VGames2Controller - Vitória de {$winAmount} abaixo do mínimo {$minWinAmount} para bloqueio automático");

            return;
        }

        // Verificar vitórias consecutivas necessárias
        $consecutiveWinsRequired = config('kyc.auto_block_consecutive_wins', 1);
        Log::debug('VGames2Controller - Verificando vitórias consecutivas', [
            'consecutive_wins_required' => $consecutiveWinsRequired,
        ]);

        // Nova lógica: aumentar dificuldade do Pacman a cada vitória
        Log::debug('VGames2Controller - Aplicando nova lógica de dificuldade progressiva');
        $this->increasePacmanDifficulty($user, $winAmount);
    }

    /**
     * Aumenta progressivamente a dificuldade dos jogos de habilidade a cada vitória
     * Fácil → Médio → Difícil → Bloqueio completo
     * Aplicado para: Pacman, Subway Surfers, Jetpack
     */
    private function increasePacmanDifficulty($user, $winAmount)
    {
        Log::debug('VGames2Controller - Iniciando increasePacmanDifficulty', [
            'user_id' => $user->id,
            'current_skill_games_difficulty' => $user->skill_games_difficulty,
            'win_amount' => $winAmount,
        ]);

        // Obter dificuldade atual (padrão é 'easy')
        $currentDifficulty = $user->skill_games_difficulty ?? 'easy';

        Log::debug('VGames2Controller - Dificuldade atual do usuário', [
            'user_id' => $user->id,
            'current_difficulty' => $currentDifficulty,
            'win_amount' => $winAmount,
        ]);

        switch ($currentDifficulty) {
            case 'easy': // Fácil → Médio
                $newDifficulty = 'medium';
                $user->update(['skill_games_difficulty' => $newDifficulty]);

                Log::info('Dificuldade dos jogos de habilidade aumentada: Fácil → Médio', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'previous_difficulty' => $currentDifficulty,
                    'new_difficulty' => $newDifficulty,
                    'win_amount' => $winAmount,
                    'timestamp' => now(),
                    'reason' => 'skill_games_difficulty_progression',
                    'controller' => 'VGames2Controller',
                ]);
                break;

            case 'medium': // Médio → Difícil
                $newDifficulty = 'hard';
                $user->update(['skill_games_difficulty' => $newDifficulty]);

                Log::info('Dificuldade dos jogos de habilidade aumentada: Médio → Difícil', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'previous_difficulty' => $currentDifficulty,
                    'new_difficulty' => $newDifficulty,
                    'win_amount' => $winAmount,
                    'timestamp' => now(),
                    'reason' => 'skill_games_difficulty_progression',
                    'controller' => 'VGames2Controller',
                ]);
                break;

            case 'hard': // Difícil → Bloqueio completo
                $user->update(['block_skill_games' => true]);

                Log::info('Usuário ganhou no nível Difícil - Jogos de habilidade bloqueados', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'difficulty_when_blocked' => $currentDifficulty,
                    'win_amount' => $winAmount,
                    'timestamp' => now(),
                    'reason' => 'won_on_hard_difficulty',
                    'controller' => 'VGames2Controller',
                ]);
                break;

            default:
                Log::warning('Dificuldade inválida encontrada', [
                    'user_id' => $user->id,
                    'invalid_difficulty' => $currentDifficulty,
                ]);
                break;
        }
    }

    /**
     * Aumenta o valor mínimo para jogos de habilidade
     */
    private function increaseSkillGamesMinAmount($user, $winAmount, $consecutiveWins)
    {
        Log::debug('VGames2Controller - Iniciando increaseSkillGamesMinAmount', [
            'user_id' => $user->id,
            'current_skill_games_min_amount_raw' => $user->skill_games_min_amount,
            'win_amount' => $winAmount,
        ]);

        // Obter valor atual ou usar padrão
        $defaultMinAmount = config('kyc.default_skill_games_min_amount', 2.00);
        $currentMinAmount = ($user->skill_games_min_amount && $user->skill_games_min_amount > 0)
            ? $user->skill_games_min_amount
            : $defaultMinAmount;

        Log::debug('VGames2Controller - Valores para cálculo', [
            'user_skill_games_min_amount' => $user->skill_games_min_amount,
            'default_from_config' => $defaultMinAmount,
            'current_min_amount_used' => $currentMinAmount,
        ]);

        // Calcular novo valor com multiplicador
        $multiplier = config('kyc.min_amount_increase_multiplier', 3.0);
        $newMinAmount = $currentMinAmount * $multiplier;

        Log::debug('VGames2Controller - Cálculo do novo valor', [
            'current_amount' => $currentMinAmount,
            'multiplier' => $multiplier,
            'calculated_new_amount' => $newMinAmount,
        ]);

        // Verificar limite máximo
        $maxAmount = config('kyc.max_skill_games_min_amount', 6.00);
        if ($maxAmount > 0 && $newMinAmount > $maxAmount) {
            $newMinAmount = $maxAmount;

            // Se já atingiu o máximo, bloquear completamente
            if ($currentMinAmount >= $maxAmount) {
                $user->update(['block_skill_games' => true]);

                Log::info('Valor mínimo já no máximo - bloqueio ativado para jogos da plataforma', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'current_min_amount' => $currentMinAmount,
                    'max_amount' => $maxAmount,
                    'win_amount' => $winAmount,
                    'consecutive_wins' => $consecutiveWins,
                    'timestamp' => now(),
                    'reason' => 'max_min_amount_reached_exclusive2',
                    'controller' => 'VGames2Controller',
                ]);

                return;
            }
        }

        // Atualizar valor mínimo
        Log::debug('VGames2Controller - Atualizando valor no banco de dados', [
            'user_id' => $user->id,
            'new_min_amount_to_save' => $newMinAmount,
        ]);

        $updateResult = $user->update(['skill_games_min_amount' => $newMinAmount]);

        Log::debug('VGames2Controller - Resultado da atualização', [
            'update_success' => $updateResult,
            'user_skill_games_min_amount_after_update' => $user->fresh()->skill_games_min_amount,
        ]);

        // Log da ação para auditoria
        Log::info('Valor mínimo aumentado automaticamente para jogos da plataforma', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_min_amount' => $currentMinAmount,
            'new_min_amount' => $newMinAmount,
            'multiplier' => $multiplier,
            'win_amount' => $winAmount,
            'consecutive_wins' => $consecutiveWins,
            'timestamp' => now(),
            'reason' => 'auto_increase_on_exclusive2_game_win',
            'controller' => 'VGames2Controller',
            'update_result' => $updateResult,
        ]);
    }

    /**
     * Conta vitórias consecutivas recentes do usuário em jogos da plataforma (exclusive2)
     */
    private function getRecentConsecutiveWins($userId)
    {
        // Buscar as últimas 10 transações do usuário APENAS em jogos da plataforma (exclusive2)
        $recentOrders = Order::where('user_id', $userId)
            ->where('providers', 'exclusive2')  // ← APENAS jogos da plataforma
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['type', 'providers', 'created_at']);

        $consecutiveWins = 0;

        Log::debug('Analisando vitórias consecutivas em jogos da plataforma', [
            'user_id' => $userId,
            'recent_orders_count' => $recentOrders->count(),
            'provider_filter' => 'exclusive2',
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

        Log::debug("Total de vitórias consecutivas em jogos da plataforma: {$consecutiveWins}");

        return $consecutiveWins;
    }
}
