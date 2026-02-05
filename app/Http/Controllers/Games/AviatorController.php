<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\AviatorBet;
use App\Models\AviatorGameResult;
use App\Models\GameSetting;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AviatorController extends Controller
{
    // Helper para obter o ID do jogo atual
    private function currentid()
    {
        $data = AviatorGameResult::orderBy('id', 'desc')->first();

        return $data ? $data->id : 0;
    }

    // Helper para obter o usuário autenticado (Web ou API)
    private function getAuthUser()
    {
        if (auth()->check()) {
            return auth()->user();
        }

        try {
            if (request()->header('Authorization') && auth('api')->check()) {
                return auth('api')->user();
            }
        } catch (\Exception $e) {
            // Silencioso em produção
        }

        return null;
    }

    // Helper para simular o helper wallet() original
    // Usando a mesma lógica dos jogos Mines e Chicken
    private function getWalletBalance($userId)
    {
        $user = User::find($userId);
        if ($user && $user->wallet) {
            return $user->wallet->balance + $user->wallet->balance_bonus + $user->wallet->balance_withdrawal;
        }

        return 0;
    }

    public function game_existence(Request $request)
    {
        $event = $request->event;
        if ($event == 'check') {
            // Verifica status do jogo em GameSetting
            $gameStatus = GameSetting::getSetting('aviator_game_status', '0'); // 0 = stopped, 1 = running

            // Lógica original adaptada
            if ($gameStatus == '0' || (session()->has('gamegenerate') && session()->get('gamegenerate') == 1)) {
                return response()->json(['data' => true]);
            } else {
                return response()->json(['data' => false]);
            }
        }

        return response()->json(['data' => false]);
    }

    public function new_game_generated(Request $request)
    {
        GameSetting::set('aviator_game_status', '0');
        $request->session()->put('gamegenerate', '1');

        // Gera o ponto de crash
        // Lógica simples:
        // 50% de chance de ser baixo (1.00 - 2.00)
        // 30% de chance de ser médio (2.00 - 10.00)
        // 20% de chance de ser alto (10.00 - 100.00)
        // Ou usar lógica do MinesController/Chance Global

        $crashPoint = $this->generateCrashPoint();

        // Cria novo jogo
        $game = new AviatorGameResult;
        $game->result = 'pending';
        $game->crash_point = $crashPoint;
        $game->save();

        return response()->json(['id' => $game->id]);
    }

    private function generateCrashPoint()
    {
        // Exemplo simples de distribuição
        $rand = rand(1, 100);

        if ($rand <= 50) {
            // 1.00 a 2.00
            return rand(100, 200) / 100;
        } elseif ($rand <= 80) {
            // 2.00 a 10.00
            return rand(200, 1000) / 100;
        } else {
            // 10.00 a 50.00
            return rand(1000, 5000) / 100;
        }
    }

    public function crash_plane()
    {
        return 1;
    }

    // Endpoint solicitado pelo JS
    public function api_currentid()
    {
        return response()->json(['id' => $this->currentid()]);
    }

    public function increamentor(Request $request)
    {
        $currentId = $this->currentid();
        $game = AviatorGameResult::find($currentId);

        // Se o jogo não existir ou já tiver resultado (diferente de pending), retorna 0 ou status false?
        // O JS espera {result: float} que é o alvo

        if ($game && $game->result == 'pending') {
            return response()->json(['status' => true, 'result' => $game->crash_point]);
        }

        // Se já acabou, retorna o resultado final
        if ($game) {
            return response()->json(['status' => true, 'result' => $game->result]);
        }

        return response()->json(['status' => false, 'result' => 0]);
    }

    public function game_over(Request $request)
    {
        $request->session()->forget('result');
        $currentId = $this->currentid();
        $user = $this->getAuthUser();

        // Atualiza o resultado do jogo atual com o valor que 'crashou'
        AviatorGameResult::where('id', $currentId)->update([
            'result' => number_format($request->last_time, 2),
        ]);

        // Processa apostas pendentes (quem não deu cashout perdeu)
        // Na verdade, o cashout atualiza o status para 1. Quem ficou 0 perdeu.
        // A lógica original verifica se o crash foi <= 1.20 para forçar perda?
        // Vou manter simples: quem não deu cashout (status 0) perdeu.

        // No original:
        // $alluserbit = Userbit::where('gameid', currentid())->where('status', 0)->get();
        // ... update status 1 ...
        // Mas não paga nada. Apenas marca como processado?
        // Original parece marcar como processado (status 1) mas sem pagar (addwallet comentado ou condicional)

        AviatorBet::where('gameid', $currentId)
            ->where('status', 0)
            ->update(['status' => 1]); // Marca como finalizado (perda)

        // Reseta status do jogo
        GameSetting::set('aviator_game_status', '0');
        $request->session()->put('gamegenerate', '0');

        // Cria próximo jogo
        $newGame = new AviatorGameResult;
        $newGame->result = 'pending';
        $newGame->save();

        if ($user) {
            return $this->getWalletBalance($user->id);
        }

        return 0;
    }

    public function betNow(Request $request)
    {
        $user = $this->getAuthUser();

        if (! $user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Usuário não autenticado',
            ]);
        }

        $wallet = $user->wallet;
        $returnBets = [];
        $status = false;
        $message = 'Something went wrong!';
        $data = [];

        // O JS envia all_bets como array
        if ($request->has('all_bets')) {
            foreach ($request->all_bets as $bet) {
                $amount = floatval($bet['bet_amount']);

                // Validações básicas de valor mínimo/máximo se necessário
                if ($amount <= 0) {
                    continue;
                }

                if ($wallet->total_balance >= $amount) {
                    // Desconta saldo usando a prioridade correta (Bonus -> Balance -> Withdrawal)
                    $changeBonus = \App\Helpers\Core::DiscountBalanceMines($wallet, $amount);

                    if ($changeBonus !== 'no_balance') {
                        // Cria a aposta
                        $aviatorBet = new AviatorBet;
                        $aviatorBet->userid = $user->id;
                        $aviatorBet->amount = $amount;
                        $aviatorBet->type = $bet['bet_type'];
                        $aviatorBet->gameid = $this->currentid();
                        $aviatorBet->section_no = $bet['section_no'];
                        $aviatorBet->wallet_type = $changeBonus; // Salva qual carteira foi usada
                        $aviatorBet->save();

                        // Contabiliza rollover
                        \App\Helpers\Core::payWithRollover($user->id, $changeBonus, 0, $amount, 'bet');

                        // Registra transação
                        \App\Models\Transaction::create([
                            'user_id' => $user->id,
                            'type' => 'bet',
                            'amount' => -$amount,
                            'game_id' => $aviatorBet->id,
                            'description' => 'Aposta Aviator',
                            'wallet_type' => $changeBonus,
                        ]);

                        $returnBets[] = ['bet_id' => $aviatorBet->id];
                        $status = true;
                        $message = '';

                        // Atualiza wallet em memória para loop
                        $wallet->refresh();
                    } else {
                        $message = 'Saldo insuficiente!';
                    }
                } else {
                    $message = 'Saldo insuficiente!';
                }
            }
        }

        // Calcula o saldo total como nos jogos Mines e Chicken
        $totalBalance = $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal;

        return response()->json([
            'isSuccess' => $status,
            'data' => [
                'wallet_balance' => $totalBalance,
                'return_bets' => $returnBets,
            ],
            'message' => $message,
        ]);
    }

    public function cashout(Request $request)
    {
        $game_id = $request->game_id;
        $bet_id = $request->bet_id;
        $win_multiplier = $request->win_multiplier;

        $user = $this->getAuthUser();
        if (! $user) {
            return response()->json(['isSuccess' => false, 'message' => 'Usuário não autenticado']);
        }

        // Busca a aposta pendente
        $bet = AviatorBet::where('id', $bet_id)
            ->where('userid', $user->id)
            ->where('status', 0)
            ->first();

        if ($bet) {
            // Verifica resultado real do jogo se já tiver crashado (segurança adicional poderia ser feita aqui)

            $cash_out_amount = floatval($bet->amount) * floatval($win_multiplier);

            // Paga o usuário usando o Helper de Rollover e o tipo de carteira salvo na aposta
            \App\Helpers\Core::payWithRollover($user->id, $bet->wallet_type, $cash_out_amount, 0, 'win');

            // Atualiza aposta
            $bet->status = 1;
            $bet->cashout_multiplier = $win_multiplier;
            $bet->save();

            // Transação de ganho
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'type' => 'win',
                'amount' => $cash_out_amount,
                'game_id' => $bet->id,
                'description' => 'Ganho Aviator',
                'wallet_type' => $bet->wallet_type,
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'wallet_balance' => $user->wallet->balance + $user->wallet->balance_bonus + $user->wallet->balance_withdrawal,
                    'cash_out_amount' => $cash_out_amount,
                ],
                'message' => '',
            ]);
        }

        return response()->json(['isSuccess' => false, 'message' => 'Aposta não encontrada ou já processada']);
    }

    public function currentlybet()
    {
        $currentId = $this->currentid();
        $user = $this->getAuthUser();

        \Log::info('Aviator currentlybet called. User: '.($user ? $user->id : 'Guest'));

        // Apostas reais
        $realBets = AviatorBet::where('gameid', $currentId)
            ->join('users', 'users.id', '=', 'aviator_bets.userid')
            ->select('aviator_bets.*', 'users.avatar', 'users.name as username', 'users.id as user_id_real')
            ->get();

        $currentGameBet = [];
        foreach ($realBets as $bet) {
            $img = asset('images/default-avatar.png');
            if ($bet->avatar) {
                if (str_starts_with($bet->avatar, 'images/')) {
                    $img = asset($bet->avatar);
                } else {
                    $img = asset('storage/'.$bet->avatar);
                }
            }

            $currentGameBet[] = [
                'userid' => $bet->username,
                'amount' => $bet->amount,
                'image' => $img,
                'cashout_multiplier' => $bet->cashout_multiplier,
                'gameid' => $bet->gameid,
                'bet_id' => $bet->id,
                'section_no' => $bet->section_no,
                'class_name' => $bet->user_id_real.$bet->section_no,
            ];
        }

        // Adicionar Bots (Fake Bets)
        // Usa o ID do jogo atual como semente para garantir consistência durante a rodada
        if ($currentId > 0) {
            mt_srand($currentId);
            $botsCount = mt_rand(25, 60); // Entre 25 e 60 bots

            for ($i = 0; $i < $botsCount; $i++) {
                $botId = mt_rand(100000, 999999);

                // Nome aleatório mascarado
                $nameLen = mt_rand(3, 5);
                $letters = 'abcdefghijklmnopqrstuvwxyz';
                $nameStart = '';
                for ($j = 0; $j < $nameLen; $j++) {
                    $nameStart .= $letters[mt_rand(0, strlen($letters) - 1)];
                }
                $botName = ucfirst($nameStart).'***';

                // Avatar aleatório
                $avatarId = mt_rand(1, 72);
                $botAvatar = asset('images/avtar/av-'.$avatarId.'.png');

                // Valor da aposta ponderado
                $prob = mt_rand(1, 100);
                if ($prob <= 80) {
                    $botAmount = mt_rand(1, 50); // 80% apostas baixas
                } elseif ($prob <= 95) {
                    $botAmount = mt_rand(51, 200); // 15% apostas médias
                } else {
                    $botAmount = mt_rand(201, 1000); // 5% apostas altas
                }

                $currentGameBet[] = [
                    'userid' => $botName,
                    'amount' => number_format($botAmount, 2, '.', ''),
                    'image' => $botAvatar,
                    'cashout_multiplier' => null, // Bots não mostram cashout na lista ao vivo para simplificar
                    'gameid' => $currentId,
                    'bet_id' => $botId,
                    'section_no' => mt_rand(0, 1),
                    'class_name' => $botId.mt_rand(0, 1),
                ];
            }
            // Reseta a semente aleatória para não afetar o restante do sistema
            mt_srand();
        }

        $currentGame = ['id' => $currentId];

        $wallet_balance = 0;
        if ($user) {
            $wallet_balance = $this->getWalletBalance($user->id);
        }

        return response()->json([
            'currentGame' => $currentGame,
            'currentGameBet' => $currentGameBet,
            'currentGameBetCount' => count($currentGameBet),
            'wallet_balance' => $wallet_balance,
        ]);
    }

    public function my_bets_history()
    {
        $user = $this->getAuthUser();

        if (! $user) {
            return response()->json([]);
        }

        $userbets = AviatorBet::where('userid', $user->id)
            ->where('status', 1)
            ->where('created_at', '>=', Carbon::today()->toDateString())
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return response()->json($userbets);
    }

    public function getUserDetails()
    {
        $user = $this->getAuthUser();

        if (! $user) {
            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'avatar' => asset('images/default-avatar.png'),
                    'username' => 'Guest',
                    'id' => 0,
                    'email' => '',
                    'notification' => '',
                    'request_type' => '',
                ],
            ]);
        }

        $avatar = asset('images/default-avatar.png');
        if ($user->avatar) {
            if (str_starts_with($user->avatar, 'images/')) {
                $avatar = asset($user->avatar);
            } else {
                $avatar = asset('storage/'.$user->avatar);
            }
        }

        return response()->json([
            'isSuccess' => true,
            'data' => [
                'avatar' => $avatar,
                'username' => $user->name,
                'id' => $user->id,
                'email' => $user->email,
                'notification' => '',
                'request_type' => '',
            ],
        ]);
    }

    public function getAvatar()
    {
        $avatars = [];
        for ($i = 1; $i <= 72; $i++) {
            $avatars[] = [
                'id' => $i,
                'image' => asset('images/avtar/av-'.$i.'.png'),
            ];
        }

        return response()->json(['isSuccess' => true, 'data' => $avatars]);
    }

    public function changeAvatar(Request $request)
    {
        $user = $this->getAuthUser();
        if (! $user) {
            return response()->json(['isSuccess' => false]);
        }

        $avatarId = $request->avatar;

        $imagePath = 'images/avtar/av-'.$avatarId.'.png';

        $user->avatar = $imagePath;
        $user->save();

        return response()->json(['isSuccess' => true]);
    }

    public function memberBet(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = 10;
        $user = $this->getAuthUser();

        if (! $user) {
            return response()->json(['isSuccess' => true, 'data' => []]);
        }
        $userId = $user->id;

        $bets = AviatorBet::where('userid', $userId)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $data = [];
        foreach ($bets as $bet) {
            $cashOutAmount = ($bet->cashout_multiplier > 0) ? ($bet->amount * $bet->cashout_multiplier) : 0;

            $data[] = [
                'multiplication' => $bet->cashout_multiplier ?? 0,
                'cash_out_amount' => $cashOutAmount,
                'date' => Carbon::parse($bet->created_at)->format('d M, H:i'),
                'bet_amount' => $bet->amount,
            ];
        }

        return response()->json(['isSuccess' => true, 'data' => $data]);
    }

    public function isLogin()
    {
        return response()->json(['isSuccess' => (bool) $this->getAuthUser()]);
    }

    public function updateIsNotify(Request $request)
    {
        return response()->json(['status' => true]);
    }

    public function previousGameBetList(Request $request)
    {
        $gameId = $request->game_id;
        $game = AviatorGameResult::find($gameId);

        if (! $game) {
            return response()->json(['isSuccess' => false]);
        }

        $bets = AviatorBet::where('gameid', $gameId)
            ->join('users', 'users.id', '=', 'aviator_bets.userid')
            ->select('aviator_bets.*', 'users.name as username', 'users.avatar', 'users.id as user_id_real')
            ->get();

        $formattedBets = [];
        foreach ($bets as $bet) {
            $img = asset('images/default-avatar.png');
            if ($bet->avatar) {
                if (str_starts_with($bet->avatar, 'images/')) {
                    $img = asset($bet->avatar);
                } else {
                    $img = asset('storage/'.$bet->avatar);
                }
            }

            $formattedBets[] = [
                'userid' => $bet->username,
                'amount' => $bet->amount,
                'image' => $img,
                'cashout_multiplier' => $bet->cashout_multiplier,
                'class_name' => $bet->user_id_real.$bet->section_no,
            ];
        }

        // Adicionar fakes no histórico também
        $fakesCount = rand(10, 20);
        for ($i = 0; $i < $fakesCount; $i++) {
            $fakeAmount = number_format(rand(10, 5000) / 10, 2, '.', '');
            $fakeMultiplier = 0;

            // Simula alguns ganhadores se o jogo não crashou instantaneamente
            if ($game->result > 1.2 && rand(0, 10) > 3) {
                $maxM = floatval($game->result) - 0.1;
                if ($maxM > 1.1) {
                    $fakeMultiplier = number_format(rand(110, $maxM * 100) / 100, 2, '.', '');
                }
            }

            $formattedBets[] = [
                'userid' => 'User'.rand(1000, 9999),
                'amount' => $fakeAmount,
                'image' => asset('images/default-avatar.png'),
                'cashout_multiplier' => $fakeMultiplier > 0 ? $fakeMultiplier : null,
                'class_name' => rand(100000, 999999),
            ];
        }

        return response()->json([
            'isSuccess' => true,
            'data' => [
                'bet_list' => $formattedBets,
                'bet_counts' => count($formattedBets),
                'win_multi' => $game->result ?? 0,
            ],
        ]);
    }

    public function index()
    {
        return inertia('Games/Aviator');
    }

    public function play()
    {
        $allresults = AviatorGameResult::where('created_at', '>=', Carbon::today()->toDateString())
            ->orderBy('id', 'desc')
            ->get();

        if (auth()->check()) {
            $mybets = AviatorBet::where('userid', auth()->id())
                ->where('created_at', '>=', Carbon::today()->toDateString())
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $mybets = collect([]);
        }

        return view('games.aviator', compact('allresults', 'mybets'));
    }
}
