<?php

namespace App\Traits;

use App\Helpers\Core as Helper;
use App\Models\GamesKey;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

trait TbsTrait
{
    /**
     * Obtém as credenciais da TBS API
     *
     * @return array
     */
    public function getTbsCredentials()
    {
        // Busca as credenciais do banco de dados
        $gamesKey = GamesKey::first();

        // Valores padrão caso não exista configuração
        $defaultEndpoint = 'https://api.tbs.com';
        $defaultDomain = 'https://games.tbs.com';
        $defaultExitUrl = url('/');
        $defaultLanguage = 'pt';
        $defaultContinent = 'EU';

        return [
            'hall' => $gamesKey->tbs_hall ?? '',
            'key' => $gamesKey->tbs_key ?? '',
            'endpoint' => $gamesKey->tbs_endpoint ?? $defaultEndpoint,
            'domain' => $gamesKey->tbs_domain ?? $defaultDomain,
            'exit_url' => $gamesKey->tbs_exit_url ?? $defaultExitUrl,
            'demo_mode' => $gamesKey->tbs_demo_mode ?? false,
            'jackpots_enabled' => $gamesKey->tbs_jackpots_enabled ?? false,
            'default_language' => $gamesKey->tbs_default_language ?? $defaultLanguage,
            'default_continent' => $gamesKey->tbs_default_continent ?? $defaultContinent,
        ];
    }

    /**
     * Cria um usuário na TBS API (não necessário, apenas para compatibilidade)
     *
     * @param  User  $user
     * @return array
     */
    public function createTbsUser($user)
    {
        // TBS não requer pré-criação de usuário
        return [
            'success' => true,
            'user_id' => $user->id,
        ];
    }

    /**
     * Lança um jogo da TBS API
     *
     * @param  User  $user
     * @param  string  $gameId
     * @param  bool  $demo
     * @return array
     */
    public function tbsGameLaunch($user, $gameId, $demo = false)
    {
        try {
            $credentials = $this->getTbsCredentials();

            // Verifica se o modo demo está forçado nas configurações
            if ($credentials['demo_mode']) {
                $demo = true;
            }

            // Obtém o saldo do usuário
            $balance = $this->getTbsBalance($user);

            // Monta os parâmetros para a URL do jogo
            $params = [
                'token' => $credentials['key'],
                'hall' => $credentials['hall'],
                'game' => $gameId,
                'user' => $user->id,
                'balance' => $balance * 100, // TBS usa centavos
                'lang' => $credentials['default_language'],
                'exit_url' => $credentials['exit_url'],
                'demo' => $demo ? 1 : 0,
            ];

            // Constrói a URL do jogo
            $gameUrl = $credentials['domain'].'?'.http_build_query($params);

            return [
                'success' => true,
                'url' => $gameUrl,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao lançar jogo TBS: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao lançar o jogo',
            ];
        }
    }

    /**
     * Obtém o saldo total do usuário
     *
     * @param  User  $user
     * @return float
     */
    public function getTbsBalance($user)
    {
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return 0;
        }

