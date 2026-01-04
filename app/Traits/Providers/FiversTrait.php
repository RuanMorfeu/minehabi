<?php

namespace App\Traits\Providers;

use App\Helpers\Core;
use App\Helpers\Core as Helper;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\GGRGamesFiver;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Missions\MissionTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait FiversTrait
{
    use MissionTrait;

    /**
     * @var string
     */
    protected static $agentCode;

    protected static $agentToken;

    protected static $agentSecretKey;

    protected static $apiEndpoint;

    /**
     * @return void
     */
    public static function getCredentials(): bool
    {
        $setting = GamesKey::first();

        self::$agentCode = $setting->getAttributes()['agent_code'];
        self::$agentToken = $setting->getAttributes()['agent_token'];
        self::$agentSecretKey = $setting->getAttributes()['agent_secret_key'];
        self::$apiEndpoint = $setting->getAttributes()['api_endpoint'];

        return true;
    }

    public static function GetAllGames()
    {
        if (self::getCredentials()) {

        }
    }

    /**
     * @return void
     */
    public static function UpdateRTP($rtp, $provider, $userId)
    {
        if (self::getCredentials()) {
            $postArray = [
                'method' => 'control_rtp',
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
                'provider_code' => $provider,
                'user_code' => $userId.'',
                'rtp' => $rtp,
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            if ($response->successful()) {
                \DB::table('debug')->insert(['text' => json_encode($response->json())]);
            }
        }
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function createUser()
    {
        if (self::getCredentials()) {

            $postArray = [
                'method' => 'user_create',
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
                'user_code' => auth('api')->id(),
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            if ($response->successful()) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Iniciar Jogo
     * Metodo responsavel para iniciar o jogo
     */
    public static function GameLaunchFivers($provider_code, $game_code, $lang, $userId)
    {
        if (self::getCredentials()) {

            $postArray = [
                'method' => 'game_launch',
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
                'user_code' => strval($userId),
                'provider_code' => $provider_code,
                'game_code' => $game_code,
                'lang' => $lang,
            ];

            $response = Http::post(self::$apiEndpoint, $postArray);

            // dd([$provider_code, $game_code, $lang, $userId, $response->json()]);
            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] == 0) {
                    if ($data['msg'] == 'Invalid User') {
                        if (self::createUser()) {
                            return self::GameLaunchFivers($provider_code, $game_code, $lang, $userId);
                        }
                    }
                } else {
                    return $data;
                }
            } else {
                return false;
            }
        }

    }

    /**
     * Get FIvers Balance
     *
     * @return false|void
     */
    public static function getFiversUserDetail()
    {
        if (self::getCredentials()) {
            $dataArray = [
                'method' => 'call_players',
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
            ];

            $response = Http::post(self::$apiEndpoint, $dataArray);

            if ($response->successful()) {
                $data = $response->json();

                dd($data);
            } else {
                return false;
            }
        }

    }

    /**
     * Get FIvers Balance
     *
     * @param  $provider_code
     * @param  $game_code
     * @param  $lang
     * @param  $userId
     * @return false|void
     */
    public static function getFiversBalance()
    {
        if (self::getCredentials()) {
            $dataArray = [
                'method' => 'money_info',
                'agent_code' => self::$agentCode,
                'agent_token' => self::$agentToken,
            ];

            $response = Http::post(self::$apiEndpoint, $dataArray);

            if ($response->successful()) {
                $data = $response->json();

                return $data['agent']['balance'] ?? 0;
            } else {
                return false;
            }
        }

    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private static function GetBalanceInfo($request)
    {
        // $decryptedUserId = Core::decryptData($request->user_code);
        $wallet = Wallet::where('user_id', $request->user_code)->where('active', 1)->first();
        if (! empty($wallet) && $wallet->total_balance >= 0) {

            Log::debug('Saldo atual: '.sprintf('%0.2f', $wallet->total_balance));

            return response()->json([
                'status' => 1,
                'user_balance' => sprintf('%0.2f', $wallet->total_balance),
            ]);
        }

        return response()->json([
            'status' => 0,
            'user_balance' => 0,
            'msg' => 'INSUFFICIENT_USER_FUNDS',
        ]);
    }

    /**
     * Set Transactions
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private static function SetTransaction($request)
    {
        $data = $request->all();

        // Log::debug($data);
        // Log::debug(["bet" => $data['live']['bet_money'], "win" => $data['live']['win_money'], "txID" => $data['live']['txn_id']]);

        // \Log::info('DEBUG:' . json_encode($data));
        // $decryptedUserId = Core::decryptData($request->user_code);
        $wallet = Wallet::where('user_id', $request->user_code)->where('active', 1)->first();

        if (! empty($wallet) && isset($data['agent_secret'])) {
            if ($data['game_type'] == 'slots' || $data['game_type'] == 'popular' || $data['game_type'] == 'slot' && isset($data['slot'])) {

                $game = Game::where('game_code', $data['slot']['game_code'])->first();

                // / verificar se usuário tem desafio ativo
                self::CheckMissionExist($request->user_code, $game, 'fivers');
                $transaction = self::PrepareTransactions(
                    $wallet,
                    $request->user_code,
                    $data['slot']['txn_id'],
                    $data['slot']['bet_money'],
                    $data['slot']['win_money'],
                    $data['slot']['game_code'],
                    $data['slot']['provider_code']
                );
                if ($transaction) {

                } else {
                    return response()->json([
                        'status' => 0,
                        'msg' => 'INSUFFICIENT_USER_FUNDS',
                    ]);
                }
            }

            if ($data['game_type'] == 'live' && isset($data['live'])) {
                $game = Game::where('game_code', $data['live']['game_code'])->first();

                $checkTransaction = Order::where('transaction_id', $data['live']['txn_id'])->count();
                if ($checkTransaction == 0 || $checkTransaction == 1) {
                    // / verificar se usuário tem desafio ativo
                    self::CheckMissionExist($request->user_code, $game, 'fivers');
                    $transaction = self::PrepareTransactionsLive(
                        $wallet,
                        $request->user_code,
                        $data['live']['txn_id'],
                        $data['live']['bet_money'],
                        $data['live']['win_money'],
                        $data['live']['game_code'],
                        $data['live']['provider_code']
                    );

                    if ($transaction) {

                    } else {
                        return response()->json([
                            'status' => 0,
                            'msg' => 'INSUFFICIENT_USER_FUNDS',
                        ]);
                    }
                }
            }
        }

        if (! empty($wallet) && isset($data['msg']) && $data['msg'] == 'Money change during the game.') {
            return response()->json([
                'status' => 1,
                'user_balance' => $wallet->total_balance,
            ]);
        }
    }

    /**
     * Metodo para retornar as listas de jogadas disponiveis
     *
     * @param  $return  players
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function CallApply($provider, $gameCode, $userCode, $rtp)
    {
        $dataArray = [
            'method' => 'call_apply',
            'agent_code' => 'trevopixdev',
            'agent_token' => '',
            'provider_code' => $provider,
            'game_code' => $gameCode,
            'user_code' => $userCode,
            'call_rtp' => $rtp,
            'call_type' => 1,
        ];

        $response = Http::post('https://api.fiverscool.com', $dataArray);

        // \DB::table('debug')->insert(['text' => json_encode($response->json())]);

    }

    /**
     * Metodo para retornar as listas de jogadas disponiveis
     *
     * @param  $return  players
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function CaptureCallList($provider, $gameCode, $userCode, $betMoney)
    {
        /* Configurar valores de super ganho */
        $min = 0.10;
        $max = 2;

        $dataArray = [
            'method' => 'call_list',
            'agent_code' => 'trevopixdev',
            'agent_token' => '',
            'provider_code' => $provider,
            'game_code' => $gameCode,
            'user_code' => $userCode,
        ];
        \DB::table('debug')->insert(['text' => json_encode($dataArray)]);

        $response = Http::post('https://api.fiverscool.com', $dataArray);
        // \DB::table('debug')->insert(['text' => json_encode($response->json())]);

        // bet * ( rtp / 100 )
        if ($response->successful()) {
            $data = $response->json();
            \DB::table('debug')->insert(['text' => json_encode($response->json())]);

            $pagou = 0;
            try {
                foreach ($data['calls'] as $jogada) { // Alterado de $data->calls para $data['calls']
                    $valorPagar = $betMoney * ($jogada['rtp'] / 100); // Alterado de $jogada->rtp para $jogada['rtp']
                    \DB::table('debug')->insert(['text' => json_encode(['jogada' => $jogada, 'valorApagar' => $valorPagar])]); // Alterado para $jogada em vez de $jogada->rtp
                    if ($valorPagar > $min && $valorPagar < $max && $pagou == 0) {
                        $pagou++;
                        self::CallApply($provider, $gameCode, $userCode, $jogada['rtp']); // Alterado de $jogada->rtp para $jogada['rtp']
                    }
                }
            } catch (\Throwable $th) {
                \DB::table('debug')->insert(['text' => $th->getMessage()]);
            }

        }

    }

    /**
     * Metodo para retornar os jogadores ativos
     *
     * @param  $return  players
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function CapturePlayers()
    {

        $dataArray = [
            'method' => 'call_players',
            'agent_code' => self::$agentCode,
            'agent_token' => self::$agentToken,
        ];

        $response = Http::post(self::$apiEndpoint, $dataArray);

        if ($response->successful()) {
            $data = $response->json();
            $qtdOnline = $data->count();
        }

    }

    /**
     * Prepare Transaction
     * Metodo responsavel por preparar a transação
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    private static function PrepareTransactionsLive($wallet, $userCode, $txnId, $betMoney, $winMoney, $gameCode, $providerCode)
    {

        Log::debug("\nIniciando uma transação ".json_encode(['txID' => $txnId, 'bet' => $betMoney, 'win' => $winMoney]));

        $user = User::find($userCode);

        if (! empty($user)) {
            $typeAction = 'bet';
            $changeBonus = 'balance';
            $bet = $betMoney;
            $winFormated = $winMoney;

            if ($wallet->balance_bonus >= $bet) {
                $wallet->decrement('balance_bonus', $bet); // / retira do bonus
                $changeBonus = 'balance_bonus'; // / define o tipo de transação
            } elseif ($wallet->balance >= $bet) {
                $wallet->decrement('balance', $bet); // / retira do saldo depositado
                $changeBonus = 'balance'; // / define o tipo de transação
            } elseif ($wallet->balance_withdrawal >= $bet) {
                $wallet->decrement('balance_withdrawal', $bet); // / retira do saldo liberado pra saque
                $changeBonus = 'balance_withdrawal'; // / define o tipo de transação
            } else {
                return response()->json([
                    'error_code' => 'INSUFFICIENT_FUNDS',
                    'error_description' => 'User balance is not enough',
                ], 200);
            }

            if ($bet == 0 && $winFormated != 0) {
                $changeBonus = 'balance';
            }

            if ($winFormated > $bet) {
                $typeAction = 'win';
            }

            $checkTransaction = Order::where('transaction_id', $txnId)->where('type', 'bet')->first();
            if ($winFormated == 0 && $bet == 0 && ! empty($checkTransaction)) {
                Helper::generateGameHistory($userCode, $typeAction, $winFormated, $checkTransaction->amount, 'balance', $txnId);
            }

            if ($winFormated != 0 && $bet == 0) {
                Helper::generateGameHistory($userCode, $typeAction, $winFormated, $bet, $changeBonus, $txnId);
            }

            if ($winFormated > $bet) {
                Log::debug("\n[WIN] ".json_encode(['txID' => $txnId, 'typeAction' => $typeAction, 'changeBonus' => $changeBonus, 'win' => $winFormated]));

                $transaction = self::CreateTransactions($userCode, time(), $txnId, $typeAction, $changeBonus, $winFormated, 'fivers', $gameCode, $gameCode);
                // $checkTransaction->update(['type', $typeAction]);

                if (! empty($transaction)) {

                    Log::debug('[WIN] Criando transação no banco');

                    // / salvar transação GGR
                    GGRGamesFiver::create([
                        'user_id' => $userCode,
                        'provider' => $providerCode,
                        'game' => $gameCode,
                        'balance_bet' => $bet,
                        'balance_win' => $winFormated,
                        'currency' => $wallet->currency,
                    ]);

                    Log::debug('[WIN] Gerando historico');
                    // Helper::generateGameHistory($userCode, $typeAction, $winFormated, $bet, $changeBonus, $txnId);

                    return response()->json([
                        'status' => 1,
                        'user_balance' => $wallet->total_balance,
                    ]);
                }
            } else {
                Log::debug("\n[BET] ".json_encode(['txID' => $txnId, 'typeAction' => $typeAction, 'changeBonus' => $changeBonus, 'win' => $winFormated]));

                $transaction = self::CreateTransactions($userCode, time(), $txnId, $typeAction, $changeBonus, $bet, 'fivers', $gameCode, $gameCode);
                // $checkTransaction->update(['type', $typeAction]);

                if (! empty($transaction)) {

                    Log::debug('[BET] Criando transação no banco');

                    // / salvar transação GGR
                    GGRGamesFiver::create([
                        'user_id' => $userCode,
                        'provider' => $providerCode,
                        'game' => $gameCode,
                        'balance_bet' => $bet,
                        'balance_win' => $winFormated,
                        'currency' => $wallet->currency,
                    ]);

                    /*$checkTransaction = Order::where('transaction_id', $txnId)->first();
                    if(empty($checkTransaction)) {
                        $checkTransaction = self::CreateTransactions($userCode, time(), $txnId, $typeAction, $changeBonus, $bet, 'fivers', $gameCode, $gameCode);
                    }*/

                    Log::debug('[BET] Gerando historico');
                    // Helper::generateGameHistory($userCode, $typeAction, $winFormated, $bet, $changeBonus, $txnId);

                    return response()->json([
                        'status' => 1,
                        'user_balance' => $wallet->total_balance,
                    ]);
                } else {
                    return response()->json([
                        'status' => 1,
                        'user_balance' => $wallet->total_balance,
                    ]);
                }
            }

            return response()->json([
                'status' => 1,
                'user_balance' => $wallet->total_balance,
            ]);
        }
    }

    /**
     * Prepare Transaction
     * Metodo responsavel por preparar a transação
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    private static function PrepareTransactions($wallet, $userCode, $txnId, $betMoney, $winMoney, $gameCode, $providerCode)
    {
        $user = User::find($userCode);
        $ordensCriadas = Order::where('user_id', $userCode)->orderBy('id', 'DESC');

        if (! empty($user)) {
            $changeBonus = 'balance';
            $bet = $betMoney;
            $winFormated = $winMoney;

            \Log::info('DEBUG BET:'.json_encode([$bet, $winFormated]));

            // / deduz o saldo apostado
            if ($wallet->balance_bonus >= $bet) {
                $wallet->decrement('balance_bonus', $bet); // / retira do bonus
                $changeBonus = 'balance_bonus'; // / define o tipo de transação
            } elseif ($wallet->balance >= $bet) {
                $wallet->decrement('balance', $bet); // / retira do saldo depositado
                $changeBonus = 'balance'; // / define o tipo de transação
            } elseif ($wallet->balance_withdrawal >= $bet) {
                $wallet->decrement('balance_withdrawal', $bet); // / retira do saldo liberado pra saque
                $changeBonus = 'balance_withdrawal'; // / define o tipo de transação
            } else {
                return response()->json([
                    'error_code' => 'INSUFFICIENT_FUNDS',
                    'error_description' => 'User balance is not enough',
                ], 200);
            }

            if ($bet == 0 && $winFormated != 0) {
                $changeBonus = 'balance';
            }

            if ($winFormated > $bet) {
                $typeAction = 'win';
                $transaction = self::CreateTransactions($userCode, time(), $txnId, $typeAction, $changeBonus, $winFormated, 'fivers', $gameCode, $gameCode);

                if ($transaction) {
                    // / salvar transação GGR
                    GGRGamesFiver::create([
                        'user_id' => $userCode,
                        'provider' => $providerCode,
                        'game' => $gameCode,
                        'balance_bet' => $bet,
                        'balance_win' => $winFormated,
                        'currency' => $wallet->currency,
                    ]);

                    // / pagar afiliado
                    Helper::generateGameHistory($userCode, $typeAction, $winFormated, $bet, $changeBonus, $txnId);

                    return response()->json([
                        'status' => 1,
                        'user_balance' => $wallet->total_balance,
                    ]);
                }
            } elseif ($winFormated < $bet) {
                $typeAction = 'bet';
                $ultimas_jogadas = $ordensCriadas->take(5)->get();
                $totalGanhos = 0;
                $totalPerdas = 0;
                foreach ($ultimas_jogadas as $jogada) {
                    if ($jogada->type == 'Vitória' || $jogada->type == 'win') {
                        $totalGanhos++;
                    } else {
                        $totalPerdas++;
                    }
                }

                /*$ultimas_jogadas = Order::where('user_id', $userCode)->orderBy('id', 'DESC')->take(5)->get();
                $totalGanhos = $ultimas_jogadas->where('type', 'Vitória')->count() + $ultimas_jogadas->where('type', 'win')->count();
                $totalPerdas = $ultimas_jogadas->whereNotIn('type', ['Vitória', 'win'])->count();*/

                // \DB::table('debug')->insert(['text' => json_encode([$ultimas_jogadas, $totalGanhos, $totalPerdas])]);

                $limites_e_acoes = [
                    ['limite_min' => 26, 'limite_max' => 30],
                    ['limite_min' => 16, 'limite_max' => 20],
                    ['limite_min' => 8, 'limite_max' => 10],
                    ['limite_min' => 4, 'limite_max' => 6],
                    ['limite_min' => 1, 'limite_max' => 3],
                ];

                foreach ($limites_e_acoes as $limite_e_acao) {
                    if ($wallet->total_balance > $limite_e_acao['limite_min'] && $wallet->total_balance < $limite_e_acao['limite_max']) {
                        if ($totalGanhos == 0 || $totalGanhos == 1 && $totalPerdas > 3 || $totalGanhos == 0 && $totalPerdas == 5) {
                            // self::CaptureCallList($providerCode, $gameCode, $userCode, $betMoney);
                        }
                    }
                }

                $rtp_list = [80, 85, 88, 90, 91, 94, 95, 96, 97, 98, 99];
                // Escolha um número aleatório da lista
                $random_rtp = $rtp_list[array_rand($rtp_list)];
                // Atualize o RTP usando o número aleatório escolhido
                // self::UpdateRTP($random_rtp, $providerCode, $userCode);

                // / criar uma transação
                $checkTransaction = Order::where('transaction_id', $txnId)->first();
                if (empty($checkTransaction)) {
                    // \Log::info('DEBUG CRIANDO TRANSACAO:' . json_encode($checkTransaction));
                    // \DB::table('debug')->insert(['text' => json_encode([$userCode, time(), $txnId, $typeAction, $changeBonus, $bet, 'fivers', $gameCode, $gameCode])]);
                    $checkTransaction = self::CreateTransactions($userCode, time(), $txnId, $typeAction, $changeBonus, $bet, 'fivers', $gameCode, $gameCode);
                }

                // \Log::info('DEBUG SALVA GGR:' . json_encode([$user->id, $typeAction, $winMoney, $bet, $changeBonus, $checkTransaction->transaction_id]));
                // / salvar transação GGR
                GGRGamesFiver::create([
                    'user_id' => $userCode,
                    'provider' => $providerCode,
                    'game' => $gameCode,
                    'balance_bet' => $bet,
                    'balance_win' => 0,
                    'currency' => $wallet->currency,
                ]);

                // Helper::lossRollover($wallet, $bet);
                Helper::generateGameHistory($userCode, $typeAction, $winFormated, $bet, $changeBonus, $checkTransaction->transaction_id);

                return response()->json([
                    'status' => 1,
                    'user_balance' => $wallet->total_balance,
                ]);
            } else {
                return response()->json([
                    'status' => 1,
                    'user_balance' => $wallet->total_balance,
                ]);
            }

        } else {
            return response()->json([
                'status' => 1,
                'user_balance' => $wallet->total_balance,
            ]);
        }
    }

    public static function WebhooksFivers($request)
    {

        /*$decryptedUserId = Core::decryptData($request->user_code);

        // Verificar se a descriptografia foi bem-sucedida
        if ($decryptedUserId === false) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        // Validar o ID do usuário (aqui você pode adicionar mais validações se necessário)
        if (!is_numeric($decryptedUserId)) {
            return response()->json(['error' => 'Acesso não autorizado'], 403);
        }

        $request->user_code = $decryptedUserId;*/

        // Log::debug("Recebendo request {$request->method}");

        switch ($request->method) {
            case 'user_balance':
                return self::GetBalanceInfo($request);
            case 'transaction':
                return self::SetTransaction($request);
            default:
                return response()->json(['status' => 0]);
        }
    }

    private static function CreateTransactions($playerId, $betReferenceNum, $transactionID, $type, $changeBonus, $amount, $providers, $game, $pn)
    {

        $order = Order::create([
            'user_id' => $playerId,
            'session_id' => $betReferenceNum,
            'transaction_id' => $transactionID,
            'type' => $type,
            'type_money' => $changeBonus,
            'amount' => $amount,
            'providers' => $providers,
            'game' => $game,
            'game_uuid' => $pn,
            'round_id' => 1,
        ]);

        if ($order) {
            return $order;
        }

        return false;
    }
}
