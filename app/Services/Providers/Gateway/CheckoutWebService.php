<?php

declare(strict_types=1);

namespace App\Services\Providers\Gateway;

use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\UserDeposit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutWebService
{
    protected $terminalId;

    protected $bearerToken;

    protected $entity;

    protected $XIBMClientId;

    private function generateCredentialsSibs()
    {
        $setting = Gateway::first();
        if (! empty($setting)) {
            $this->terminalId = (int) $setting->getAttributes()['sibs_terminalId'];
            $this->entity = $setting->getAttributes()['sibs_entidade'];
            $this->XIBMClientId = $setting->getAttributes()['sibs_clientId'];
            $this->bearerToken = 'Bearer '.$setting->getAttributes()['sibs_bearerToken'];
        }
    }

    private $urlApiSibis = 'https://api.sibspayments.com/sibs/spg/v2/payments';

    private $url = 'https://api.sibspayments.com/sibs/spg/v2/payments';

    private function generateMultibancoReference($transactionID, $transactionSignature, $amount = null, $user = null, $accept_bonus = false, $influencer_code = null)
    {
        // Verificar se o usuário existe quando fornecido
        if ($user === null && $amount !== null) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $url = "{$this->url}/{$transactionID}/service-reference/generate";

        // dd($url);
        $response = Http::withHeaders([
            'Authorization' => "Digest $transactionSignature",
            'X-IBM-Client-Id' => $this->XIBMClientId,
            'Content-Type' => 'application/json',
        ])->post($url, []);

        if ($response->failed()) {
            return response()->json(['error' => 'Erro ao gerar referência MULTIBANCO', 'details' => $response->json()], 500);
        }

        // Se usuário e valor foram fornecidos, criar registro de depósito
        if ($user !== null && $amount !== null) {
            // Log para depurar a criação do depósito
            Log::info('CheckoutWebService::generateMultibancoReference - Criando depósito', [
                'transactionID' => $transactionID,
                'amount' => $amount,
                'user_id' => $user->id,
            ]);

            try {
                // Criar registro em UserDeposit com os detalhes da SIBS
                $transaction_sibs = UserDeposit::create([
                    'transaction_id' => $transactionID,
                    'deposit_method' => 'MULTIBANCO',
                    'amount' => $amount,
                    'user_id' => $user->id,
                    'meta' => $response->json(), // Salva toda a resposta da SIBS
                ]);

                // Log para depurar o código de influencer
                Log::info('[DEBUG-INFLUENCER-BONUS] CheckoutWebService::generateMultibancoReference - Código de influencer', [
                    'influencer_code' => $influencer_code,
                ]);

                // Criar registro principal em Deposit
                $deposit = Deposit::create([
                    'payment_id' => $transactionID,
                    'user_id' => $user->id,
                    'type' => 'MULTIBANCO',
                    'amount' => $amount,
                    'currency' => 'EUR',
                    'symbol' => '€',
                    'accept_bonus' => $accept_bonus,
                    'meta' => [
                        'influencer_code' => $influencer_code,
                    ],
                ]);

                Log::info('Depósito Multibanco e UserDeposit criados com sucesso.', ['deposit_id' => $deposit->id, 'user_deposit_id' => $transaction_sibs->id]);

            } catch (\Exception $e) {
                Log::error('Erro ao salvar depósito Multibanco ou UserDeposit: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'transaction_id' => $transactionID,
                    'exception' => $e,
                ]);
                throw $e; // Re-lança a exceção
            }
        }

        $json = $response->json();
        $referencia = $json['paymentReference']['reference'];
        $entidade = $json['paymentReference']['entity'];

        return [
            'transactionID' => $transactionID,
            'transactionSignature' => $transactionSignature,
            'method' => 'REFERENCE',
            'reference' => $response->json(),
            'referencia' => $referencia,
            'entidade' => $entidade,
            'success' => true,
        ];
    }

    private function generateMBWayReference($transactionID, $transactionSignature, $phoneNumber, $amount, $user, $accept_bonus = false, $influencer_code = null)
    {
        // dd($transactionID, $transactionSignature, $phoneNumber, $amount, $user);
        // Verificar se o usuário existe
        if (! $user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $url = "https://api.sibspayments.com/api/v2/payments/{$transactionID}/mbway-id/purchase";
        // dd(['customerPhone' => $phoneNumber]);
        $response = Http::withHeaders([
            'Authorization' => "Digest $transactionSignature",
            'X-IBM-Client-Id' => $this->XIBMClientId,
            'Content-Type' => 'application/json',
        ])->post($url, ['customerPhone' => $phoneNumber]);

        if ($response->failed()) {
            return response()->json(['error' => 'Erro ao gerar referência MBWAY', 'details' => $response->json()], 500);
        }

        // Log para depurar a criação do depósito MBWay
        Log::info('CheckoutWebService::generateMBWayReference - Criando depósito', [
            'transactionID' => $transactionID,
            'amount' => $amount,
            'user_id' => $user->id,
            'phoneNumber' => $phoneNumber,
        ]);

        try {
            // Criar registro em UserDeposit com os detalhes da SIBS
            $transaction_sibs = UserDeposit::create([
                'transaction_id' => $transactionID,
                'deposit_method' => 'MBWAY',
                'amount' => $amount,
                'user_id' => $user->id,
                'meta' => $response->json(), // Salva toda a resposta da SIBS
            ]);

            // Log para depurar o código de influencer
            Log::info('[DEBUG-INFLUENCER-BONUS] CheckoutWebService::generateMBWayReference - Código de influencer', [
                'influencer_code' => $influencer_code,
            ]);

            // Criar registro principal em Deposit
            $deposit = Deposit::create([
                'payment_id' => $transactionID,
                'user_id' => $user->id,
                'type' => 'MBWAY',
                'amount' => $amount,
                'currency' => 'EUR',
                'symbol' => '€',
                'accept_bonus' => $accept_bonus,
                'meta' => [
                    'influencer_code' => $influencer_code,
                ],
            ]);

            Log::info('Depósito MBWay e UserDeposit criados com sucesso.', ['deposit_id' => $deposit->id, 'user_deposit_id' => $transaction_sibs->id]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar depósito MBWay ou UserDeposit: '.$e->getMessage(), [
                'user_id' => $user->id,
                'transaction_id' => $transactionID,
                'exception' => $e,
            ]);
            throw $e; // Re-lança a exceção
        }

        // Retornar array formatado
        return [
            'transactionID' => $transactionID,
            'method' => 'MBWAY', // Indica o método
            'success' => true, // Indica sucesso na criação da transação inicial
            // Não há entidade/referência para MBWay nesta fase
        ];
    }

    public function formatString(?string $input = null)
    {
        if (empty($input)) {
            return null;
        }

        return '351#'.$input;

    }

    public function createPayment($user, $method, $amount, ?string $phone = null, ?bool $accept_bonus = null, ?string $influencer_code = null)
    {
        \Log::info('[DEBUG-INFLUENCER-BONUS] CheckoutWebService::createPayment - Iniciando pagamento', [
            'method' => $method,
            'amount' => $amount,
            'accept_bonus' => $accept_bonus,
            'influencer_code' => $influencer_code,
        ]);

        self::generateCredentialsSibs();

        try {
            // Verificar se o usuário existe
            if (! $user) {
                return response()->json(['error' => 'Usuário não encontrado'], 404);
            }

            $paymentMethod = '';

            switch ($method) {
                case 'mbway':
                case 'mbway-sibs':
                    $paymentMethod = 'MBWAY';
                    break;
                case 'mbank':
                case 'mbank-sibs':
                    $paymentMethod = 'REFERENCE';
                    break;
            }

            $amount = (float) $amount;
            $phoneNumber = $this->formatString($phone);

            $transactionData = [
                'merchant' => [
                    'terminalId' => $this->terminalId,
                    'channel' => 'web',
                    'merchantTransactionId' => 'Order ID: '.time(),
                ],
                'transaction' => [
                    'transactionTimestamp' => now()->toIso8601String(),
                    'description' => 'Deposit',
                    'moto' => false,
                    'paymentType' => 'PURS',
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'EUR',
                    ],
                    'paymentMethod' => ['REFERENCE', 'CARD', 'MBWAY'],
                    'paymentReference' => [
                        'initialDatetime' => now()->toIso8601String(),
                        'finalDatetime' => now()->addDays(30)->toIso8601String(),
                        'maxAmount' => ['value' => $amount, 'currency' => 'EUR'],
                        'minAmount' => ['value' => $amount, 'currency' => 'EUR'],
                        'entity' => $this->entity,
                    ],
                ],
            ];

            // dd($transactionData);

            $response = Http::withHeaders([
                'Authorization' => $this->bearerToken,
                'X-IBM-Client-Id' => $this->XIBMClientId,
                'Content-Type' => 'application/json',
            ])->post($this->urlApiSibis, $transactionData);

            if ($response->failed()) {
                \Log::error('[DEBUG-MULTIBANCO-SIBS] Falha na API Sibs', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);

                return ['error' => 'Falha ao criar pagamento', 'details' => $response->json(), 'success' => false];
            }

            $data = $response->json();
            $transactionID = $data['transactionID'];
            $transactionSignature = $data['transactionSignature'];

            if ($paymentMethod === 'REFERENCE') {
                return $this->generateMultibancoReference($transactionID, $transactionSignature, $amount, $user, $accept_bonus, $influencer_code);
            } elseif ($paymentMethod === 'MBWAY') {
                return $this->generateMBWayReference($transactionID, $transactionSignature, $phoneNumber, $amount, $user, $accept_bonus, $influencer_code);
            }

            return [
                'transactionID' => $transactionID,
                'transactionSignature' => $transactionSignature,
                'method' => $paymentMethod,
                'success' => true,
            ];
        } catch (\Exception $e) {
            \Log::error('[DEBUG-MULTIBANCO-SIBS] Erro no createPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['error' => 'Erro ao processar pagamento', 'details' => $e->getMessage(), 'success' => false];
        }
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public static function register($amount, $customerTaxId, $paymentMethod)
    {
        $client = Http::withHeaders([
            'Authorization' => 'Bearer wDRuo6eBx9GqYwossx3aOuiYn8f9InJFHL6VZBoO8a039b5a',
        ])->post('https://sheckout.com/api/v1/checkout', [
            'amount' => $amount,
            'currency_id' => 'eur',
            // 'identifier' => 1244,
            'paymentable_id' => $paymentMethod,
            'customer' => [
                'name' => auth()->user()->name,
                'tax_id' => $customerTaxId,
            ],
        ]);

        if ($client->successful()) {
            return $client->json();
        }

        return false;
    }

    public static function pay($amount, $customerTaxId, $paymentMethod, $phone)
    {
        try {
            $client = Http::withHeaders([
                // 'Authorization' => 'Bearer wDRuo6eBx9GqYwossx3aOuiYn8f9InJFHL6VZBoO8a039b5a',
            ])->post('https://sheckout.com/api/merchant/order', [
                'amount' => $amount,
                'currency' => 'EUR',
                'merchant_slug' => 'deibet',
                'identifier' => 'swde',
                'payment_type' => $paymentMethod,
                'customer' => [
                    'name' => auth()->user()->name,
                    'tax_id' => $customerTaxId,
                    'phone' => $phone,
                ],
            ]);

            if ($client->successful()) {
                return $client->json();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return false;
    }
}
