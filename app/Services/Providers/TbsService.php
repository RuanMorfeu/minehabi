<?php

namespace App\Services\Providers;

use App\Models\Game;
use App\Models\Provider;
use App\Traits\TbsTrait;
use Illuminate\Support\Facades\Log;

class TbsService
{
    use TbsTrait;

    protected $allowedProviders = ['evolution', 'spribe', 'galaxsys'];

    /**
     * Obtém todos os jogos da API TBS
     *
     * @return array
     */
    public function getAllGames()
    {
        try {
            $credentials = $this->getTbsCredentials();
            $endpoint = $credentials['endpoint'].'/games';

            $response = $this->makeRequest('GET', $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer '.$credentials['key'],
                    'Accept' => 'application/json',
                ],
            ]);

            if (isset($response['data'])) {
                return $response['data'];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao obter jogos TBS: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Sincroniza todos os jogos da API TBS
     *
     * @param  bool  $dryRun
     * @return array
     */
    public function sync($dryRun = false)
    {
        $games = $this->getAllGames();
        $totalGames = count($games);
        $createdGames = 0;
        $updatedGames = 0;
        $createdProviders = 0;
        $updatedProviders = 0;
        $filteredGames = 0;

        $providers = [];

        foreach ($games as $game) {
            // Filtra apenas os provedores permitidos
            $providerCode = strtolower($game['provider'] ?? '');
            if (! in_array($providerCode, $this->allowedProviders)) {
                continue;
            }

            $filteredGames++;

            if (! $dryRun) {
                // Cria ou atualiza o provedor
                $provider = $this->store($game);

                if ($provider) {
                    if ($provider['created']) {
                        $createdProviders++;
                    } else {
                        $updatedProviders++;
                    }

                    // Cria ou atualiza o jogo
                    $gameResult = $this->update($game, $provider['provider']);

                    if ($gameResult) {
                        if ($gameResult['created']) {
                            $createdGames++;
                        } else {
                            $updatedGames++;
                        }
                    }
                }
            }

            // Armazena os provedores para retorno
            if (! isset($providers[$providerCode])) {
                $providers[$providerCode] = [
                    'name' => $game['provider'] ?? '',
                    'games' => 1,
                ];
            } else {
                $providers[$providerCode]['games']++;
            }
        }

        return [
            'total_games' => $totalGames,
            'filtered_games' => $filteredGames,
            'created_games' => $createdGames,
            'updated_games' => $updatedGames,
            'created_providers' => $createdProviders,
            'updated_providers' => $updatedProviders,
            'providers' => $providers,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * Cria ou atualiza um provedor
     *
     * @param  array  $game
     * @return array|null
     */
    protected function store($game)
    {
        try {
            $providerCode = strtolower($game['provider'] ?? '');
            $providerName = $game['provider'] ?? '';

            if (empty($providerCode)) {
                return null;
            }

            $provider = Provider::where('code', $providerCode)->first();
            $created = false;

            if (! $provider) {
                $provider = new Provider;
                $provider->code = $providerCode;
                $provider->name = $providerName;
                $provider->distribution = 'tbs';
                $provider->save();
                $created = true;
            } else {
                // Atualiza o distribution para 'tbs' se necessário
                if ($provider->distribution !== 'tbs') {
                    $provider->distribution = 'tbs';
                    $provider->save();
                }
            }

            return [
                'provider' => $provider,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao criar provedor TBS: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Atualiza ou cria um jogo
     *
     * @param  array  $game
     * @param  Provider  $provider
     * @return array|null
     */
    protected function update($game, $provider)
    {
        try {
            $gameCode = $game['id'] ?? '';
            $gameName = $game['name'] ?? '';

            if (empty($gameCode) || empty($gameName)) {
                return null;
            }

            $existingGame = Game::where('game_code', $gameCode)
                ->where('provider_id', $provider->id)
                ->first();

            $created = false;

            if (! $existingGame) {
                $existingGame = new Game;
                $existingGame->game_code = $gameCode;
                $existingGame->provider_id = $provider->id;
                $created = true;
            }

            $existingGame->game_name = $gameName;
            $existingGame->distribution = 'tbs';
            $existingGame->cover = $game['img'] ?? null;
            $existingGame->active = true;
            $existingGame->save();

            return [
                'game' => $existingGame,
                'created' => $created,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar jogo TBS: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Faz uma requisição para a API TBS
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return array
     */
    protected function makeRequest($method, $url, $options = [])
    {
        try {
            $client = new \GuzzleHttp\Client;
            $response = $client->request($method, $url, $options);
            $contents = $response->getBody()->getContents();

            return json_decode($contents, true);
        } catch (\Exception $e) {
            Log::error('Erro na requisição TBS: '.$e->getMessage());

            return [];
        }
    }
}
