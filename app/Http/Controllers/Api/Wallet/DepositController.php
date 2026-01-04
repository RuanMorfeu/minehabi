<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\User\UserDepositService;
use App\Services\User\UserWalletService;
use App\Traits\Gateways\DigitoPayTrait;
use App\Traits\Gateways\EuPagoTrait;
use App\Traits\Gateways\EzzepayTrait;
use App\Traits\Gateways\SibsTrait;
use App\Traits\Gateways\SuitpayTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    use DigitoPayTrait;
    use EuPagoTrait;
    use EzzepayTrait;
    use SibsTrait;
    use SuitpayTrait;

    public function storePayment(Request $request)
    {
        // Verificar se o usuário está bloqueado para depósitos
        $user = Auth::user();
        if ($user && $user->block_deposits) {
            return response()->json([
                'status' => false,
                'message' => 'Sua conta está temporariamente impedida de realizar depósitos. Entre em contato com o suporte.',
            ], 403);
        }

        \Log::info('[DEBUG-INFLUENCER-BONUS] DepositController::storePayment - Request recebido', [
            'request' => $request->all(),
            'has_influencer_code' => $request->has('influencer_code'),
            'influencer_code' => $request->input('influencer_code'),
            'meta' => $request->input('meta'),
        ]);

        $info = $request->all();
        $phone = isset($info['phone']) ? $info['phone'] : '';
        $accept_bonus = isset($info['accept_bonus']) ? $info['accept_bonus'] : false;
        $influencer_code = isset($info['influencer_code']) ? $info['influencer_code'] : '';

        // Também verifica se o código está no meta
        if (empty($influencer_code) && isset($info['meta']['influencer_code'])) {
            $influencer_code = $info['meta']['influencer_code'];
        }

        \Log::info('[DEBUG-INFLUENCER-BONUS] DepositController::storePayment - Dados processados', [
            'accept_bonus' => $accept_bonus,
            'influencer_code' => $influencer_code,
        ]);

        $response = UserDepositService::depositar($info['deposit_method_slug'], $info['amount'], $accept_bonus, $phone, $influencer_code);

        return response()->json($response);
    }

    public function getMethods(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            UserWalletService::wallets()
        );
    }

    public function getOptions(): \Illuminate\Http\JsonResponse
    {
        // MBWay e Multibanco sempre disponíveis (independente do status Mollie)
        $paymentTypes = [
            [
                'slug' => 'mbway',
                'icon' => '/assets/images/payments/mbway.svg',
                'deposit_bounty' => 0,
                'fields' => ['label' => 'Phone'],
            ],
            [
                'slug' => 'mbank',
                'icon' => '/assets/images/payments/multibanco.svg',
                'deposit_bounty' => 0,
            ],
        ];

        // Verificar se Mollie está ativo e adicionar métodos Mollie específicos (exceto MBWay e Multibanco)
        $setting = \App\Models\Gateway::first(); // Busca o primeiro gateway onde o admin salva
        if ($setting && $setting->mollie_active) {
            $molliePaymentTypes = [
                [
                    'slug' => 'mollie-creditcard',
                    'icon' => '/assets/images/payments/Design-sem-nome-_16_.webp',
                    'deposit_bounty' => 0,
                    'mollie_method' => 'creditcard',
                ],
                [
                    'slug' => 'mollie-applepay',
                    'icon' => '/assets/images/payments/apple-pay-main-logo-2025-horizontal-1-removebg-preview.png',
                    'deposit_bounty' => 0,
                    'mollie_method' => 'applepay',
                ],
                // [
                //     'slug' => 'mollie-googlepay',
                //     'icon' => '/assets/images/payments/Design sem nome (14).png',
                //     'deposit_bounty' => 0,
                //     'mollie_method' => 'googlepay',
                // ],
                [
                    'slug' => 'mollie-paybybank',
                    'icon' => '/assets/images/payments/Bancário.webp',
                    'deposit_bounty' => 0,
                    'mollie_method' => 'paybybank',
                ],
                // [
                //     'slug' => 'mollie-banktransfer',
                //     'icon' => '/assets/images/payments/mollie-banktransfer.svg',
                //     'deposit_bounty' => 0,
                //     'mollie_method' => 'banktransfer',
                // ],
            ];

            $paymentTypes = array_merge($paymentTypes, $molliePaymentTypes);
        }

        return response()->json([
            'payment_types' => $paymentTypes,
            'value' => [],
            'tabs' => ['25', '30', '40'],
            'currency' => '€',
        ]);
    }

    /*** @param Request $request
     * @return array|false[]
     */
    public function submitPayment(Request $request)
    {
        // Obter dados do usuário para envio de SMS
        $user = Auth::user();
        $response = null;

        switch ($request->gateway) {
            case 'suitpay':
                $response = self::requestQrcode($request);
                break;
            case 'digitopay':
                $response = self::requestQrcodeDigito($request);
                break;
            case 'ezzepay':
                $response = self::requestQrcodeEzze($request);
                break;
        }

        // O envio de SMS foi movido para os métodos específicos de depósito

        return $response;
    }

    /*** Show the form for creating a new resource.
     */
    public function consultStatusTransactionPix(Request $request)
    {
        return self::consultStatusTransaction($request);
    }

    /*** Display a listing of the resource.
     */
    public function index()
    {
        $deposits = Deposit::whereUserId(auth('api')->id())->paginate();

        return response()->json(['deposits' => $deposits], 200);
    }

    public function callbackEupago(Request $request)
    {
        $json = $request->all();

        $identificador = $json['identificador'];
        $entidade = $json['entidade'];
        $ref = $json['referencia'];
        $transacao_id = $json['transacao'];

        if (empty($identificador) || empty($entidade) || empty($ref) || empty($transacao_id)) {
            return response()->json(['status' => false, 'message' => 'Erro, falta preencher algum campo!']);
        }

        self::finalizePaymentEuPago($ref);

    }

    public function callbackSibs(Request $request)
    {
        $json = $request->all();

        $transactionID = $json['transactionID'];
        $status = $json['paymentStatus'];
        $entidade = $json['entidade'];
        $valor = $json['valor'];

        if (intval($valor) <= 0 || empty($entidade) || empty($status) || empty($transactionID)) {
            return response()->json(['status' => false, 'message' => 'Erro, falta preencher algum campo!']);
        }

        if ($status == 'Success') {
            self::finalizePaymentSibs($transactionID);
        }
    }

    /**
     * Verifica se o usuário já fez algum depósito
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasDeposits()
    {
        $userId = auth('api')->id();

        // Verifica se tem algum depósito
        $hasDeposits = Deposit::where('user_id', $userId)
            ->where('status', 1) // Depósitos aprovados
            ->exists();

        // Conta quantos depósitos o usuário já fez
        $depositCount = Deposit::where('user_id', $userId)
            ->where('status', 1) // Depósitos aprovados
            ->count();

        return response()->json([
            'has_deposits' => $hasDeposits,
            'deposit_count' => $depositCount,
        ]);
    }
}
