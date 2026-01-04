<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDeposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        // dd(auth('api')->user());
        $user = User::query()->where('id', auth('api')->id())->first();

        // Verificar se o usuário está bloqueado para depósitos
        if ($user && $user->block_deposits) {
            return response()->json([
                'status' => false,
                'message' => 'Sua conta está temporariamente impedida de realizar depósitos. Entre em contato com o suporte.',
            ], 403);
        }

        // $user = User::query()->where('email', $request->email)->first();

        $checkout = new \App\Services\Providers\Gateway\CheckoutWebService;

        // Log para depurar os parâmetros enviados para o método createPayment
        \Log::info('DepositController::store - Parâmetros:', [
            'deposit_method_slug' => $request->get('deposit_method_slug', 'MBWAY'),
            'amount' => $request->get('amount'),
            'phone' => $request->get('phone'),
            'accept_bonus' => $request->get('accept_bonus', false),
        ]);

        $response = $checkout->createPayment($user, $request->get('deposit_method_slug', 'MBWAY'), $request->get('amount'), $request->get('phone', null), $request->get('accept_bonus', false));

        // Log da resposta direta do CheckoutWebService (Mantido para debug)
        \Log::info('DepositController::store - Resposta direta do CheckoutWebService:', [
            'response_type' => gettype($response),
            'content' => method_exists($response, 'getData') ? $response->getData(true) : 'Não é JsonResponse ou falhou getData()',
        ]);

        // Log detalhado da resposta ANTES de enviar
        $logData = 'Resposta não é JsonResponse';
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $logData = json_encode($response->getData(true)); // Pega o array/objeto de dados
        }
        \Log::info('DepositController::store - ENVIANDO PARA FRONTEND:', ['json_data' => $logData]);

        // Retorna diretamente a resposta do CheckoutWebService
        return $response;

        /*$userDeposit = UserDeposit::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'transaction_id' => 123,
            'deposit_method' => 'MBWAY',
            'meta' => [],
        ]);
        return $userDeposit;*/
    }
}
