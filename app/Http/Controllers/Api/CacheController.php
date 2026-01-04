<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    /**
     * Retorna a versão atual do cache global
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVersion()
    {
        $version = Cache::get('global_cache_version', 0);

        return response()->json([
            'version' => (string) $version,
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Incrementa a versão do cache global para testes
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incrementVersion()
    {
        $currentVersion = Cache::get('global_cache_version', 0);
        $newVersion = $currentVersion + 1;
        Cache::forever('global_cache_version', $newVersion);

        return response()->json([
            'success' => true,
            'previous_version' => (string) $currentVersion,
            'new_version' => (string) $newVersion,
            'message' => 'Versão do cache incrementada com sucesso!',
        ]);
    }
}
