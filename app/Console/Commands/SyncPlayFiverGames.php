<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\GamesKey;
use App\Models\Provider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncPlayFiverGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:playfiver-games';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar jogos da PlayFiver com o banco de dados local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de jogos da PlayFiver...');

        // Obter credenciais da PlayFiver
        $gamesKey = GamesKey::first();
        if (! $gamesKey || ! $gamesKey->playfiver_token || ! $gamesKey->playfiver_secret) {
            $this->error('Credenciais da PlayFiver não encontradas!');

            return 1;
        }

        // Buscar jogos da API
        $response = Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ])->get('https://api.playfivers.com/api/v2/games', [
            'agentToken' => $gamesKey->playfiver_token,
            'secretKey' => $gamesKey->playfiver_secret,
        ]);

        if (! $response->successful()) {
            $this->error('Falha ao buscar jogos da API PlayFiver: '.$response->status());

            return 1;
        }

        $data = $response->json();

        if (! isset($data['data']) || empty($data['data'])) {
            $this->error('Nenhum jogo encontrado na resposta da API!');

            return 1;
        }

        $games = $data['data'];

        // Garantir que o provedor PlayFiver exista
        $provider = Provider::firstOrCreate([
            'code' => 'play_fiver',
        ], [
            'name' => 'PlayFiver',
            'status' => 1,
        ]);

        $syncedCount = 0;
        $createdCount = 0;

        foreach ($games as $gameData) {
            // Verificar se é do provedor OFICIAL - PG SOFT
            if (! isset($gameData['provider']['name']) || $gameData['provider']['name'] !== 'OFICIAL - PG SOFT') {
                continue;
            }

            // Extrair dados do jogo
            $gameCode = $gameData['game_code'] ?? null;
            $gameName = $gameData['name'] ?? 'Jogo sem nome';
            $imageUrl = $gameData['image_url'] ?? null;

            if (! $gameCode) {
                $this->warn("Jogo sem código ignorado: {$gameName}");

                continue;
            }

            // Buscar ou criar o jogo
            $game = Game::where('game_code', $gameCode)->first();

            if (! $game) {
                $game = new Game;
                $game->game_code = $gameCode;
                $game->distribution = 'play_fiver';
                $game->status = 1;
                $game->show_home = 1;
                $game->views = 0;
                $game->provider_id = $provider->id;
                $createdCount++;
            }

            // Atualizar dados
            $game->game_name = $gameName;

            // Definir URL da imagem
            if ($imageUrl) {
                // Se for URL completa, usar diretamente
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $game->cover = $imageUrl;
                    $game->home_cover = $imageUrl;
                } else {
                    // Se for path relativo, montar URL completa
                    $game->cover = 'https://api.playfivers.com/'.ltrim($imageUrl, '/');
                    $game->home_cover = 'https://api.playfivers.com/'.ltrim($imageUrl, '/');
                }
            }

            $game->save();
            $syncedCount++;

            $this->line("Sincronizado: {$gameName} ({$gameCode})");
        }

        $this->info("\nSincronização concluída!");
        $this->info("Total de jogos sincronizados: {$syncedCount}");
        $this->info("Novos jogos criados: {$createdCount}");

        return 0;
    }
}
