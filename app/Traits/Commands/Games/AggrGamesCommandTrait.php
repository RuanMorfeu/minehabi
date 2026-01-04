<?php

namespace App\Traits\Commands\Games;

use App\Models\GamesKey;
use App\Models\GameSpins;
use Illuminate\Support\Facades\Http;

trait AggrGamesCommandTrait
{
    /**
     * @var string
     */
    protected static $agentApi;

    protected static $agentPassword;

    protected static $apiEndpoint;

    /**
     * @return void
     */
    public static function getCredentials(): bool
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

    /**
     * Create User
     * Metodo para criar novo usuário
     *
     * @return bool
     */
    public static function getGames()
    {
        if (self::getCredentials()) {
            $data_get_games = [
                'api_login' => self::$agentApi,
                'api_password' => self::$agentPassword,
                'method' => 'getGameList',
                'show_additional' => true,
                'show_systems' => 0,
                'list_type' => 1,
                // "freerounds_supported" => true,
                'currency' => 'EUR',
            ];

            $res_games = Http::post(self::$apiEndpoint, $data_get_games);

            if ($res_games->successful()) {
                $json = $res_games->json();

                $games = $json['response'];

                foreach ($games as $game) {
                    // Se o campo 'details' for um array, converte para JSON
                    if (isset($game['details']) && is_array($game['details'])) {
                        $game['details'] = json_encode($game['details']);
                    }

                    $categorias = ['bgaming', 'evoplay', 'evolution', 'onlyplay', 'pgsoft', 'spribe', 'pragmaticplay', 'pragmaticplaylive', 'hacksaw'];

                    if (in_array($game['category'], $categorias)) {
                        // Atualiza o registro se ele já existir ou cria um novo
                        GameSpins::updateOrCreate(
                            ['id_hash' => $game['id_hash']], // Condição para identificar o registro único
                            $game                  // Dados para inserir/atualizar
                        );

                        echo "Criado {$game['name']} -- {$game['category']}\n";
                    }
                    // print_r($game);

                }
            }
        }
    }
}
