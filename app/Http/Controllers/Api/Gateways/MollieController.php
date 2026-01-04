<?php

namespace App\Http\Controllers\Api\Gateways;

use App\Http\Controllers\Controller;
use App\Traits\Gateways\MollieTrait;
use Illuminate\Http\Request;

class MollieController extends Controller
{
    use MollieTrait;

    /**
     * Create Mollie Payment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        try {

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'mollie_method' => 'nullable|string|in:creditcard,applepay,mbway,multibanco,paybybank',
            ]);

            $result = MollieTrait::createMolliePayment($request);

            if ($result['status']) {
                return response()->json([
                    'status' => true,
                    'payment_id' => $result['payment_id'],
                    'checkout_url' => $result['checkout_url'],
                    'deposit_id' => $result['deposit_id'],
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            // Verificar se é erro específico do Google Pay em ambiente de teste
            $mollieMethod = $request->input('mollie_method');
            if ($mollieMethod === 'googlepay' && (strpos($e->getMessage(), 'Service Temporarily Unavailable') !== false || strpos($e->getMessage(), '500') !== false)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Google Pay não está disponível em ambiente de teste. Use cartão de crédito para testes ou configure em produção com HTTPS.',
                ], 400);
            }

            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Mollie Webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        try {
            // Verificar se é um evento de ping do webhook
            $eventType = $request->input('type');
            if ($eventType === 'hook.ping') {
                return response('OK', 200);
            }

            $paymentId = $request->input('id');

            if (! $paymentId) {
                return response('Payment ID not provided', 400);
            }

            $result = self::finalizePaymentMollie($paymentId);

            if ($result) {
                return response('OK', 200);
            }

            return response('Payment not processed', 400);

        } catch (\Exception $e) {
            return response('Error', 500);
        }
    }

    /**
     * Handle Return from Mollie
     *
     * @param  string  $paymentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function returnUrl(Request $request, $paymentId)
    {
        try {
            // Verificar status do pagamento
            self::finalizePaymentMollie($paymentId);

            // Redirecionar para homepage com parâmetros de sucesso
            return redirect()->to('/?payment_status=success&payment_id='.$paymentId);

        } catch (\Exception $e) {
            return redirect()->to('/?payment=error');
        }
    }

    /**
     * Listar cartões salvos do cliente
     */
    public function getSavedCards(Request $request)
    {
        try {
            // Tentar auth:api primeiro, depois auth.jwt
            $user = auth('api')->user();
            if (! $user) {
                $user = auth()->user();
            }

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado',
                ]);
            }

            if (! $user->mollie_customer_id) {
                return response()->json([
                    'status' => true,
                    'savedCards' => [],
                ]);
            }

            $result = MollieTrait::getCustomerMandates($user->mollie_customer_id);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao buscar cartões salvos',
            ]);
        }
    }

    /**
     * Criar pagamento com cartão salvo
     */
    public function createPaymentWithSavedCard(Request $request)
    {
        try {
            // Tentar auth:api primeiro, depois auth.jwt
            $user = auth('api')->user();
            if (! $user) {
                $user = auth()->user();
            }

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'mandate_id' => 'required|string',
            ]);

            $result = MollieTrait::createPaymentWithMandate($request, $request->mandate_id);

            if ($result['status']) {
                return response()->json([
                    'status' => true,
                    'payment_id' => $result['payment_id'],
                    'deposit_id' => $result['deposit_id'],
                    'payment_status' => $result['payment_status'],
                ]);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao processar pagamento com cartão salvo',
            ]);
        }
    }

    /**
     * Check Payment Status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|string',
            ]);

            $credentials = MollieTrait::generateCredentials();
            if (! $credentials) {
                return response()->json([
                    'status' => false,
                    'message' => 'Credenciais do gateway não configuradas',
                ], 400);
            }

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            $payment = $mollie->payments->get($request->payment_id);

            return response()->json([
                'status' => $payment->status, // Retornar o status real do Mollie
                'payment_status' => $payment->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ]);
        }
    }

    /**
     * Create Mollie Payment with Card Token (Embedded)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentWithToken(Request $request)
    {
        try {

            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'cardToken' => 'required|string',
            ]);

            $result = MollieTrait::createMolliePaymentWithToken($request);

            if ($result['status']) {
                return response()->json([
                    'status' => true,
                    'payment_id' => $result['payment_id'],
                    'checkout_url' => $result['checkout_url'] ?? null,
                    'deposit_id' => $result['deposit_id'],
                    'requires_3ds' => $result['requires_3ds'] ?? false,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ]);
        }
    }

    /**
     * Get Available Mollie Payment Methods
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
        try {
            $methods = [
                [
                    'slug' => 'mollie-creditcard',
                    'name' => 'Cartão de Crédito',
                    'icon' => '/assets/images/payments/Design-sem-nome-_16_.webp',
                    'mollie_method' => 'creditcard',
                ],
                [
                    'slug' => 'mollie-applepay',
                    'name' => 'Apple Pay',
                    'icon' => '/assets/images/payments/apple-pay-main-logo-2025-horizontal-1-removebg-preview.png',
                    'mollie_method' => 'applepay',
                ],
                [
                    'slug' => 'mollie-googlepay',
                    'name' => 'Google Pay',
                    'icon' => '/assets/images/payments/Design sem nome (14).png',
                    'mollie_method' => 'googlepay',
                ],
                [
                    'slug' => 'mollie-mbway',
                    'name' => 'MB WAY',
                    'icon' => '/assets/images/payments/mollie-mbway.svg',
                    'mollie_method' => 'mbway',
                ],
                [
                    'slug' => 'mollie-multibanco',
                    'name' => 'Multibanco',
                    'icon' => '/assets/images/payments/mollie-multibanco.svg',
                    'mollie_method' => 'multibanco',
                ],
                [
                    'slug' => 'mollie-paybybank',
                    'name' => 'Pay by Bank',
                    'icon' => '/assets/images/payments/Bancário.webp',
                    'mollie_method' => 'paybybank',
                ],
                [
                    'slug' => 'mollie-banktransfer',
                    'name' => 'Transferência Bancária',
                    'icon' => '/assets/images/payments/mollie-banktransfer.svg',
                    'mollie_method' => 'banktransfer',
                ],
            ];

            return response()->json([
                'status' => true,
                'methods' => $methods,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor',
            ]);
        }
    }

    /**
     * Get Active Mollie Methods from API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveMethods()
    {
        try {
            $credentials = MollieTrait::generateCredentials();
            if (! $credentials) {
                return response()->json([
                    'status' => false,
                    'message' => 'Credenciais do gateway não configuradas',
                ], 400);
            }

            $mollie = new \Mollie\Api\MollieApiClient;
            $mollie->setApiKey($credentials['api_key']);

            $methods = $mollie->methods->allActive();

            return response()->json([
                'status' => true,
                'methods' => $methods,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao obter métodos de pagamento',
            ]);
        }
    }

    /**
     * Excluir cartão salvo (revogar mandate)
     */
    public function deleteSavedCard(Request $request)
    {
        try {
            // Verificar autenticação
            $user = auth('api')->user();
            if (! $user) {
                $user = auth()->user();
            }

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            // Validar dados
            $validated = $request->validate([
                'mandate_id' => 'required|string',
            ]);

            // Chamar função de revogar mandate
            $result = MollieTrait::revokeMandate($validated['mandate_id']);

            // Retornar resultado
            if ($result['status']) {
                return response()->json($result, 200);
            } else {
                return response()->json($result, 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Dados inválidos: mandate_id é obrigatório',
                'errors' => $e->errors(),
            ], 400);

        } catch (\Throwable $e) {
            // Capturar qualquer tipo de erro
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor: '.$e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Get Mollie Configuration
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig()
    {
        try {
            $credentials = MollieTrait::generateCredentials();
            if (! $credentials) {
                return response()->json([
                    'status' => false,
                    'message' => 'Credenciais do gateway não configuradas',
                ], 400);
            }

            return response()->json([
                'status' => true,
                'profile_id' => $credentials['profile_id'] ?? null,
                'api_key_configured' => ! empty($credentials['api_key']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Erro ao obter configuração',
            ]);
        }
    }
}
