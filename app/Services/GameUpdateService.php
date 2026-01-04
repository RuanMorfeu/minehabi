<?php

namespace App\Services;

use App\Models\GamesKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GameUpdateService
{
    protected $gamesKey;

    protected $baseUrl = 'https://api.supremabet.online';

    public function __construct()
    {
        $this->gamesKey = GamesKey::first();
    }

    public function updateGameList(string $providerCode = 'PGSOFT')
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'method' => 'game_list',
                'agent_code' => 'ruan001',
                'agent_token' => 'd016d2b9f0b4b1cdad945dce4785c6f7',
                'provider_code' => 'PGSOFT',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to update game list', [
                'provider' => $providerCode,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error updating game list', [
                'provider' => $providerCode,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getProviderList()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'method' => 'provider_list',
                'agent_code' => $this->gamesKey->agent_code,
                'agent_token' => $this->gamesKey->agent_token,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get provider list', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error getting provider list', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
