<?php

namespace App\Services\Casino;

use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

class PlayFiverCasinoService
{
    protected static $secretPlayFiver;

    protected static $codePlayFiver;

    protected static $tokenPlayFiver;

    public static function credentialFiverPlay()
    {
        self::$secretPlayFiver = 'dfc0938d-eb44-43ac-aa0c-578480bd2ae6';
        self::$codePlayFiver = 'deibei';
        self::$tokenPlayFiver = '16dd8265-da64-49cd-a2dd-f607aaaaee9b';
    }

    public static function playLaunch($slug)
    {
        self::credentialFiverPlay();
        $postArray = [
            'agentToken' => self::$tokenPlayFiver,
            'secretKey' => self::$secretPlayFiver,
            'user_code' => \auth()->user()->email,
            'game_code' => $slug,
            'user_balance' => Wallet::where('user_id', \auth()->user()->id),
        ];

        $client = Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ])->post('https://api.playfiver.com/api/v2/game_launch', $postArray);

        return $client->json();
    }
}
