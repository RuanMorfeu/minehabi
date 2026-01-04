<?php

namespace App\Traits\Gateways;

use App\Helpers\Core as Helper;
use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewDepositNotification;
use App\Services\PlayFiverService;
use Stevebauman\Location\Facades\Location;

trait MollieTrait
{
    protected static string $mollieApiKey;

    /**
     * Generate Credentials
     *
     * @return array|false
     */
    public static function generateCredentials()
    {
        $gateway = Gateway::first();

        // Verificar apenas se gateway existe e tem API key (não verificar mollie_active)
        // Isso permite usar Mollie para MBWay/Multibanco mesmo quando mollie_active=false
        if (! $gateway || ! $gateway->mollie_api_key) {
            \Log::error('[PAYMENT-DEBUG] Gateway não encontrado ou API key não configurada', [
                'gateway_found' => (bool) $gateway,
                'api_key_set' => $gateway ? (bool) $gateway->mollie_api_key : false,
                'active' => $gateway ? $gateway->mollie_active : false,
            ]);

            return false;
        }

        // Inicializar a propriedade estática
        self::$mollieApiKey = $gateway->mollie_api_key;

        return [
            'api_key' => $gateway->mollie_api_key,
            'profile_id' => $gateway->mollie_profile_id,
            'active' => $gateway->mollie_active,
        ];
    }

    /**
     * Create Mollie Payment - Integração completa baseada no EuPago
     *
     * @param  Request  $request
     * @return array
     */
    public static function createMolliePayment($request)
    {
        try {
            $credentials = self::generateCredentials();
            if (! $credentials) {
                return ['status' => false, 'message' => 'Credenciais Mollie não configuradas'];
            }

            $user = auth('api')->user();
            $amount = $request->amount;
            $mollieMethod = $request->mollie_method ?? 'creditcard';

            // Criar ou obter cliente Mollie para pagamentos com um clique
            $mollieCustomerId = self::getOrCreateMollieCustomer($user);

            // Validar método específico
            $validMethods = ['creditcard', 'applepay', 'googlepay', 'mbway', 'multibanco', 'paybybank'];
            if (! in_array($mollieMethod, $validMethods)) {
                return ['status' => false, 'message' => 'Método de pagamento inválido'];
            }

            // Criar depósito no banco (em vez de transação simples)
            $deposit = Deposit::create([
                'user_id' => $user->id,
                'payment_id' => 'tr_mollie_'.time().'_'.$user->id,
                'amount' => $amount,
                'type' => self::getPaymentTypeName($mollieMethod),
                'status' => 0,
                'currency' => 'EUR',
                'accept_bonus' => $request->accept_bonus ?? true,
                'meta' => [
                    'influencer_code' => $request->influencer_code ?? null,
                    'mollie_method' => $mollieMethod,
                ],
            ]);

            // Inicializar cliente Mollie
            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            $paymentData = [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => 'Online Purchase',
                'redirectUrl' => 'https://xpdeal.pt/mollie_redirect.php?payment_id={payment_id}',
                'webhookUrl' => 'https://xpdeal.pt/mollie_webhook.php',
                'method' => $mollieMethod,
                'metadata' => [
                    'user_id' => $user->id,
                    'order_id' => $deposit->id,
                    'accept_bonus' => $request->accept_bonus ?? true,
                    'influencer_code' => $request->influencer_code ?? null,
                ],
            ];

            // Adicionar customerId para pagamentos com um clique (apenas para creditcard)
            if ($mollieCustomerId && $mollieMethod === 'creditcard') {
                $paymentData['customerId'] = $mollieCustomerId;
                $paymentData['sequenceType'] = 'first'; // Necessário para criar mandates
                \Log::info('[MOLLIE-PAYMENT] Usando customerId para pagamento com um clique:', [
                    'user_id' => $user->id,
                    'customer_id' => $mollieCustomerId,
                    'method' => $mollieMethod,
                    'sequence_type' => 'first',
                ]);
            } elseif ($mollieCustomerId && $mollieMethod !== 'creditcard') {
                \Log::info('[MOLLIE-PAYMENT] CustomerId não usado - método não suporta cartões salvos:', [
                    'user_id' => $user->id,
                    'customer_id' => $mollieCustomerId,
                    'method' => $mollieMethod,
                ]);
            }

            $payment = $mollie->payments->create($paymentData);

            // Atualizar payment_id com o ID do Mollie
            $deposit->update(['payment_id' => $payment->id]);

            $result = [
                'status' => true,
                'payment_id' => $payment->id,
                'deposit_id' => $deposit->id,
            ];

            // Para Multibanco, retornar detalhes da referência sem redirecionamento
            if ($mollieMethod === 'multibanco' && isset($payment->details)) {
                $result['multibanco_details'] = [
                    'entity' => $payment->details->entity ?? null,
                    'reference' => $payment->details->reference ?? null,
                    'amount' => $payment->details->amount ?? null,
                ];
            }

            // Para todos os métodos (incluindo Apple Pay), incluir checkout_url
            $result['checkout_url'] = $payment->getCheckoutUrl();

            return $result;

        } catch (\Exception $e) {
            \Log::error('Payment Gateway Error: '.$e->getMessage());

            // Google Pay desabilitado temporariamente
            // if (isset($mollieMethod) && $mollieMethod === 'googlepay') {
            //     \Log::info('[GOOGLE-PAY] Criando pagamento sem método específico para Google Pay');
            //
            //     try {
            //         // Google Pay só funciona no Mollie Hosted Checkout sem especificar método
            //         $googlePayment = $mollie->payments->create([
            //             'amount' => [
            //                 'currency' => 'EUR',
            //                 'value' => number_format($amount, 2, '.', '')
            //             ],
            //             'description' => 'Online Purchase',
            //             'redirectUrl' => 'https://xpdeal.pt/mollie_redirect.php?payment_id={payment_id}',
            //             'webhookUrl' => 'https://xpdeal.pt/mollie_webhook.php',
            //             // NÃO especificar 'method' para Google Pay
            //             'metadata' => [
            //                 'user_id' => $user->id,
            //                 'order_id' => $deposit->id,
            //                 'accept_bonus' => $request->accept_bonus ?? true,
            //                 'influencer_code' => $request->influencer_code ?? null
            //             ]
            //         ]);
            //
            //         $deposit->update(['payment_id' => $googlePayment->id]);
            //
            //         return [
            //             'status' => true,
            //             'payment_id' => $googlePayment->id,
            //             'deposit_id' => $deposit->id,
            //             'checkout_url' => $googlePayment->getCheckoutUrl()
            //         ];
            //     } catch (\Exception $googlePayError) {
            //         \Log::error('[GOOGLE-PAY] Erro ao criar pagamento Google Pay: ' . $googlePayError->getMessage());
            //     }
            // }

            return ['status' => false, 'message' => 'Erro ao processar pagamento: '.$e->getMessage()];
        }
    }

