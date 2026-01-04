<?php

declare(strict_types=1);

namespace App\Services\Providers;

use App\Models\Provider;
use App\Models\ProviderGame;
use App\Services\Casino\PlayFiverCasinoService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class XpDealService
{
    public static function originals($identifier): array
    {
        // $game = ProviderGame::query()->where('identifier',$identifier)->first();
        return [
            'content' => [
                'game' => [
                    'url' => '/modal/'.$identifier,
                ],
            ],
        ];
    }

    public static function listGames()
    {
        $data = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://tbs2api.dark-a.com/API/', [
            'cmd' => 'gamesList',
            'hall' => env('PROVIDER_GAME_ID', 'aa'),
            'key' => env('PROVIDER_GAME_KEY', 'aaa'),
            'login' => env('PROVIDER_GAME_ACCOUNT', 'aa'),
        ]);

        return $data->json();
    }

    /**
     * @throws ConnectionException
     */
    public static function logs()
    {
        $cmd = 'gameSessionsLog';

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://tbs2api.dark-a.com/API/', [
            'cmd' => 'gameSessionsLog',
            'hall' => env('PROVIDER_GAME_ID'),
            'key' => env('PROVIDER_GAME_KEY'),
            'sessionsId' => '201345875',
        ]);

        return $response->json();
    }

    public static function getBmActive($data): string
    {
        $bm = (int) Arr::get($data['meta'], 'bm', 0);

        if ($bm) {
            return '15|10.00';
        }

        return '0';
    }

    public static function play($identifier)
    {
        $game = ProviderGame::where('identifier', $identifier)->first();
        $provider = PlayFiverCasinoService::playLaunch($game->slug);

        // dd($provider);

        return [
            'content' => [
                'game' => [
                    'url' => $provider['launch_url'],
                ],
            ],
        ];
        /* $response = Http::withHeaders([
             'Accept' => 'application/json',
             'Content-Type' => 'application/json',
         ])->post('https://tbs2api.dark-a.com/API/openGame/', [
             'cmd' => 'openGame',
             'gameId' => (string) $identifier,
             'hall' => '3205222',
             'key' => '1qa2wszxc',
             'language' => 'pt_BR',
             'continent' => 'brl',
             'domain' => env('APP_URL'),
             //'sessionId' => Session::getId(),
             'exitUrl' => url('/close'),
             'login' => auth()->user()->id,
             'demo' => '0',
             'jackpots' => '0',
             //   'bm' => self::getBmActive($game),
         ]);

         return $response->json();*/
    }

    public static function store($provider)
    {
        $providerData = Provider::where('provider', $provider)->first();
        if ($providerData) {
            return $providerData->id;
        }

        $providerData = Provider::create([
            'provider' => $provider,
            'active' => 1,
            'icon' => null,
            'slug' => Str::slug($provider),
        ]);

        return $providerData->id;
    }

    public static function storeProviders(): void
    {
        $data = self::listGames()['content']['gameList'];

        foreach ($data as $d) {
            ProviderGame::updateOrCreate(
                [
                    'identifier' => $d['id'],
                ],
                [
                    'active' => 1,
                    'provider_id' => self::store($d['title']),
                    'game' => $d['name'],
                    'identifier' => $d['id'],
                    'thumbnail' => self::uploadFromUrl($d['img']),
                ]
            );
        }
    }

    public static function update(ProviderGame $providerGame, $data)
    {
        $providerGame->meta = $data;
        $providerGame->tags = [
            $data['categories'],
            $data['title'],
            $data['label'],
        ];
        $providerGame->thumbnail = self::uploadFromUrl($data['img']);

        return $providerGame->save();
    }

    private static function uploadFromUrl($url, $name = null): ?string
    {
        try {
            $client = new Client;
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
                $fileName = 'assets/games/'.$fileName.'.'.$extension;

                if (Storage::disk('public')->exists($fileName)) {
                    return $fileName;
                }

                // Salva o arquivo usando o nome extraído da URL
                Storage::disk('public')->put($fileName, $fileContent);

                return $fileName;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
