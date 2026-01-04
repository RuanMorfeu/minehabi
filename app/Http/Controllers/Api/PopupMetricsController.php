<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthPopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PopupMetricsController extends Controller
{
    /**
     * Registra uma visualização do pop-up
     */
    public function recordView(Request $request)
    {
        $request->validate([
            'popup_id' => 'required|exists:auth_popups,id',
        ]);

        $popupId = $request->popup_id;
        $userId = auth('api')->id() ?? 'guest';
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

        // Chave única para evitar múltiplas visualizações do mesmo usuário
        $uniqueViewKey = "popup_view_{$popupId}_{$userId}_{$ipAddress}";

        try {
            $popup = AuthPopup::findOrFail($popupId);

            // Sempre incrementa visualizações totais
            $popup->increment('total_views');

            // Verifica se é uma visualização única (cache de 24 horas)
            if (! Cache::has($uniqueViewKey)) {
                $popup->increment('unique_views');
                Cache::put($uniqueViewKey, true, now()->addHours(24));
            }

            // Atualiza última exibição
            $popup->update(['last_shown_at' => now()]);

            Log::info('Pop-up visualizado', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'total_views' => $popup->total_views,
                'unique_views' => $popup->unique_views,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Visualização registrada com sucesso',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar visualização do pop-up', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao registrar visualização',
            ], 500);
        }
    }

    /**
     * Registra um clique no pop-up
     */
    public function recordClick(Request $request)
    {
        $request->validate([
            'popup_id' => 'required|exists:auth_popups,id',
        ]);

        $popupId = $request->popup_id;
        $userId = auth('api')->id() ?? 'guest';

        try {
            $popup = AuthPopup::findOrFail($popupId);

            // Incrementa cliques totais
            $popup->increment('total_clicks');

            Log::info('Pop-up clicado', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'total_clicks' => $popup->total_clicks,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Clique registrado com sucesso',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar clique do pop-up', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao registrar clique',
            ], 500);
        }
    }

    /**
     * Registra um resgate bem-sucedido
     */
    public function recordRedemption(Request $request)
    {
        $request->validate([
            'popup_id' => 'required|exists:auth_popups,id',
            'success' => 'required|boolean',
        ]);

        $popupId = $request->popup_id;
        $success = $request->success;
        $userId = auth('api')->id() ?? 'guest';

        try {
            $popup = AuthPopup::findOrFail($popupId);

            // Incrementa resgates totais
            $popup->increment('total_redemptions');

            // Se foi bem-sucedido, incrementa resgates bem-sucedidos
            if ($success) {
                $popup->increment('successful_redemptions');
            }

            Log::info('Resgate de pop-up registrado', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'success' => $success,
                'total_redemptions' => $popup->total_redemptions,
                'successful_redemptions' => $popup->successful_redemptions,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Resgate registrado com sucesso',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar resgate do pop-up', [
                'popup_id' => $popupId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro ao registrar resgate',
            ], 500);
        }
    }
}
