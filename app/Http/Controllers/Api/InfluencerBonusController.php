<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InfluencerBonus;
use App\Models\InfluencerBonusRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class InfluencerBonusController extends Controller
{
    /**
     * Display a listing of the active influencer bonuses.
     */
    public function index()
    {
        // Cache por 1 hora para melhor performance
        return Cache::remember('active_influencer_bonuses', 3600, function () {
            return InfluencerBonus::active()->get();
        });
    }

    /**
     * Store a newly created influencer bonus in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:influencer_bonuses,code',
            'bonus_percentage' => 'required|numeric|min:0|max:300',
            'max_bonus' => 'nullable|numeric|min:0',
            'min_deposit' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $bonus = InfluencerBonus::create($validated);

        // Limpa o cache
        Cache::forget('active_influencer_bonuses');

        return response()->json($bonus, 201);
    }

    /**
     * Display the specified influencer bonus.
     */
    public function show(InfluencerBonus $influencerBonus)
    {
        return $influencerBonus;
    }

    /**
     * Update the specified influencer bonus in storage.
     */
    public function update(Request $request, InfluencerBonus $influencerBonus)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:influencer_bonuses,code,'.$influencerBonus->id,
            'bonus_percentage' => 'sometimes|numeric|min:0|max:100',
            'max_bonus' => 'nullable|numeric|min:0',
            'min_deposit' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
            'one_time_use' => 'boolean',
            'browser_persistent' => 'boolean',
        ]);

        $influencerBonus->update($validated);

        // Limpa o cache
        Cache::forget('active_influencer_bonuses');

        return response()->json($influencerBonus);
    }

    /**
     * Remove the specified influencer bonus from storage.
     */
    public function destroy(InfluencerBonus $influencerBonus)
    {
        $influencerBonus->delete();

        // Limpa o cache
        Cache::forget('active_influencer_bonuses');

        return response()->json(null, 204);
    }

    /**
     * Find an active bonus by code.
     */
    public function findByCode($code)
    {
        $bonus = InfluencerBonus::active()
            ->where('code', $code)
            ->first();

        if (! $bonus) {
            return response()->json(['message' => 'Bonus not found or inactive'], 404);
        }

        // Verificar se o usuário já resgatou este bônus (se for de uso único)
        $alreadyRedeemed = false;
        if ($bonus->one_time_use && Auth::check()) {
            $alreadyRedeemed = InfluencerBonusRedemption::where('user_id', Auth::id())
                ->where('influencer_bonus_id', $bonus->id)
                ->exists();
        }

        // Adicionar a informação ao objeto de resposta
        $bonus->already_redeemed = $alreadyRedeemed;

        return response()->json($bonus);
    }

    /**
     * Check if the current user has already redeemed a specific bonus.
     */
    public function checkRedemptionStatus($code)
    {
        // Verificação detalhada de autenticação com logs
        // Usando auth('api') para ser consistente com o middleware JwtMiddleWare
        $userId = auth('api')->id();
        $isAuthenticated = auth('api')->check();

        \Log::debug("[BONUS-API] Verificando resgate para código: {$code}");
        \Log::debug('[BONUS-API] Status de autenticação: '.($isAuthenticated ? 'Autenticado' : 'Não autenticado'));
        \Log::debug('[BONUS-API] ID do usuário: '.($userId ?: 'Não disponível'));
        \Log::debug('[BONUS-API] Guarda de autenticação: api');

        $bonus = InfluencerBonus::where('code', $code)->first();

        if (! $bonus) {
            \Log::warning("[BONUS-API] Bônus não encontrado para código: {$code}");

            return response()->json(['already_redeemed' => false, 'message' => 'Bonus not found'], 200);
        }

        \Log::debug("[BONUS-API] Bônus encontrado: ID {$bonus->id}, Nome: {$bonus->name}, Uso único: ".($bonus->one_time_use ? 'Sim' : 'Não'));

        // Se o bônus não for de uso único, nunca estará "já resgatado"
        if (! $bonus->one_time_use) {
            \Log::debug('[BONUS-API] Bônus não é de uso único, retornando não resgatado');

            return response()->json(['already_redeemed' => false, 'message' => 'Bonus is not one-time use'], 200);
        }

        // Se o usuário não estiver autenticado, retorna que não foi resgatado
        // mas indica que a autenticação é necessária para verificar corretamente
        if (! $isAuthenticated) {
            \Log::info("[BONUS-API] Usuário não autenticado, retornando status padrão para código: {$code}");

            return response()->json([
                'already_redeemed' => false,
                'auth_required' => true,
                'message' => 'Authentication required for accurate redemption status',
                'bonus_id' => $bonus->id,
                'bonus_code' => $code,
                'bonus_name' => $bonus->name,
            ], 200);
        }

        // Se chegou aqui, o usuário está autenticado e o bônus é de uso único
        $alreadyRedeemed = InfluencerBonusRedemption::where('user_id', $userId)
            ->where('influencer_bonus_id', $bonus->id)
            ->exists();

        // Log adicional para debug
        \Log::debug("[BONUS-API] Consulta de resgate: user_id={$userId}, bonus_id={$bonus->id}, resultado=".($alreadyRedeemed ? 'Encontrado' : 'Não encontrado'));

        \Log::debug("[BONUS-API] Status de resgate para usuário {$userId}, bônus {$bonus->id}: ".($alreadyRedeemed ? 'Já resgatado' : 'Nunca resgatado'));

        return response()->json([
            'already_redeemed' => $alreadyRedeemed,
            'auth_required' => false,
            'bonus_id' => $bonus->id,
            'bonus_code' => $code,
            'bonus_name' => $bonus->name,
            'user_id' => $userId,
        ]);
    }
}
