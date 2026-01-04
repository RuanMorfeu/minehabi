<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Retorna as configurações de bônus para o frontend
     * Atualizado para usar o novo sistema de bônus de influencer
     */
    public function getBonusSettings()
    {
        // Limpa o cache para garantir dados atualizados
        Cache::forget('active_influencer_bonuses');

        // Retorna os bônus de influencer ativos do novo sistema
        $bonuses = \App\Models\InfluencerBonus::where('is_active', true)->get();

        return response()->json($bonuses);
    }
}
