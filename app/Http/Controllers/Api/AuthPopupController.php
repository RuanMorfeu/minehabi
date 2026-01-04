<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthPopup;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\AuthPopupService;
use App\Services\PlayFiverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthPopupController extends Controller
{
    protected $popupService;

    public function __construct(AuthPopupService $popupService)
    {
        $this->popupService = $popupService;
    }

    /**
     * Obter todos os pop-ups ativos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllActivePopups(Request $request)
    {
        // Obter o código do influencer da requisição
        $influencerCode = $request->header('X-Influencer-Code') ?: $request->query('influencer_code');

        // Logs detalhados para debug - comentados para produção
        // \Log::info('Buscando pop-ups com código de influencer: ' . ($influencerCode ?: 'nenhum'));
        // \Log::info('Headers da requisição:', $request->headers->all());
        // \Log::info('Query params da requisição:', $request->query());
        // \Log::info('Valor do parâmetro influencer_code: ' . $request->query('influencer_code'));

        $popups = $this->popupService->getAllActivePopups($influencerCode);

        if ($popups->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up ativo disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popups' => $popups->map(function ($popup) {
                return $this->formatPopup($popup);
            }),
        ]);
    }

    /**
     * Obter pop-up para exibição após login
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoginPopup()
    {
        $popup = $this->popupService->getActiveLoginPopup();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up para exibição após registro
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRegisterPopup()
    {
        $popup = $this->popupService->getActiveRegisterPopup();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up com base no tipo de usuário
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopupByUserType(Request $request)
    {
        $userType = $request->input('user_type', 'all');
        $popup = $this->popupService->getPopupByUserType($userType);

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up para usuários com depósito realizado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithDepositPopup()
    {
        $popup = $this->popupService->getActiveWithDepositPopup();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up para usuários sem depósito realizado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithoutDepositPopup()
    {
        $popup = $this->popupService->getActiveWithoutDepositPopup();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up para usuários afiliados com link
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAffiliatePopup()
    {
        $popup = $this->popupService->getActiveAffiliatePopup();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Obter pop-up com base no status de depósito do usuário autenticado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopupByDepositStatus()
    {
        $popup = $this->popupService->getPopupByDepositStatus();

        if (! $popup) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum pop-up disponível',
            ]);
        }

        return response()->json([
            'success' => true,
            'popup' => $this->formatPopup($popup),
        ]);
    }

    /**
     * Formatar dados do pop-up para o frontend
     *
     * @return array
     */
    protected function formatPopup(AuthPopup $popup)
    {
        return [
            'id' => $popup->id,
            'title' => $popup->title,
            'message' => $popup->message,
            'image' => $popup->image,
            'button_text' => $popup->button_text,
            'redirect_url' => $popup->redirect_url,
            'show_only_once' => $popup->show_only_once,
            'require_redemption' => $popup->require_redemption,
            'target_user_type' => $popup->target_user_type,
            'influencer_code' => $popup->influencer_code,
            // Campos de freespin
            'game_free_rounds_active_popup' => $popup->game_free_rounds_active_popup ?? false,
            'game_code_rounds_free_popup' => $popup->game_code_rounds_free_popup ?? null,
            'game_name_rounds_free_popup' => $popup->game_name_rounds_free_popup ?? null,
            'rounds_free_popup' => $popup->rounds_free_popup ?? 0,
            // Campos de crédito inicial
            'initial_credit_active' => $popup->initial_credit_active ?? false,
            'initial_credit_amount' => $popup->initial_credit_amount ?? 0,
            'browser_persistent' => $popup->browser_persistent ?? false,
        ];
    }

    /**
     * Processar o freespin quando o usuário clica no botão do popup
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPopupFreespin(Request $request)
    {
        try {
            // Validar os dados da requisição
            $request->validate([
                'popup_id' => 'required|exists:auth_popups,id',
            ]);

            // O middleware auth.jwt já garante que o usuário está autenticado
            $user = auth('api')->user();
            $popupId = $request->input('popup_id');

            // Buscar o popup pelo ID
            $popup = AuthPopup::findOrFail($popupId);

            // Verificar se o usuário já resgatou este freespin
            $existingRedemption = \App\Models\PopupFreespinRedemption::where('user_id', $user->id)
                ->where('popup_id', $popupId)
                ->first();

            if ($existingRedemption) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já resgatou as rodadas grátis deste popup anteriormente',
                    'already_redeemed' => true,
                ], 400);
            }

            $response = [
                'success' => true,
                'message' => '',
                'popup_id' => $popupId,
                'credit_added' => false,
                'credit_amount' => 0,
            ];

            // Verificar se o popup tem freespin ativo
            if ($popup->game_free_rounds_active_popup) {
                // Preparar os dados para o freespin
                $dados = [
                    'username' => $user->email,
                    'game_code' => $popup->game_code_rounds_free_popup,
                    'rounds' => $popup->rounds_free_popup,
                ];

                // Chamar o serviço para conceder as rodadas gratuitas
                $result = PlayFiverService::RoundsFree($dados);

                // Registrar o resgate do freespin
                \App\Models\PopupFreespinRedemption::create([
                    'user_id' => $user->id,
                    'popup_id' => $popupId,
                    'game_code' => $popup->game_code_rounds_free_popup,
                    'rounds' => $popup->rounds_free_popup,
                    'success' => $result['status'],
                    'response_message' => $result['message'],
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);

                $response['success'] = $result['status'];
                $response['message'] = $result['message'];
                $response['game_code'] = $popup->game_code_rounds_free_popup;
                $response['game_name'] = $popup->game_name_rounds_free_popup;
                $response['rounds'] = $popup->rounds_free_popup;
            }

            // Adicionar crédito inicial à carteira do usuário se configurado no popup
            if (isset($popup->initial_credit_active) && $popup->initial_credit_active) {
                $creditAmount = $popup->initial_credit_amount > 0 ? $popup->initial_credit_amount : 0.01;
                $wallet = Wallet::where('user_id', $user->id)->first();

                if ($wallet) {
                    $wallet->increment('balance', $creditAmount);

                    // Registrar a transação
                    Transaction::create([
                        'user_id' => $user->id,
                        'payment_id' => 'popup_credit_'.$user->id.'_'.$popupId,
                        'status' => 1,
                        'amount' => $creditAmount,
                        'type' => 'deposit',
                        'gateway' => 'system',
                        'currency' => 'EUR',
                        'info' => json_encode(['description' => 'Crédito bônus do popup #'.$popupId]),
                    ]);

                    $response['credit_added'] = true;
                    $response['credit_amount'] = (float) $creditAmount;

                    if (empty($response['message'])) {
                        $response['message'] = 'Crédito de €'.$creditAmount.' adicionado à sua carteira!';
                    } else {
                        $response['message'] .= ' E um crédito de €'.$creditAmount.' foi adicionado à sua carteira!';
                    }
                }
            }

            // Se nenhuma ação foi realizada (nem freespin nem crédito)
            if (! $popup->game_free_rounds_active_popup && (! isset($popup->initial_credit_active) || ! $popup->initial_credit_active)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este popup não possui ações configuradas (freespin ou crédito)',
                ], 400);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Erro ao processar freespin de popup: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar rodadas grátis: '.$e->getMessage(),
            ], 500);
        }
    }
}
