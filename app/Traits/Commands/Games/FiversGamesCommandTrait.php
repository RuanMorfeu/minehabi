<?php

namespace App\Traits\Commands\Games;

use App\Models\CategoryGame;
use App\Models\Game;
use App\Models\GamesKey;
use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait FiversGamesCommandTrait
{
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

        self::$agentCode = $setting->agent_code;
        self::$agentToken = $setting->agent_token;
        self::$agentSecretKey = $setting->agent_secret_key;
        self::$apiEndpoint = $setting->api_endpoint;

        return true;
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getProvider()
    {
        if (self::getCredentials()) {
            $response = Http::post(self::$apiEndpoint, [
                'method' => 'provider_list',
                'agent_code' => 'ruan2',
                'agent_token' => 'a1c31fd38ea05ccebf1f643f64b81f22',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                echo var_dump($data);

                foreach ($data['providers'] as $provider) {
                    $provider['distribution'] = 'fivers';
                    $checkProvider = Provider::where('code', strtolower($provider['code']))->where('distribution', 'fivers')->first();
                    if (! $checkProvider) {
                        Provider::create($provider);
                    }
                }
            }
        }
    }

    public static function updateImages()
    {
        if (self::getCredentials()) {
            $providers = Provider::where('distribution', 'fivers')->get();
            foreach ($providers as $provider) {
                $response = Http::post(self::$apiEndpoint, [
                    'method' => 'game_list',
                    'agent_code' => 'ruan2',
                    'agent_token' => 'a1c31fd38ea05ccebf1f643f64b81f22',
                    'provider_code' => $provider->code,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['games'])) {
                        foreach ($data['games'] as $game) {
                            $getGame = Game::where('game_code', $game['game_code'])->where('distribution', 'fivers')->first();

                            if ($getGame) { // Check if $getGame is not null

                                $imagemGame = '';
                                if ($provider['code'] == 'booongo' || $provider['code'] == 'evolution' || $provider['code'] == 'pgsoft' || $provider['code'] == 'playson' || $provider['code'] == 'pplive' || $provider['code'] == 'pragmatic') {
                                    if (strtolower($provider['code']) == 'pgsoft') {
                                        $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/'.$game['game_code'].'.jpg' ?? 'https://dei.bet/semfoto.png';
                                    } elseif (strtolower($provider['code']) == 'pragmatic') {
                                        $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/'.$game['game_code'].'.png' ?? 'https://dei.bet/semfoto.png';
                                    } else {
                                        $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/images/'.$game['game_code'].'.jpg' ?? 'https://dei.bet/semfoto.png';
                                    }
                                } else {
                                    $imagemGame = $game['banner'];
                                }

                                $data = [
                                    'cover' => $imagemGame,
                                ];

                                $getGame->update($data);
                                echo $game['game_code']." imagem atualizada com sucesso \n";
                            } else {
                                echo $game['game_code']." não encontrado \n"; // Log if the game is not found
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getGames()
    {
        if (self::getCredentials()) {

            $providers = Provider::where('distribution', 'fivers')->get();

            // echo var_dump($providers);
            foreach ($providers as $provider) {
                $response = Http::post(self::$apiEndpoint, [
                    'method' => 'game_list',
                    'agent_code' => 'ruan2',
                    'agent_token' => 'a1c31fd38ea05ccebf1f643f64b81f22',
                    'provider_code' => $provider->code,
                ]);

                echo var_dump($response->json());

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['games'])) {
                        foreach ($data['games'] as $game) {
                            $checkProvider = Provider::where('code', strtolower($provider['code']))->where('distribution', 'fivers')->first();
                            // $image = self::uploadFromUrl($game['banner'], $game['game_code']);

                            $imagemGame = '';
                            if ($provider['code'] == 'booongo' || $provider['code'] == 'evolution' || $provider['code'] == 'pgsoft' || $provider['code'] == 'playson' || $provider['code'] == 'pplive' || $provider['code'] == 'pragmatic') {
                                if (strtolower($provider['code']) == 'pgsoft') {
                                    $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/'.$game['game_code'].'.jpg' ?? 'https://dei.bet/semfoto.png';
                                } elseif (strtolower($provider['code']) == 'pragmatic') {
                                    $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/'.$game['game_code'].'.png' ?? 'https://dei.bet/semfoto.png';
                                } else {
                                    $imagemGame = 'https://dei.bet/assets_games/'.strtolower($provider['code']).'/images/'.$game['game_code'].'.jpg' ?? 'https://dei.bet/semfoto.png';
                                }
                            } else {
                                $imagemGame = $game['banner'];
                            }

                            $data = [
                                'provider_id' => $checkProvider->id,
                                'game_id' => $game['game_code'],
                                'game_code' => $game['game_code'],
                                'game_name' => $game['game_name'],
                                'technology' => 'html5',
                                'distribution' => 'fivers',
                                'rtp' => 90,
                                'cover' => $imagemGame,
                                'status' => 1,
                            ];

                            $gameId = Game::create($data);

                            $padrao = [
                                'category_id' => 1,
                                'game_id' => $gameId->id,
                            ];

                            CategoryGame::insert($padrao);

                            $nome = $game['game_name'].' -- '.$game['game_code'];

                            echo "$nome jogo criado com sucesso \n";
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function uploadFromUrl($url, $name = null)
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
                $fileName = 'fivers/'.$fileName.'.'.$extension;

                // Salva o arquivo usando o nome extraído da URL
                Storage::disk('public')->put($fileName, $fileContent);

                return $fileName;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
