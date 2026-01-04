<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Http\Controllers\Integrations\AresSMSService;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

class UserDepositService
{
    public static function getKey()
    {
        $gateway = Gateway::first();
        $settings = Setting::first();

        return [
            'eupago_uri' => $gateway->eupago_uri,
            'eupago_id' => $gateway->eupago_id,
            'eupago_secret' => $gateway->eupago_secret,
            'eupago_api_key' => $gateway->eupago_api_key,
            'default_gateway' => $settings->default_gateway ?? 'eupago',
            'mbway_gateway' => $settings->mbway_gateway ?? $settings->default_gateway ?? 'eupago',
            'multibanco_gateway' => $settings->multibanco_gateway ?? $settings->default_gateway ?? 'eupago',
        ];
    }

    public static function auth()
    {
        $uri = self::getKey()['eupago_uri'];
        $id = self::getKey()['eupago_id'];
        $secret = self::getKey()['eupago_secret'];
        $api_key = self::getKey()['eupago_api_key'];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->post($uri.'api/auth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $id,
                'client_secret' => $secret,
            ]);

        if ($response->successful()) {
            $json = $response->json();

            return $json['access_token'];
        }

        return 'error';
    }

    private static function generateDeposit($idTransaction, $amount, $type, $accept_bonus, $influencer_code = null)
    {
        $userId = auth('api')->user()->id;
        $wallet = Wallet::where('user_id', $userId)->first();

        Deposit::create([
            'payment_id' => $idTransaction,
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'currency' => $wallet->currency,
            'symbol' => $wallet->symbol,
            'status' => 0,
            'accept_bonus' => $accept_bonus,
            'meta' => [
                'influencer_code' => $influencer_code,
            ],
        ]);
    }

    public static function depositar($banco, $valor, $accept_bonus, $phone = '', $influencer_code = '')
    {
        \Log::info('[DEBUG-INFLUENCER-BONUS] UserDepositService::depositar - Iniciando depósito', [
            'banco' => $banco,
            'valor' => $valor,
            'accept_bonus' => $accept_bonus,
            'phone' => $phone,
            'influencer_code' => $influencer_code,
        ]);

        $settings = self::getKey();

        // Verificar qual gateway usar para cada método de pagamento
        if ($banco == 'mbank') {
            $gateway = $settings['multibanco_gateway'];

            if ($gateway == 'sibs') {
                return self::depositarMultibancoSibs($valor, $accept_bonus, $influencer_code);
            } elseif ($gateway == 'mollie') {
                return self::depositarMultibancoMollie($valor, $accept_bonus, $influencer_code);
            } else {
                return self::depositarMultibancoEupago($valor, $accept_bonus, $influencer_code);
            }
        }

        if ($banco == 'mbway') {
            $gateway = $settings['mbway_gateway'];

            if ($gateway == 'sibs') {
                return self::depositarMbwaySibs($valor, $accept_bonus, $phone, $influencer_code);
            } elseif ($gateway == 'mollie') {
                return self::depositarMbwayMollie($valor, $accept_bonus, $phone, $influencer_code);
            } else {
                return self::depositarMbwayEupago($valor, $accept_bonus, $phone, $influencer_code);
            }
        }

        return ['erro' => true, 'mensagem' => 'Método de pagamento não suportado'];
    }

    /**
     * Processa depósito via Multibanco usando o gateway Eupago
     */
    private static function depositarMultibancoEupago($valor, $accept_bonus, $influencer_code = '')
    {
        $settings = self::getKey();
        $uri = $settings['eupago_uri'];
        $api_key = $settings['eupago_api_key'];

        $token = self::auth();
        $idUnico = uniqid();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->post($uri.'clientes/rest_api/multibanco/create', [
                'chave' => $api_key,
                'valor' => $valor,
                'id' => $idUnico,
            ]);

        if ($response->successful()) {
            $json = $response->json();
            if ($json['sucesso'] == true) {
                self::generateDeposit($json['referencia'], \Helper::amountPrepare($valor), 'mbank', $accept_bonus, $influencer_code);

                // Enviar SMS para notificar sobre o Multibanco gerado
                $user = auth('api')->user();
                $entidade = $json['entidade'];
                $referencia = $json['referencia'];

                $payload = [
                    'name' => ! empty($user->name) ? $user->name : null,
                    'email' => ! empty($user->email) ? $user->email : null,
                    'type' => 'new-pix',
                    'event_identify' => 'Pix Gerado',
                    'phone' => ! empty($user->phone) ? $user->phone : null,
                    'username' => ! empty($user->username) ? $user->username : null,
                    'checkout' => "ENT: $entidade | REF: $referencia",
                    'value' => $valor,
                ];
                AresSMSService::sendSMS($payload);

                return $json;
            }
        }

        return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento via Multibanco (Eupago)'];
    }

    /**
     * Processa depósito via Multibanco usando o gateway Sibs
     */
    private static function depositarMultibancoSibs($valor, $accept_bonus, $influencer_code = '')
    {
        \Log::info('[DEBUG-MULTIBANCO-SIBS] UserDepositService::depositarMultibancoSibs - Iniciando processo', [
            'valor' => $valor,
            'accept_bonus' => $accept_bonus,
            'influencer_code' => $influencer_code,
            'user_id' => auth('api')->id(),
        ]);

        try {
            // Usar o serviço CheckoutWebService para processar pagamento via Sibs
            \Log::info('[DEBUG-MULTIBANCO-SIBS] Criando CheckoutWebService');
            $checkoutService = new \App\Services\Providers\Gateway\CheckoutWebService;

            \Log::info('[DEBUG-MULTIBANCO-SIBS] Chamando createPayment', [
                'method' => 'mbank-sibs',
                'valor' => $valor,
                'accept_bonus' => $accept_bonus,
                'influencer_code' => $influencer_code,
            ]);

            $result = $checkoutService->createPayment(auth('api')->user(), 'mbank-sibs', $valor, null, $accept_bonus, $influencer_code);

            \Log::info('[DEBUG-MULTIBANCO-SIBS] Resultado do createPayment', [
                'result' => $result,
            ]);

            // Verificar se o resultado é um array ou JsonResponse
            if (is_object($result) && method_exists($result, 'getData')) {
                // É um JsonResponse, extrair os dados
                $resultData = $result->getData(true);
            } else {
                // É um array
                $resultData = $result;
            }

            if (isset($resultData['success']) && $resultData['success']) {
                \Log::info('[DEBUG-MULTIBANCO-SIBS] Pagamento processado com sucesso, enviando SMS');

                // Enviar SMS para notificar sobre o Multibanco gerado via Sibs
                $user = auth('api')->user();
                $entidade = $resultData['paymentReference']['entity'] ?? 'N/A';
                $referencia = $resultData['paymentReference']['reference'] ?? 'N/A';

                $payload = [
                    'name' => ! empty($user->name) ? $user->name : null,
                    'email' => ! empty($user->email) ? $user->email : null,
                    'type' => 'new-pix',
                    'event_identify' => 'Pix Gerado',
                    'phone' => ! empty($user->phone) ? $user->phone : null,
                    'username' => ! empty($user->username) ? $user->username : null,
                    'checkout' => "ENT: $entidade | REF: $referencia",
                    'value' => $valor,
                ];
                AresSMSService::sendSMS($payload);

                \Log::info('[DEBUG-MULTIBANCO-SIBS] Processo concluído com sucesso');

                return $resultData;
            }

            \Log::error('[DEBUG-MULTIBANCO-SIBS] Pagamento não foi bem-sucedido', [
                'result' => $resultData,
            ]);

        } catch (\Exception $e) {
            \Log::error('[DEBUG-MULTIBANCO-SIBS] Erro durante o processo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['erro' => true, 'mensagem' => 'Erro interno: '.$e->getMessage()];
        }

        return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento via Multibanco (Sibs)'];
    }

    /**
     * Processa depósito via MBWay usando o gateway Eupago
     */
    private static function depositarMbwayEupago($valor, $accept_bonus, $phone, $influencer_code = '')
    {
        $settings = self::getKey();
        $uri = $settings['eupago_uri'];
        $api_key = $settings['eupago_api_key'];

        $token = self::auth();
        $idUnico = uniqid();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'ApiKey '.$api_key,
        ])
            ->post($uri.'api/v1.02/mbway/create', [
                'payment' => [
                    'amount' => [
                        'currency' => 'EUR',
                        'value' => floatval($valor),
                    ],
                    'identifier' => $token,
                    'customerPhone' => $phone,
                    'countryCode' => '351',
                ],
            ]);

        if ($response->successful()) {
            $json = $response->json();
            if ($json['transactionStatus'] == 'Success') {
                self::generateDeposit($json['reference'], \Helper::amountPrepare($valor), 'mbway', $accept_bonus, $influencer_code);

                // Retornar resposta específica para MBWay (sem referencia/entidade)
                return [
                    'erro' => false,
                    'mensagem' => 'Pagamento MBWay via EuPago criado com sucesso',
                    'transactionStatus' => $json['transactionStatus'],
                    'reference' => $json['reference'],
                    'mbway_success' => true, // Flag específica para MBWay
                ];
            }
        }

        return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento via MBWay (Eupago)'];
    }

    /**
     * Processa depósito via MBWay usando o gateway Sibs
     */
    private static function depositarMbwaySibs($valor, $accept_bonus, $phone, $influencer_code = '')
    {
        \Log::info('[DEBUG-MBWAY-SIBS] UserDepositService::depositarMbwaySibs - Iniciando processo', [
            'valor' => $valor,
            'accept_bonus' => $accept_bonus,
            'phone' => $phone,
            'influencer_code' => $influencer_code,
            'user_id' => auth('api')->id(),
        ]);

        try {
            // Usar o serviço CheckoutWebService para processar pagamento via Sibs
            \Log::info('[DEBUG-MBWAY-SIBS] Criando CheckoutWebService');
            $checkoutService = new \App\Services\Providers\Gateway\CheckoutWebService;

            \Log::info('[DEBUG-MBWAY-SIBS] Chamando createPayment', [
                'method' => 'mbway-sibs',
                'valor' => $valor,
                'phone' => $phone,
                'accept_bonus' => $accept_bonus,
                'influencer_code' => $influencer_code,
            ]);

            $result = $checkoutService->createPayment(auth('api')->user(), 'mbway-sibs', $valor, $phone, $accept_bonus, $influencer_code);

            \Log::info('[DEBUG-MBWAY-SIBS] Resultado do createPayment', [
                'result' => $result,
            ]);

            // Verificar se o resultado é um array ou JsonResponse
            if (is_object($result) && method_exists($result, 'getData')) {
                // É um JsonResponse, extrair os dados
                $resultData = $result->getData(true);
            } else {
                // É um array
                $resultData = $result;
            }

            if (isset($resultData['success']) && $resultData['success']) {
                \Log::info('[DEBUG-MBWAY-SIBS] Pagamento processado com sucesso, enviando SMS');

                // Retornar resposta específica para MBWay (sem referencia/entidade)
                \Log::info('[DEBUG-MBWAY-SIBS] Processo concluído com sucesso');

                return [
                    'erro' => false,
                    'mensagem' => 'Pagamento MBWay via Sibs criado com sucesso',
                    'transactionStatus' => 'Success',
                    'transactionID' => $resultData['transactionID'] ?? null,
                    'mbway_success' => true, // Flag específica para MBWay
                ];
            }

            \Log::error('[DEBUG-MBWAY-SIBS] Pagamento não foi bem-sucedido', [
                'result' => $resultData,
            ]);

        } catch (\Exception $e) {
            \Log::error('[DEBUG-MBWAY-SIBS] Erro durante o processo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['erro' => true, 'mensagem' => 'Erro interno: '.$e->getMessage()];
        }

        return ['erro' => true, 'mensagem' => 'Erro ao processar pagamento via MBWay (Sibs)'];
    }

    /**
     * Processa depósito via MBWay usando o gateway Mollie
     *
     * @param  float  $valor
     * @param  bool  $accept_bonus
     * @param  string  $phone
     * @param  string|null  $influencer_code
     * @return array
     */
    public static function depositarMbwayMollie($valor, $accept_bonus, $phone, $influencer_code = null)
    {
        // Mollie ignora o parâmetro phone para MBWay - cliente insere no checkout
        $request = (object) [
            'amount' => $valor,
            'mollie_method' => 'mbway',
            'accept_bonus' => $accept_bonus,
            'influencer_code' => $influencer_code,
        ];

        $result = \App\Traits\Gateways\MollieTrait::createMolliePayment($request);

        if ($result['status']) {
            return [
                'erro' => false,
                'mensagem' => 'Pagamento MBWay via Mollie criado com sucesso',
                'payment_id' => $result['payment_id'],
                'checkout_url' => $result['checkout_url'],
                'deposit_id' => $result['deposit_id'],
            ];
        }

        return ['erro' => true, 'mensagem' => $result['message'] ?? 'Erro ao processar pagamento via MBWay (Mollie)'];
    }

    /**
     * Processa depósito via Multibanco usando o gateway Mollie
     *
     * @param  float  $valor
     * @param  bool  $accept_bonus
     * @param  string|null  $influencer_code
     * @return array
     */
    public static function depositarMultibancoMollie($valor, $accept_bonus, $influencer_code = null)
    {
        $request = (object) [
            'amount' => $valor,
            'mollie_method' => 'multibanco',
            'accept_bonus' => $accept_bonus,
            'influencer_code' => $influencer_code,
        ];

        $result = \App\Traits\Gateways\MollieTrait::createMolliePayment($request);

        if ($result['status']) {
            $response = [
                'erro' => false,
                'mensagem' => 'Pagamento Multibanco via Mollie criado com sucesso',
                'payment_id' => $result['payment_id'],
                'deposit_id' => $result['deposit_id'],
            ];

            // Se tem detalhes do Multibanco, usar mesmo formato que EuPago/Sibs
            if (isset($result['multibanco_details'])) {
                $response['entidade'] = $result['multibanco_details']['entity'];
                $response['referencia'] = $result['multibanco_details']['reference'];
            } else {
                // Para outros casos, incluir checkout_url
                $response['checkout_url'] = $result['checkout_url'];
            }

            return $response;
        }

        return ['erro' => true, 'mensagem' => $result['message'] ?? 'Erro ao processar pagamento via Multibanco (Mollie)'];
    }
}
