<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\GamesKey;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait AggrTrait
{
    protected static $agentApi;

    protected static $agentPassword;

    protected static $apiEndpoint;

    public static function getCredentialsAggregtr(): bool
    {
        $setting = GamesKey::first();

        self::$agentApi = $setting->getAttributes()['agentApi'];
        self::$agentPassword = $setting->getAttributes()['agentPassword'];
        self::$apiEndpoint = $setting->getAttributes()['apiEndpoint'] ?? 'https://gs.aggregtr.com/api/system/operator';

        /*self::$agentApi = '58c7af16-7561-414a-bf29-47e0c44294d6-664057';
        self::$agentPassword = 'UcOOpNY7vU46';
        self::$apiEndpoint = 'https://gs.aggregtr.com/api/system/operator';*/
        return true;
    }

    public function createPlayer(string $user, string $pass)
    {
        $data = [
            'api_login' => self::$agentApi,
            'api_password' => self::$agentPassword,
            'method' => 'createPlayer',
            'user_username' => $user,
            'user_password' => $pass,
            'currency' => 'EUR',
        ];

        $res = Http::post(self::$apiEndpoint, $data);
        if ($res->successful()) {
            return true;
        } else {
            return false;
        }
    }

    public function startGameAggregtr(string $player, string $nick, string $gameId)
    {

        if (! self::getCredentialsAggregtr()) {
            return response()->json([
                'erro' => 'Falta parametros!',
            ], 400);
        }

        if (! $player || ! $gameId) {
            return response()->json([
                'erro' => 'Falta parametros!',
            ], 400);
        }

        $username = "teste@$player";
        $password = "@$username@2025@";

        $data = [
            'api_login' => self::$agentApi,
            'api_password' => self::$agentPassword,
            'method' => 'getGame',
            'lang' => 'pt',
            'user_username' => $username,
            'user_password' => $password,
            'gameid' => $gameId,
            'homeurl' => url('/'),
            'cashierurl' => url('/deposit'),
            'play_for_fun' => 0,
            'currency' => 'EUR',
        ];

        // Http::post('https://zealous-keyboard-62.webhook.cool', $data);
        $user = $this->createPlayer($username, $password);

        $res = Http::post(self::$apiEndpoint, $data);
        if ($res->successful() && $user) {
            $json = $res->json();

            return ['launch_url' => $json['response']];
        }
    }

    private static function getBalanceUser($id)
    {
        $wallet = Wallet::where('user_id', $id)->where('active', 1)->first();
        if (! empty($wallet) && $wallet->total_balance >= 0) {
            $balanceEmCentavos = (int) ($wallet->total_balance * 100);

            return response()->json([
                'error' => 0,
                'balance' => $balanceEmCentavos,
            ]);
        }

        return response()->json([
            'error' => 1,
            'balance' => 0,
        ]);
    }

    private static function percaOuGanhoAggr($user_id, $bet, $win, $tx_id, $round_id, $game_code)
    {
        if (! self::getCredentialsAggregtr()) {
            return response()->json([
                'erro' => 'Falta parametros!',
            ], 400);
        }

        $user = User::where('id', $user_id)->first();
        if ($user != null) {
            $wallet = $user->wallet;

            $saldoAnt = (float) $wallet->balance + (float) $wallet->balance_bonus + (float) $wallet->balance_withdrawal;
            $saldo = ((float) $wallet->balance + (float) $wallet->balance_bonus + (float) $wallet->balance_withdrawal) - $bet + $win;
            $id = rand(0, 9999999999);
            $changeBonus = null;
            if ($saldoAnt >= $bet) {
                if ($wallet->balance_bonus > $bet) {
                    $wallet->decrement('balance_bonus', $bet);
                    $changeBonus = 'balance_bonus';
                } elseif ($wallet->balance >= $bet) {
                    $wallet->decrement('balance', $bet);
                    $changeBonus = 'balance';
                } elseif ($wallet->balance_withdrawal >= $bet) {
                    $wallet->decrement('balance_withdrawal', $bet);
                    $changeBonus = 'balance_withdrawal';
                } else {
                    $changeBonus = 'balance';
                    if ($saldoAnt >= $bet) {
                        $valorPerdido = $bet;
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

                        $wallet->update([
                            'balance_bonus' => $balanceBonus,
                            'balance' => $balance,
                            'balance_withdrawal' => $balanceWithdrawal,
                        ]);
                    }
                }
                if ($bet == 0 && $win != 0) {
                    $changeBonus = 'balance';
                }

                if ($bet != 0 || $win != 0) {
                    Order::create([
                        'user_id' => $user->id,
                        'session_id' => $round_id,
                        'transaction_id' => $tx_id,
                        'game' => $game_code,
                        'game_uuid' => $game_code,
                        'type' => $win == 0 ? 'bet' : 'win',
                        'type_money' => $changeBonus,
                        'amount' => $win == 0 ? $bet : $win,
                        'providers' => 'aggr',
                        'refunded' => false,
                        'round_id' => $round_id,
                        'status' => true,
                    ]);
                    Helper::generateGameHistory($user->id, $win == 0 ? 'bet' : 'win', $win, $bet, $changeBonus, $tx_id);
                }

                return self::getBalanceUser($user_id);
            } else {
                return response()->json(['error' => 1, 'balance' => 0]);
            }
        } else {
            return response()->json(['error' => 2, 'balance' => 0]);
        }
    }

    public function WebhooksAggrTrait(Request $request)
    {

        $data = $request->all();

        Log::debug($data);

        $id_user = explode('@', $data['username'])[1];

        if ($data['action'] == 'balance') {
            return $this->getBalanceUser($id_user);
        }

        if ($data['action'] == 'debit') {
            $win = 0;
            $bet = sprintf('%0.2f', $data['amount'] / 100);
            $game_code = $data['game_id'];
            $tx_id = $data['call_id'];
            $round_id = $data['round_id'];

            return $this->percaOuGanhoAggr($id_user, $bet, $win, $tx_id, $round_id, $game_code);
        }

        if ($data['action'] == 'credit') {
            $win = sprintf('%0.2f', $data['amount'] / 100);
            $bet = 0;
            $game_code = $data['game_id'];
            $tx_id = $data['call_id'];
            $round_id = $data['round_id'];

            return $this->percaOuGanhoAggr($id_user, $bet, $win, $tx_id, $round_id, $game_code);
        }

    }
}