    /**
     * Criar ou obter cliente Mollie para pagamentos com um clique
     *
     * @param  User  $user
     * @return string|null
     */
    public static function getOrCreateMollieCustomer($user)
    {
        try {
            // Se usuário já tem mollie_customer_id, retornar
            if ($user->mollie_customer_id) {
                return $user->mollie_customer_id;
            }

            $credentials = self::generateCredentials();
            if (! $credentials) {
                \Log::warning('[MOLLIE-CUSTOMER] Credenciais não configuradas');

                return null;
            }

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            // Criar cliente no Mollie
            $customer = $mollie->customers->create([
                'name' => $user->name.' '.($user->last_name ?? ''),
                'email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'created_at' => now()->toISOString(),
                ],
            ]);

            // Salvar customer_id no usuário
            $user->update(['mollie_customer_id' => $customer->id]);

            \Log::info('[MOLLIE-CUSTOMER] Cliente criado:', [
                'user_id' => $user->id,
                'customer_id' => $customer->id,
            ]);

            return $customer->id;

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-CUSTOMER] Erro ao criar cliente: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Listar mandatos (cartões salvos) de um cliente
     *
     * @param  string  $customerId
     * @return array
     */
    public static function getCustomerMandates($customerId)
    {
        try {
            $credentials = self::generateCredentials();
            if (! $credentials) {
                return ['status' => false, 'message' => 'Credenciais não configuradas'];
            }

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            // Listar mandatos do cliente
            $mandates = $mollie->mandates->pageForId($customerId);

            $savedCards = [];
            foreach ($mandates as $mandate) {
                // Filtrar apenas mandates de cartão de crédito que estão válidos
                if ($mandate->method === 'creditcard' && $mandate->status === 'valid') {
                    $savedCards[] = [
                        'id' => $mandate->id,
                        'method' => $mandate->method,
                        'cardHolder' => $mandate->details->cardHolder ?? 'N/A',
                        'cardNumber' => $mandate->details->cardNumber ?? 'N/A',
                        'cardLabel' => $mandate->details->cardLabel ?? 'N/A',
                        'createdAt' => $mandate->createdAt,
                    ];
                }
            }

            \Log::info('[MOLLIE-MANDATES] Cartões salvos encontrados:', [
                'customer_id' => $customerId,
                'count' => count($savedCards),
                'cards' => $savedCards,
            ]);

            return [
                'status' => true,
                'savedCards' => $savedCards,
            ];

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-MANDATES] Erro ao buscar cartões salvos: '.$e->getMessage());

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Revoke mandate (delete saved card)
     */
    public static function revokeMandate($mandateId)
    {
        try {
            // Verificar autenticação
            $user = auth('api')->user();
            if (! $user) {
                $user = auth()->user();
            }

            if (! $user) {
                return ['status' => false, 'message' => 'Usuário não autenticado'];
            }

            if (! $user->mollie_customer_id) {
                return ['status' => false, 'message' => 'Cliente Mollie não encontrado'];
            }

            // Verificar credenciais
            $credentials = self::generateCredentials();
            if (! $credentials || ! isset($credentials['api_key'])) {
                return ['status' => false, 'message' => 'Credenciais não configuradas'];
            }

            // Inicializar cliente Mollie
            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            // Revogar mandate usando a sintaxe correta da biblioteca Mollie
            // Baseado na documentação: DELETE /v2/customers/{customerId}/mandates/{mandateId}
            $customer = $mollie->customers->get($user->mollie_customer_id);
            $mollie->mandates->revokeFor($customer, $mandateId);

            return ['status' => true, 'message' => 'Cartão removido com sucesso'];

        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            // Verificar se o mandate não existe mais (já foi removido)
            // Códigos 404 (Not Found) e 410 (Gone) indicam que já foi removido
            if (strpos($e->getMessage(), 'not found') !== false ||
                strpos($e->getMessage(), 'no longer available') !== false ||
                $e->getCode() === 404 ||
                $e->getCode() === 410) {
                return ['status' => true, 'message' => 'Cartão já foi removido anteriormente'];
            }

            return ['status' => false, 'message' => 'Erro da API Mollie: '.$e->getMessage()];

        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Erro geral: '.$e->getMessage()];
        }
    }

    /**
     * Criar pagamento usando mandato (cartão salvo)
     *
     * @param  Request  $request
     * @param  string  $mandateId
     * @return array
     */
    public static function createPaymentWithMandate($request, $mandateId)
    {
        try {
            $credentials = self::generateCredentials();
            if (! $credentials) {
                return ['status' => false, 'message' => 'Credenciais não configuradas'];
            }

            $user = auth('api')->user();
            $amount = $request->amount;

            // Criar depósito
            $deposit = Deposit::create([
                'user_id' => $user->id,
                'payment_id' => 'tr_mollie_mandate_'.time().'_'.$user->id,
                'amount' => $amount,
                'type' => 'Mollie Cartão Salvo',
                'status' => 0,
                'currency' => 'EUR',
                'accept_bonus' => $request->accept_bonus ?? true,
                'meta' => [
                    'influencer_code' => $request->influencer_code ?? null,
                    'mandate_id' => $mandateId,
                ],
            ]);

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            // Criar pagamento recorrente usando mandato
            $payment = $mollie->customerPayments->createFor($mollie->customers->get($user->mollie_customer_id), [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => 'Online Purchase - Cartão Salvo',
                'sequenceType' => 'recurring',
                'mandateId' => $mandateId,
                'webhookUrl' => 'https://xpdeal.pt/mollie_webhook.php',
                'metadata' => [
                    'user_id' => $user->id,
                    'order_id' => $deposit->id,
                    'accept_bonus' => $request->accept_bonus ?? true,
                    'influencer_code' => $request->influencer_code ?? null,
                ],
            ]);

            $deposit->update(['payment_id' => $payment->id]);

            \Log::info('[MOLLIE-MANDATE-PAYMENT] Pagamento criado com cartão salvo:', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'mandate_id' => $mandateId,
            ]);

            return [
                'status' => true,
                'payment_id' => $payment->id,
                'deposit_id' => $deposit->id,
                'payment_status' => $payment->status,
            ];

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-MANDATE-PAYMENT] Erro: '.$e->getMessage());

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finalize Mollie Payment - Integração completa baseada no EuPago
     *
     * @param  string  $paymentId
     */
    public static function finalizePaymentMollie($paymentId): bool
    {
        try {
            \Log::info('[PAYMENT-WEBHOOK] Iniciando finalização do pagamento', ['payment_id' => $paymentId]);

            self::generateCredentials();

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey(self::$mollieApiKey);

            \Log::info('[PAYMENT-WEBHOOK] Buscando pagamento no gateway', ['payment_id' => $paymentId]);
            $payment = $mollie->payments->get($paymentId);

            \Log::info('[PAYMENT-WEBHOOK] Status do pagamento no gateway', [
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'is_paid' => $payment->isPaid(),
            ]);

            if ($payment->isPaid()) {
                // Buscar tanto na tabela Transaction quanto Deposit
                $transaction = \App\Models\Transaction::where('payment_id', $paymentId)->where('status', 0)->first();
                $deposit = Deposit::where('payment_id', $paymentId)->where('status', 0)->first();

                \Log::info('[PAYMENT-WEBHOOK] Resultado da busca', [
                    'transaction_found' => ! empty($transaction),
                    'deposit_found' => ! empty($deposit),
                    'transaction_id' => $transaction ? $transaction->id : null,
                    'deposit_id' => $deposit ? $deposit->id : null,
                ]);

                // Processar depósito se encontrado
                if (! empty($deposit)) {
                    return self::processDepositMollie($deposit, $payment);
                }

                // Processar transação se encontrada (fallback)
                if (! empty($transaction)) {
                    \Log::info('[PAYMENT-WEBHOOK] Processando transação simples', [
                        'transaction_id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                    ]);

                    $transaction->status = 1;
                    $transaction->save();

                    \Log::info('[PAYMENT-WEBHOOK] Transação aprovada com sucesso', [
                        'transaction_id' => $transaction->id,
                        'new_status' => $transaction->status,
                    ]);

                    return true;
                }

                \Log::warning('[PAYMENT-WEBHOOK] Nenhuma transação ou depósito encontrado', ['payment_id' => $paymentId]);

                return false;
            } else {
                \Log::warning('[PAYMENT-WEBHOOK] Pagamento não está pago no gateway', [
                    'payment_id' => $paymentId,
                    'status' => $payment->status,
                ]);

                return false;
            }

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-WEBHOOK] Erro na finalização do pagamento: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Processa depósito completo baseado no sistema EuPago
     *
     * @param  Deposit  $deposit
     * @param  object  $payment
     */
    private static function processDepositMollie($deposit, $payment): bool
    {
        try {
            \Log::info('[PAYMENT-DEPOSIT] Iniciando processamento completo do depósito', [
                'deposit_id' => $deposit->id,
                'user_id' => $deposit->user_id,
                'amount' => $deposit->amount,
            ]);

            $user = User::find($deposit->user_id);
            $wallet = Wallet::where('user_id', $deposit->user_id)->first();
            $setting = Setting::first();

            if (empty($user) || empty($wallet)) {
                \Log::error('[PAYMENT-DEPOSIT] Usuário ou carteira não encontrados', [
                    'user_found' => ! empty($user),
                    'wallet_found' => ! empty($wallet),
                ]);

                return false;
            }

            // Extrair metadata do pagamento Mollie
            $metadata = $payment->metadata ?? (object) [];
            $acceptBonus = $metadata->accept_bonus ?? true;
            $influencerCode = $metadata->influencer_code ?? null;

            \Log::info('[PAYMENT-DEPOSIT] Metadata extraída', [
                'accept_bonus' => $acceptBonus,
                'influencer_code' => $influencerCode,
            ]);

            $influencerBonusApplied = false;

            // 1. BÔNUS DE INFLUENCER (Prioridade)
            if ($acceptBonus && ! empty($influencerCode)) {
                $influencerBonusApplied = self::processInfluencerBonus($deposit, $wallet, $setting, $influencerCode);
            }

            // 2. VERIFICAR HISTÓRICO DE DEPÓSITOS
            $checkTransactions = Deposit::where('user_id', $deposit->user_id)
                ->where('status', 1)
                ->count();

            \Log::info('[PAYMENT-DEPOSIT] Histórico de transações', [
                'completed_deposits' => $checkTransactions,
                'is_first_deposit' => $checkTransactions == 0,
            ]);

            // 2.1. ATIVAR KYC NO PRIMEIRO DEPÓSITO (se estava desativado)
            if ($checkTransactions == 0 && $user->kyc_required === false) {
                $user->kyc_required = true;
                $user->save();
                \Log::info('[PAYMENT-DEPOSIT] KYC ativado automaticamente no primeiro depósito', [
                    'user_id' => $user->id,
                ]);
            }

            // 3. BÔNUS DE PRIMEIRO DEPÓSITO
            if (! $influencerBonusApplied && $checkTransactions == 0) {
                self::processFirstDepositBonus($deposit, $wallet, $setting, $user, $acceptBonus);
            }
            // 4. BÔNUS DE SEGUNDO DEPÓSITO
            elseif (! $influencerBonusApplied && $checkTransactions == 1) {
                self::processSecondDepositBonus($deposit, $wallet, $setting, $acceptBonus);
            }

            // 5. RODADAS GRÁTIS PARA DEPÓSITOS SUBSEQUENTES
            if ($checkTransactions > 0) {
                self::processFreeRoundsAnyDeposit($deposit, $setting, $user);
            }

            // 6. ROLLOVER DE DEPÓSITO
            $wallet->increment('balance_deposit_rollover', $deposit->amount * intval($setting->rollover_deposit));

            // 7. BÔNUS VIP
            Helper::payBonusVip($wallet, $deposit->amount);

            // 8. ADICIONAR SALDO PRINCIPAL
            if ($wallet->increment('balance', $deposit->amount)) {
                if ($deposit->update(['status' => 1])) {

                    // 9. PROCESSAR AFILIADOS CPA
                    self::processAffiliateCPA($user, $deposit);

                    // 10. NOTIFICAR ADMINS
                    self::notifyAdmins($user, $deposit->amount);

                    // 11. REGISTRAR ATIVIDADE
                    self::logDepositActivity($user, $deposit);

                    // 12. FACEBOOK PIXEL
                    self::sendFacebookPixelEvent($deposit);

                    \Log::info('[PAYMENT-DEPOSIT] Transação processada com sucesso', [
                        'deposit_id' => $deposit->id,
                        'user_id' => $deposit->user_id,
                        'amount' => $deposit->amount,
                        'new_balance' => $wallet->fresh()->balance,
                    ]);

                    return true;
                }
            }

            \Log::error('[PAYMENT-DEPOSIT] Falha ao atualizar saldo ou status da transação');

            return false;

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-DEPOSIT] Erro no processamento da transação: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Processa bônus de influencer
     */
    private static function processInfluencerBonus($deposit, $wallet, $setting, $influencerCode): bool
    {
        try {
            \Log::info('[PAYMENT-BONUS] Processando bônus promocional', ['code' => $influencerCode]);

            $influencerBonus = \App\Models\InfluencerBonus::where('code', $influencerCode)
                ->where('is_active', true)
                ->first();

            if (! $influencerBonus) {
                \Log::info('[PAYMENT-BONUS] Bônus promocional não encontrado', ['code' => $influencerCode]);

                return false;
            }

            $isOneTimeUse = $influencerBonus->one_time_use ?? false;
            $alreadyRedeemed = false;

            if ($isOneTimeUse) {
                $alreadyRedeemed = \App\Models\InfluencerBonusRedemption::where('user_id', $deposit->user_id)
                    ->where('influencer_bonus_id', $influencerBonus->id)
                    ->exists();
            }

            $meetsMinDepositRequirement = $influencerBonus->min_deposit <= 0 || $deposit->amount >= $influencerBonus->min_deposit;

            if ($meetsMinDepositRequirement && ! $alreadyRedeemed) {
                $calculatedBonus = Helper::porcentagem_xn($influencerBonus->bonus_percentage, $deposit->amount);
                $maxBonus = $influencerBonus->max_bonus;

                $bonus = ($maxBonus > 0 && $calculatedBonus > $maxBonus) ? $maxBonus : $calculatedBonus;

                $wallet->increment('balance_bonus', $bonus);
                $newRollover = ($wallet->balance_bonus_rollover ?? 0) + ($bonus * ($setting->rollover ?? 1));
                $wallet->update(['balance_bonus_rollover' => $newRollover]);

                // Registrar resgate se for de uso único
                if ($isOneTimeUse) {
                    \App\Models\InfluencerBonusRedemption::create([
                        'user_id' => $deposit->user_id,
                        'influencer_bonus_id' => $influencerBonus->id,
                        'deposit_amount' => $deposit->amount,
                        'bonus_amount' => $bonus,
                    ]);
                }

                \Log::info('[PAYMENT-BONUS] Bônus promocional aplicado', [
                    'bonus_amount' => $bonus,
                    'bonus_percentage' => $influencerBonus->bonus_percentage,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-BONUS] Erro no processamento do bônus promocional: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Processa bônus de primeiro depósito
     */
    private static function processFirstDepositBonus($deposit, $wallet, $setting, $user, $acceptBonus): void
    {
        try {
            \Log::info('[PAYMENT-BONUS] Processando bônus de boas-vindas');

            if ($acceptBonus && $setting->initial_bonus > 0) {
                $bonus = Helper::porcentagem_xn($setting->initial_bonus, $deposit->amount);
                $wallet->increment('balance_bonus', $bonus);
                $wallet->update(['balance_bonus_rollover' => $bonus * $setting->rollover]);

                \Log::info('[PAYMENT-BONUS] Bônus de boas-vindas aplicado', [
                    'bonus_amount' => $bonus,
                    'bonus_percentage' => $setting->initial_bonus,
                ]);
            }

            // Rodadas grátis para primeiro depósito
            if ($setting->game_free_rounds_active_deposit) {
                self::processFreeRoundsFirstDeposit($deposit, $setting, $user);
            }

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-BONUS] Erro no processamento do bônus de boas-vindas: '.$e->getMessage());
        }
    }

    /**
     * Processa bônus de segundo depósito
     */
    private static function processSecondDepositBonus($deposit, $wallet, $setting, $acceptBonus): void
    {
        try {
            \Log::info('[PAYMENT-BONUS] Processando bônus adicional');

            if ($acceptBonus && $setting->second_deposit_bonus > 0 && $setting->second_deposit_bonus_active) {
                $bonus = Helper::porcentagem_xn($setting->second_deposit_bonus, $deposit->amount);
                $wallet->increment('balance_bonus', $bonus);
                $wallet->update(['balance_bonus_rollover' => ($wallet->balance_bonus_rollover + ($bonus * $setting->rollover))]);

                \Log::info('[PAYMENT-BONUS] Bônus adicional aplicado', [
                    'bonus_amount' => $bonus,
                    'bonus_percentage' => $setting->second_deposit_bonus,
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-BONUS] Erro no processamento do bônus adicional: '.$e->getMessage());
        }
    }

    /**
     * Processa rodadas grátis para primeiro depósito
     */
    private static function processFreeRoundsFirstDeposit($deposit, $setting, $user): void
    {
        try {
            $amount = $deposit->amount;

            if (isset($setting->amount_rounds_free_deposit_cat1_min) &&
                $amount >= $setting->amount_rounds_free_deposit_cat1_min &&
                (! isset($setting->amount_rounds_free_deposit_cat1_max) || $amount <= $setting->amount_rounds_free_deposit_cat1_max)) {

                PlayFiverService::RoundsFree([
                    'username' => $user->email,
                    'game_code' => $setting->game_code_rounds_free_deposit,
                    'rounds' => $setting->rounds_free_deposit_cat1,
                ]);

                \Log::info('[MOLLIE-FREE-ROUNDS] Rodadas grátis categoria 1 concedidas', ['rounds' => $setting->rounds_free_deposit_cat1]);
            }
            // Repetir para outras categorias...

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-FREE-ROUNDS] Erro nas rodadas grátis: '.$e->getMessage());
        }
    }

    /**
     * Processa rodadas grátis para qualquer depósito
     */
    private static function processFreeRoundsAnyDeposit($deposit, $setting, $user): void
    {
        try {
            if (! $setting->game_free_rounds_active_any_deposit) {
                return;
            }

            $amount = $deposit->amount;

            if (isset($setting->amount_rounds_free_any_deposit_cat1_min) &&
                $amount >= $setting->amount_rounds_free_any_deposit_cat1_min &&
                (! isset($setting->amount_rounds_free_any_deposit_cat1_max) || $amount <= $setting->amount_rounds_free_any_deposit_cat1_max)) {

                PlayFiverService::RoundsFree([
                    'username' => $user->email,
                    'game_code' => $setting->game_code_rounds_free_any_deposit,
                    'rounds' => $setting->rounds_free_any_deposit_cat1,
                ]);

                \Log::info('[MOLLIE-FREE-ROUNDS-ANY] Rodadas grátis categoria 1 concedidas', ['rounds' => $setting->rounds_free_any_deposit_cat1]);
            }
            // Repetir para outras categorias...

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-FREE-ROUNDS-ANY] Erro nas rodadas grátis: '.$e->getMessage());
        }
    }

    /**
     * Processa afiliados CPA
     */
    private static function processAffiliateCPA($user, $deposit): void
    {
        try {
            $affHistoryCPA = AffiliateHistory::where('user_id', $user->id)
                ->where('commission_type', 'cpa')
                ->where('status', 0)
                ->first();

            if (! empty($affHistoryCPA)) {
                $sponsorCpa = User::find($user->inviter);
                if (! empty($sponsorCpa)) {
                    if ($affHistoryCPA->deposited_amount >= $sponsorCpa->affiliate_baseline || $deposit->amount >= $sponsorCpa->affiliate_baseline) {
                        $walletCpa = Wallet::where('user_id', $affHistoryCPA->inviter)->first();
                        if (! empty($walletCpa)) {
                            $walletCpa->increment('refer_rewards', $sponsorCpa->affiliate_cpa);
                            $affHistoryCPA->update(['status' => 1, 'commission_paid' => $sponsorCpa->affiliate_cpa]);

                            \Log::info('[MOLLIE-CPA] Comissão CPA paga', [
                                'sponsor_id' => $sponsorCpa->id,
                                'commission' => $sponsorCpa->affiliate_cpa,
                            ]);
                        }
                    } else {
                        $affHistoryCPA->update(['deposited_amount' => $deposit->amount]);
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-CPA] Erro no processamento CPA: '.$e->getMessage());
        }
    }

    /**
     * Notifica admins sobre novo depósito
     */
    private static function notifyAdmins($user, $amount): void
    {
        try {
            $admins = User::where('role_id', 0)->get();
            foreach ($admins as $admin) {
                $admin->notify(new NewDepositNotification($user->name, $amount));
            }
        } catch (\Exception $e) {
            \Log::error('[MOLLIE-NOTIFY] Erro ao notificar admins: '.$e->getMessage());
        }
    }

    /**
     * Registra atividade de depósito
     */
    private static function logDepositActivity($user, $deposit): void
    {
        try {
            $ipLocation = Location::get(request()->ip());
            $locationData = [];

            if ($ipLocation) {
                $locationData = [
                    'country_name' => $ipLocation->countryName,
                    'country_code' => $ipLocation->countryCode,
                    'region' => $ipLocation->regionName,
                    'city' => $ipLocation->cityName,
                ];
            }

            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'amount' => $deposit->amount,
                    'payment_method' => 'mollie',
                    'payment_id' => $deposit->payment_id,
                    'location' => $locationData,
                ])
                ->log('deposit_completed');

        } catch (\Exception $e) {
            \Log::error('[MOLLIE-ACTIVITY] Erro ao registrar atividade: '.$e->getMessage());
        }
    }

    /**
     * Envia evento para Facebook Pixel
     */
    private static function sendFacebookPixelEvent($deposit): void
    {
        try {
            if (isset($deposit->id)) {
                $facebookPixelService = new \App\Services\Facebook\FacebookPixelService;
                $facebookPixelService->sendPurchaseEvent((string) $deposit->id);
            }
        } catch (\Exception $e) {
            \Log::error('[MOLLIE-FACEBOOK] Erro ao enviar evento para Facebook Pixel: '.$e->getMessage());
        }
    }

    /**
     * Create Mollie Payment with Card Token (Embedded)
     *
     * @return array
     */
    public static function createMolliePaymentWithToken($request)
    {
        try {
            $credentials = self::generateCredentials();
            if (! $credentials) {
                return ['status' => false, 'message' => 'Credenciais Mollie não configuradas'];
            }

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            $user = auth('api')->user();
            $amount = $request->amount;
            $cardToken = $request->cardToken;

            // Obter ou criar cliente Mollie para cartões salvos
            $mollieCustomerId = self::getOrCreateMollieCustomer($user);

            $deposit = Deposit::create([
                'user_id' => $user->id,
                'payment_id' => 'tr_mollie_token_'.time().'_'.$user->id,
                'amount' => $amount,
                'type' => 'Cartão',
                'status' => 0,
                'currency' => 'EUR',
                'accept_bonus' => $request->accept_bonus ?? true,
                'meta' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'influencer_code' => $request->influencer_code ?? null,
                    'mollie_method' => 'creditcard',
                ],
            ]);

            $paymentData = [
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => 'Online Service Payment',
                'method' => 'creditcard',
                'redirectUrl' => 'https://xpdeal.pt/mollie_redirect.php?payment_id='.$deposit->payment_id,
                'webhookUrl' => 'https://xpdeal.pt/mollie_webhook.php',
                'cardToken' => $cardToken,
                'metadata' => [
                    'user_id' => $user->id,
                    'order_id' => $deposit->id,
                    'accept_bonus' => $request->accept_bonus ?? true,
                    'influencer_code' => $request->influencer_code ?? null,
                ],
            ];

            // Adicionar customerId e sequenceType para criar mandates
            if ($mollieCustomerId) {
                $paymentData['customerId'] = $mollieCustomerId;
                $paymentData['sequenceType'] = 'first';
                \Log::info('[PAYMENT-TOKEN] Usando customerId para cartões salvos:', [
                    'user_id' => $user->id,
                    'customer_id' => $mollieCustomerId,
                    'sequence_type' => 'first',
                ]);
            }

            $payment = $mollie->payments->create($paymentData);

            \Log::info('[PAYMENT-TOKEN] Pagamento criado com sucesso:', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'requires_3ds' => isset($payment->_links->checkout),
            ]);

            $deposit->update(['payment_id' => $payment->id]);

            $result = [
                'status' => true,
                'payment_id' => $payment->id,
                'deposit_id' => $deposit->id,
            ];

            // Verificar se requer 3DS (checkout URL disponível)
            if (isset($payment->_links->checkout)) {
                $result['requires_3ds'] = true;
                $result['checkout_url'] = $payment->_links->checkout->href;
            } else {
                $result['requires_3ds'] = false;
            }

            return $result;

        } catch (\Exception $e) {
            \Log::error('[PAYMENT-TOKEN] Erro no pagamento com token: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return ['status' => false, 'message' => 'Erro ao processar pagamento: '.$e->getMessage()];
        }
    }

    /**
     * Generate Transaction
     */
    public static function generateTransaction(string $idTransaction, array $dataTransaction): bool
    {
        return \App\Models\Transaction::create([
            'payment_id' => $idTransaction,
            'user_id' => $dataTransaction['user_id'],
            'status' => 0,
        ]);
    }

    /**
     * Generate Deposit
     */
    public static function generateDeposit(string $paymentId, array $dataDeposit): bool
    {
        return Deposit::create([
            'payment_id' => $paymentId,
            'user_id' => $dataDeposit['user_id'],
            'amount' => $dataDeposit['amount'],
            'type' => $dataDeposit['type'],
            'proof' => $dataDeposit['proof'] ?? null,
            'status' => 0,
            'currency' => $dataDeposit['currency'] ?? 'EUR',
        ]);
    }

    /**
     * Converte método Mollie para nome de exibição
     */
    private static function getPaymentTypeName($mollieMethod)
    {
        $methodNames = [
            'creditcard' => 'Cartão',
            'applepay' => 'Applepay',
            'googlepay' => 'Googlepay',
            'mbway' => 'Mbway',
            'multibanco' => 'Multibanco',
            'paybybank' => 'Paybybank',
            // 'banktransfer' => 'Banktransfer',
        ];

        return $methodNames[$mollieMethod] ?? 'Mollie';
    }
}
