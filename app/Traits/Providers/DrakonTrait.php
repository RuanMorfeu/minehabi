<?php

namespace App\Traits\Providers;

use App\Helpers\Core as Helper;
use App\Models\Category;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait DrakonTrait
{
    /**
     * @var string
     */
    public static $apiUriDrakon = 'https://gator.drakon.casino/api/v1/';

    /**
     * @return false|mixed
     */
    public static function DrakonAuthentication()
    {
        $gateway = GamesKey::first();
        if (! empty($gateway)) {
            $basicToken = base64_encode($gateway->getAttributes()['drakon_agent_token'].':'.$gateway->getAttributes()['drakon_agent_secret']);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer  '.$basicToken,
            ])->post(self::$apiUriDrakon.'auth/authentication');
            if ($response->successful()) {
                $json = $response->json();

                return $json['access_token'];
            }

            return false;
        }

        return false;
    }

    /**
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getGamesAndProvider()
    {
        self::getDrakonGames();
    }

    /**
     * @return void
     */
    public static function getDrakonProvider()
    {
        if ($token = self::DrakonAuthentication()) {
            $response = Http::withToken($token)->get(self::$apiUriDrakon.'games/provider');
            if ($response->successful()) {
                $json = $response->json();
                if ($json['status']) {
                    foreach ($json['providers'] as $provider) {

                        if (! empty($provider['code']) && ! empty($provider['name'])) {
                            $data = [
                                'code' => $provider['code'],
                                'name' => $provider['name'],
                                'rtp' => $provider['rtp'] ?? 95,
                                'status' => 1,
                                'distribution' => 'drakon',
                            ];

                            $providerCheck = Provider::where('code', $provider['code'])
                                ->where('distribution', 'drakon')
                                ->first();

                            if (empty($providerCheck)) {
                                Provider::create($data);
                                echo 'Provedor salvo com sucesso: '.$provider['name'].' ('.$provider['code'].")\n";
                            } else {
                                echo 'Provedor já existe: '.$provider['name'].' ('.$provider['code'].")\n";
                            }
                        }

                    }
                }
            }
        }
    }

    /**
     * @return void
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getDrakonGames()
    {
        if ($token = self::DrakonAuthentication()) {
            $response = Http::withToken($token)->get(self::$apiUriDrakon.'games/all');

            if ($response->successful()) {
                $json = $response->json();
                if ($json['status'] == 'success') {
                    // Obter categorias existentes
                    $categoryTodos = Category::where('name', 'Todos')->first();
                    $categorySlots = Category::where('name', 'Slots')->first();
                    $categoryAoVivo = Category::where('name', 'Ao vivo')->first();
                    $categoryPopular = Category::where('name', 'Popular')->first();
                    $categoryRoletas = Category::where('name', 'Roletas')->first();

                    // Lista de provedores permitidos e suas categorias
                    $allowedProviders = [
                        '100hp' => [$categoryTodos->id, $categorySlots->id],
                        'Aviatrix' => [$categoryTodos->id, $categorySlots->id],
                        'Bgaming' => [$categoryTodos->id, $categorySlots->id],
                        'creedz' => [$categoryTodos->id, $categoryAoVivo->id],
                        'Evolution' => [$categoryTodos->id, $categoryAoVivo->id],
                        'Evoplay' => [$categoryTodos->id, $categorySlots->id],
                        'Galaxsys' => [$categoryTodos->id, $categorySlots->id],
                        'Sagaming' => [$categoryTodos->id, $categoryAoVivo->id],
                        'Smartsoft' => [$categoryTodos->id, $categorySlots->id],
                        'Spribe' => [$categoryTodos->id, $categorySlots->id],
                    ];

                    // Agrupar jogos por provedor
                    $groupedGames = [];
                    foreach ($json['games'] as $game) {
                        $originalProviderName = $game['provider_game'];
                        $normalizedProviderName = null;

                        // Verificar se o provedor está na lista de permitidos ou é parte de um grupo
                        foreach (array_keys($allowedProviders) as $allowedProvider) {
                            // Verificar correspondência exata ou parcial para creedz e Evolution
                            if ($allowedProvider === '100hp' && stripos($originalProviderName, '100hp') !== false) {
                                $normalizedProviderName = '100hp';
                                break;
                            } elseif ($allowedProvider === 'Aviatrix' && stripos($originalProviderName, 'Aviatrix') !== false) {
                                $normalizedProviderName = 'Aviatrix';
                                break;
                            } elseif ($allowedProvider === 'Bgaming' && stripos($originalProviderName, 'Bgaming') !== false) {
                                $normalizedProviderName = 'Bgaming';
                                break;
                            } elseif ($allowedProvider === 'creedz' && stripos($originalProviderName, 'creedz') !== false) {
                                $normalizedProviderName = 'creedz';
                                break;
                            } elseif ($allowedProvider === 'Evolution' && stripos($originalProviderName, 'Evolution') !== false) {
                                $normalizedProviderName = 'Evolution';
                                break;
                            } elseif ($allowedProvider === 'Evoplay' && stripos($originalProviderName, 'Evoplay') !== false) {
                                $normalizedProviderName = 'Evoplay';
                                break;
                            } elseif ($allowedProvider === 'Galaxsys' && stripos($originalProviderName, 'Galaxsys') !== false) {
                                $normalizedProviderName = 'Galaxsys';
                                break;
                            } elseif ($allowedProvider === 'Sagaming' && stripos($originalProviderName, 'Sagaming') !== false) {
                                $normalizedProviderName = 'Sagaming';
                                break;
                            } elseif ($allowedProvider === 'Smartsoft' && stripos($originalProviderName, 'Smartsoft') !== false) {
                                $normalizedProviderName = 'Smartsoft';
                                break;
                            } elseif (($allowedProvider === 'Spribe' && stripos($originalProviderName, 'Spribe') !== false) ||
                                     (stripos($originalProviderName, 'Aviator') !== false)) { // Adicionar Aviator ao Spribe
                                $normalizedProviderName = 'Spribe';
                                break;
                            }
                        }

                        // Se o provedor não está na lista de permitidos, pular este jogo
                        if ($normalizedProviderName === null) {
                            continue;
                        }

                        // Adicionar o jogo ao grupo do provedor normalizado
                        if (! isset($groupedGames[$normalizedProviderName])) {
                            $groupedGames[$normalizedProviderName] = [];
                        }

                        $groupedGames[$normalizedProviderName][] = $game;
                    }

                    // Processar jogos por provedor
                    foreach ($groupedGames as $providerName => $games) {
                        // Verificar se o provedor existe
                        $provider = Provider::where('distribution', 'drakon')
                            ->where('name', $providerName)
                            ->first();

                        // Se o provedor não existe, criar
                        if (empty($provider)) {
                            $provider = Provider::create([
                                'code' => strtolower($providerName),
                                'name' => $providerName,
                                'rtp' => 95,
                                'status' => 1,
                                'distribution' => 'drakon',
                            ]);
                            echo "Provedor criado: {$providerName}\n";
                        }

                        // Processar jogos deste provedor
                        foreach ($games as $game) {
                            // Verificar se o jogo já existe
                            $gameCheck = Game::where('game_code', $game['game_id'])
                                ->where('provider_id', $provider->id)
                                ->first();

                            if (empty($gameCheck)) {
                                // Criar o jogo com as categorias corretas
                                $createdGame = self::CreateGame($provider, $game);

                                // Adicionar categorias ao jogo
                                if ($createdGame && isset($allowedProviders[$providerName])) {
                                    $createdGame->categories()->sync($allowedProviders[$providerName]);
                                    echo "Categorias adicionadas ao jogo: {$game['game_name']} do provedor {$providerName}\n";
                                }
                            } else {
                                echo "Jogo já existe: {$game['game_name']} ({$game['game_id']}) do provedor {$provider->name}\n";

                                // Atualizar categorias de jogos existentes
                                if (isset($allowedProviders[$providerName])) {
                                    $gameCheck->categories()->sync($allowedProviders[$providerName]);
                                    echo "Categorias atualizadas para o jogo existente: {$game['game_name']}\n";
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return Game|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function CreateGame($provider, $game)
    {
        $checkGame = Game::where('provider_id', $provider->id)->where('game_code', $game['game_id'])->where('distribution', 'drakon')->first();
        if (empty($checkGame)) {
            // Usar a URL da imagem diretamente da API
            $image = null;
            if (isset($game['banner']) && ! empty($game['banner'])) {
                // Usar a URL da imagem diretamente da API em vez de fazer download
                $image = $game['banner'];
            }

            $data = [
                'provider_id' => $provider->id,
                'game_id' => $game['game_id'],
                'game_code' => $game['game_id'],
                'game_name' => $game['game_name'],
                'game_type' => $game['game_type'],
                'technology' => 'html5',
                'distribution' => 'drakon',
                'rtp' => $game['rtp'] ?? 95,
                'cover' => $image,  // URL da imagem diretamente da API
                'status' => 1,      // Status ativo conforme solicitado
                'show_home' => 0,   // Exibir na home desativado conforme solicitado
            ];

            $createdGame = Game::create($data);
            echo 'Jogo salvo com sucesso: '.$game['game_name'].' ('.$game['game_id'].') do provedor '.$provider->name." com imagem da API\n";

            return $createdGame;
        }

        return null;
    }

    /**
     * @return string|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function uploadFromUrlDrakon($url, $name = null)
    {
        try {
            $client = new \GuzzleHttp\Client;
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                $fileContent = $response->getBody();

                // Extrai o nome do arquivo e a extensão da URL
                $parsedUrl = parse_url($url);
                $pathInfo = pathinfo($parsedUrl['path']);
                // $fileName = $pathInfo['filename'] ?? 'file_' . time(); // Nome do arquivo
                $fileName = $name ?? $pathInfo['filename'];
                $extension = $pathInfo['extension'] ?? 'png'; // Extensão do arquivo

                // Monta o nome do arquivo com o prefixo e a extensão
                $fileName = 'drakon/'.$fileName.'.'.$extension;

                // Salva o arquivo usando o nome extraído da URL
                Storage::disk('public')->put($fileName, $fileContent);

                return $fileName;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return false|mixed
     */
    public function GameLaunchDrakon(Game $game)
    {
        if ($token = self::DrakonAuthentication()) {
            $gateway = GamesKey::first();
            if (! empty($gateway)) {
                $request = \Illuminate\Support\Facades\Http::withToken($token)
                    ->withQueryParameters([
                        'agent_code' => $gateway->getAttributes()['drakon_agent_code'],
                        'agent_token' => $gateway->getAttributes()['drakon_agent_token'],
                        'game_id' => $game->game_id,
                        'type' => 'CHARGED',
                        'currency' => 'BRL',
                        'lang' => 'pt_BR',
                        'user_id' => auth('api')->user()->id,
                    ])->get(self::$apiUriDrakon.'games/game_launch');

                if ($request->successful()) {
                    $data = $request->json();
                    if (! empty($data['game_url'])) {
                        return $data['game_url'];
                    }

                    return false;
                }

                return false;
            }

            return false;
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function WebhookDrakon($request)
    {
        if (! empty($request->method)) {
            switch ($request->method) {
                case 'account_details':
                    return $this->AccountDetailsDrakon($request);
                case 'user_balance':
                    return $this->GetBalanceDrakon($request);
                case 'transaction_bet':
                    return $this->SetTransactionBetDrakon($request);
                case 'transaction_win':
                    return $this->SetTransactionWinDrakon($request);
                case 'refund':
                    return $this->SetRefundDrakon($request);
                default:
                    return response()->json(['error' => 'Método desconhecido'], 400);
            }
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function SetTransactionBetDrakon($request)
    {
        if (isset($request->user_id)) {
            $user = User::with(['wallet'])->find($request->user_id);
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (! empty($user) && ! empty($wallet)) {
                $bet = $request->bet;
                $changeBonus = Helper::DiscountBalance($wallet, $bet);

                if ($changeBonus != 'no_balance') {
                    $hashGenerate = Helper::GenerateHash('hash:'.$request->session_id.'#'.$request->game.'#'.$request->user_id);

                    $checkTransaction = Order::where('type', 'bet')
                        ->where('transaction_id', $request->transaction_id)
                        ->where('hash', $hashGenerate)
                        ->first();

                    if (! empty($checkTransaction)) {
                        if ($checkTransaction->amount == $request->bet && $checkTransaction->game_uuid == $request->game) {
                            $walletResp = Wallet::where('user_id', $request->user_id)->where('active', 1)->first();

                        } else {
                            return response()->json([
                                'status' => false,
                                'error' => 'DOUBLED_BET',
                                'balance' => 0,
                            ]);
                        }
                    } else {
                        // cria uma transação
                        $transactionCreated = self::CreateDrakonTransactions(
                            $request->user_id,
                            $request->session_id,
                            $request->transaction_id,
                            'bet',
                            $changeBonus,
                            $bet,
                            $request->game,
                            $hashGenerate,
                            $request->round_id
                        );
                        if (! empty($transactionCreated)) {
                            Helper::generateGameHistory(
                                $wallet->user_id,
                                'bet',
                                0,
                                $bet,
                                $changeBonus,
                                $request->transaction_id
                            );
                        }
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'error' => 'NO_BALANCE',
                        'balance' => $wallet->total_balance,
                    ]);
                }

                $walletResp = Wallet::where('user_id', $request->user_id)->where('active', 1)->first();

                return response()->json([
                    'status' => true,
                    'balance' => $walletResp->total_balance,
                ]);
            }

            return response()->json([
                'status' => false,
                'error' => 'no_balance',
                'balance' => 0,
            ]);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function SetTransactionWinDrakon($request)
    {
        if (isset($request->user_id)) {
            $user = User::with(['wallet'])->find($request->user_id);
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (! empty($user) && ! empty($wallet)) {
                $bet = $request->bet;
                $win = $request->win;

                $hashGenerate = Helper::GenerateHash('hash:'.$request->session_id.'#'.$request->game.'#'.$request->user_id);

                $checkTransaction = Order::where('type', 'bet')
                    ->where('round_id', $request->round_id)
                    ->where('hash', $hashGenerate)
                    ->first();

                if (! empty($checkTransaction)) {
                    // Gerar o hash para a transação de win
                    $transactionCreated = self::CreateDrakonTransactions(
                        $request->user_id,
                        $request->session_id,
                        $checkTransaction->transaction_id,
                        'win',
                        $checkTransaction->type_money,
                        $win,
                        $request->game,
                        $hashGenerate,
                        $request->round_id
                    );

                    if (! empty($transactionCreated)) {
                        if ($win >= 0) {
                            // Registrar no log para debug
                            Log::info('Chamando generateGameHistory para user_id: '.$wallet->user_id);

                            $historyGenerated = Helper::generateGameHistory(
                                $wallet->user_id,
                                'win',
                                $win,
                                $checkTransaction->amount,
                                $checkTransaction->type_money,
                                $checkTransaction->transaction_id
                            );

                            Log::info('generateGameHistory retornou: '.($historyGenerated ? 'true' : 'false'));

                            // Só incrementa o saldo se o histórico não foi gerado com sucesso
                            // Isso evita duplicação, pois o payWithRollover já incrementa o saldo
                            if (! $historyGenerated) {
                                Log::info('Incrementando saldo manualmente para user_id: '.$wallet->user_id.' no valor: '.$win);
                                $wallet->increment($checkTransaction->type_money, $win);
                                $wallet->save();
                            }

                            // Atualiza o status da transação de win para 1
                            $checkTransaction->status = 1;
                            $checkTransaction->save();

                            $walletResp = Wallet::where('user_id', $request->user_id)->first();

                            return response()->json([
                                'status' => true,
                                'balance' => $walletResp->total_balance,
                            ]);
                        }
                    }
                }
            }
            $walletResp = Wallet::where('user_id', $request->user_id)->first();

            return response()->json([
                'status' => true,
                'balance' => $walletResp->total_balance,
            ]);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function AccountDetailsDrakon($request)
    {
        $user = User::find($request->user_id);
        if (! empty($user)) {
            return response()->json([
                'email' => $user->email,
                'date' => $user->created_at,
            ]);
        }

        return response()->json([
            'status' => false,
            'error' => 'INVALID_USER',
        ], 400);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function GetBalanceDrakon($request)
    {
        Log::info('GetBalanceDrakon', ['request' => $request]);
        $walletResp = Wallet::where('user_id', $request->user_id)->where('active', 1)->first();
        if ($walletResp) {
            return response()->json([
                'status' => 1,
                'balance' => $walletResp->total_balance,
            ]);
        }

        return response()->json([
            'status' => 0,
            'error' => 'INVALID_USER',
        ], 400);
    }

    /**
     * Create Transactions
     * Metodo para criar uma transação
     *
     * @return false
     */
    private static function CreateDrakonTransactions($playerId, $betReferenceNum, $transactionID, $type, $changeBonus, $amount, $game, $hash, $roundID)
    {
        $order = Order::create([
            'user_id' => $playerId,
            'session_id' => $betReferenceNum,
            'transaction_id' => $transactionID,
            'type' => $type,
            'type_money' => $changeBonus,
            'amount' => $amount,
            'providers' => 'drakon',
            'game' => $game,
            'game_uuid' => $game,
            'round_id' => $roundID,
            'hash' => $hash,
        ]);

        // \Log::info('Order: '. $order);

        if ($order) {
            return $order;
        }

        return false;
    }

    private function SetRefundDrakon($request)
    {
        if (isset($request->user_id)) {
            $user = User::with(['wallet'])->find($request->user_id);
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (! empty($user) && ! empty($wallet)) {

                $hashGenerate = Helper::GenerateHash('hash:'.$request->session_id.'#'.$request->game.'#'.$request->user_id);

                $checkTransaction = Order::where('type', 'bet')
                    ->where('round_id', $request->round_id)
                    ->where('hash', $hashGenerate)
                    ->first();

                if (! empty($checkTransaction)) {

                    // / cria uma nova transação de recusado
                    $transaction = self::CreateDrakonTransactions(
                        $request->user_id,
                        $request->session_id,
                        $request->transaction_id,
                        'refund',
                        $checkTransaction->type_money,
                        $checkTransaction->amount,
                        $request->game,
                        $hashGenerate,
                        $request->round_id
                    );

                    if ($transaction) {
                        // $wallet->increment($checkTransaction->type_money, $checkTransaction->amount);
                        $wallet->increment('balance', $checkTransaction->amount);

                        $walletResp = Wallet::where('user_id', $user->id)->first();

                        return response()->json([
                            'status' => true,
                            'balance' => $walletResp->total_balance,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'status' => false,
            'error' => 'NO_AMOUNT',
            'balance' => 0,
        ]);
    }
}