        // Retorna o saldo total (saldo + bônus + saldo de saque)
        return $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal;
    }

    /**
     * Processa o webhook da TBS API
     *
     * @param  array  $data
     * @return array
     */
    public function webhookTbs($data)
    {
        try {
            Log::info('Webhook TBS recebido', $data);

            // Verifica se os dados necessários estão presentes
            if (! isset($data['user_id']) || ! isset($data['action'])) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                ];
            }

            $userId = $data['user_id'];
            $action = $data['action'];

            // Busca o usuário
            $user = User::find($userId);

            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado',
                ];
            }

            // Processa a ação
            switch ($action) {
                case 'bet':
                    return $this->processTbsBet($data, $user);

                case 'win':
                    return $this->processTbsWin($data, $user);

                case 'rollback':
                    return $this->processTbsRollback($data, $user);

                case 'balance':
                    return $this->getTbsBalanceInfo($user);

                default:
                    return [
                        'success' => false,
                        'message' => 'Ação não suportada',
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Erro no webhook TBS: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno',
            ];
        }
    }

    /**
     * Processa uma aposta da TBS API
     *
     * @param  array  $data
     * @param  User  $user
     * @return array
     */
    protected function processTbsBet($data, $user)
    {
        try {
            // Verifica se os dados necessários estão presentes
            if (! isset($data['amount']) || ! isset($data['transaction_id']) || ! isset($data['game_id'])) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                ];
            }

            $amount = $data['amount'] / 100; // Converte de centavos para reais
            $transactionId = $data['transaction_id'];
            $gameId = $data['game_id'];

            // Verifica se a transação já existe
            $existingOrder = Order::where('tx', $transactionId)->first();

            if ($existingOrder) {
                return [
                    'success' => false,
                    'message' => 'Transação duplicada',
                ];
            }

            // Busca a carteira do usuário
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (! $wallet) {
                return [
                    'success' => false,
                    'message' => 'Carteira não encontrada',
                ];
            }

            // Verifica se o usuário tem saldo suficiente
            $totalBalance = $wallet->balance + $wallet->balance_bonus + $wallet->balance_withdrawal;

            if ($totalBalance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Saldo insuficiente',
                ];
            }

            // Debita o saldo do usuário
            Helper::generateGameHistory($user->id, $gameId, $amount, 0, $transactionId, 'bet', 'tbs');

            return [
                'success' => true,
                'balance' => $this->getTbsBalance($user) * 100, // Retorna em centavos
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar aposta TBS: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno',
            ];
        }
    }

    /**
     * Processa um ganho da TBS API
     *
     * @param  array  $data
     * @param  User  $user
     * @return array
     */
    protected function processTbsWin($data, $user)
    {
        try {
            // Verifica se os dados necessários estão presentes
            if (! isset($data['amount']) || ! isset($data['transaction_id']) || ! isset($data['game_id'])) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                ];
            }

            $amount = $data['amount'] / 100; // Converte de centavos para reais
            $transactionId = $data['transaction_id'];
            $gameId = $data['game_id'];

            // Verifica se a transação já existe
            $existingOrder = Order::where('tx', $transactionId)->first();

            if ($existingOrder) {
                return [
                    'success' => false,
                    'message' => 'Transação duplicada',
                ];
            }

            // Credita o saldo do usuário
            Helper::generateGameHistory($user->id, $gameId, 0, $amount, $transactionId, 'win', 'tbs');

            return [
                'success' => true,
                'balance' => $this->getTbsBalance($user) * 100, // Retorna em centavos
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar ganho TBS: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno',
            ];
        }
    }

    /**
     * Processa um estorno da TBS API
     *
     * @param  array  $data
     * @param  User  $user
     * @return array
     */
    protected function processTbsRollback($data, $user)
    {
        try {
            // Verifica se os dados necessários estão presentes
            if (! isset($data['transaction_id']) || ! isset($data['original_transaction_id'])) {
                return [
                    'success' => false,
                    'message' => 'Dados inválidos',
                ];
            }

            $transactionId = $data['transaction_id'];
            $originalTransactionId = $data['original_transaction_id'];

            // Verifica se a transação de rollback já existe
            $existingRollback = Order::where('tx', $transactionId)->first();

            if ($existingRollback) {
                return [
                    'success' => false,
                    'message' => 'Rollback duplicado',
                ];
            }

            // Busca a transação original
            $originalOrder = Order::where('tx', $originalTransactionId)->first();

            if (! $originalOrder) {
                return [
                    'success' => false,
                    'message' => 'Transação original não encontrada',
                ];
            }

            // Processa o rollback
            if ($originalOrder->type == 'bet') {
                // Estorna uma aposta (credita o valor)
                Helper::generateGameHistory($user->id, $originalOrder->game_id, 0, $originalOrder->bet, $transactionId, 'rollback', 'tbs');
            } elseif ($originalOrder->type == 'win') {
                // Estorna um ganho (debita o valor)
                Helper::generateGameHistory($user->id, $originalOrder->game_id, $originalOrder->win, 0, $transactionId, 'rollback', 'tbs');
            }

            return [
                'success' => true,
                'balance' => $this->getTbsBalance($user) * 100, // Retorna em centavos
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao processar rollback TBS: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno',
            ];
        }
    }

    /**
     * Retorna informações de saldo para a TBS API
     *
     * @param  User  $user
     * @return array
     */
    protected function getTbsBalanceInfo($user)
    {
        return [
            'success' => true,
            'balance' => $this->getTbsBalance($user) * 100, // Retorna em centavos
        ];
    }
}
