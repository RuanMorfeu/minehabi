<?php

namespace App\Http\Controllers\Api\Profile;

use App\Helpers\Core;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Integrations\AresSMSService;
use App\Models\AffiliateWithdraw;
use App\Models\Deposit;
use App\Models\Setting;
use App\Models\SuitPayPayment;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Traits\Gateways\DigitoPayTrait;
use App\Traits\Gateways\EzzepayTrait;
use App\Traits\Gateways\SuitpayTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use DigitoPayTrait;
    use EzzepayTrait;
    use SuitpayTrait;

    public function index()
    {
        $wallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        $user = auth('api')->user();

        // Verificar e atualizar os limites de saque
        $user->checkAndUpdateWithdrawalLimits();

        return response()->json([
            'wallet' => $wallet,
            'user' => [
                'daily_withdrawal_limit' => $user->daily_withdrawal_limit,
                'daily_withdrawal_count_limit' => $user->daily_withdrawal_count_limit,
                'withdrawal_amount_today' => $user->withdrawal_amount_today,
                'withdrawal_count_today' => $user->withdrawal_count_today,
                'withdrawal_count_reset_at' => $user->withdrawal_count_reset_at ? $user->withdrawal_count_reset_at->format('Y-m-d\TH:i:s.v\Z') : null,
            ],
        ], 200);
    }

    /*** @return \Illuminate\Http\JsonResponse
     */
    public function myWallet()
    {
        $wallets = Wallet::whereUserId(auth('api')->id())->get();

        return response()->json(['wallets' => $wallets], 200);
    }

    /*** @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function setWalletActive($id)
    {
        $checkWallet = Wallet::whereUserId(auth('api')->id())->where('active', 1)->first();
        if (! empty($checkWallet)) {
            $checkWallet->update(['active' => 0]);
        }

        $wallet = Wallet::find($id);
        if (! empty($wallet)) {
            $wallet->update(['active' => 1]);

            return response()->json(['wallet' => $wallet], 200);
        }
    }

    public function cancelWithdrawal($id, Request $request)
    {
        $tipo = $request->input('tipo');
        $user = Auth::user();
        if (! $user->hasRole('admin')) {
            back();
        }
        if ($tipo == 'user') {
            return $this->cancelWithdrawalUser($id);
        }

        if ($tipo == 'afiliado') {
            return $this->cancelWithdrawalAffiliate($id);
        }
    }

    public function withdrawalFromModal($id, Request $request)
    {
        $setting = Core::getSetting();
        $resultado = null;
        $tipo = $request->input('tipo');
        $user = Auth::user();
        $message = 'Saque solicitado com sucesso';
        if (! $user->hasRole('admin')) {
            back();
        }
        switch ($setting->default_gateway) {
            case 'suitpay':
                $withdrawal = Withdrawal::find($id);
                if ($tipo == 'afiliado') {
                    $withdrawal = AffiliateWithdraw::find($id);
                }
                $withdrawal->update(['status' => 1]);

                $suitpayment = SuitPayPayment::create([
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'pix_key' => $withdrawal->pix_key,
                    'pix_type' => $withdrawal->pix_type,
                    'amount' => $withdrawal->amount,
                    'observation' => 'Saque direto',
                ]);
                $parm = [
                    'pix_key' => $withdrawal->pix_key,
                    'pix_type' => $withdrawal->pix_type,
                    'amount' => $withdrawal->amount,
                    'suitpayment_id' => $suitpayment->id,
                ];
                $resultado = self::pixCashOut($parm);
                break;
            case 'digitopay':
                $message = 'Para poder autorizar o saque, você precisa acessar o painel da digitopay para autorizar';
                $resultado = self::pixCashOutDigito($id, $tipo);
                break;
            case 'ezzepay':
                $resultado = self::pixCashOutEzze($id, $tipo);
                break;
        }

        if ($resultado == true) {
            Notification::make()
                ->title('Saque solicitado')
                ->body($message)
                ->success()
                ->send();

            return back();
        } else {
            Notification::make()
                ->title('Erro no saque')
                ->body('Erro ao solicitar o saque')
                ->danger()
                ->send();

            return back();
        }
    }

    private function cancelWithdrawalAffiliate($id)
    {
        $withdrawal = AffiliateWithdraw::find($id);
        if (! empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if (! empty($wallet)) {
                $wallet->increment('refer_rewards', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }

            return back();
        }

        return back();
    }

    /*** @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    private function cancelWithdrawalUser($id)
    {
        $withdrawal = Withdrawal::find($id);
        if (! empty($withdrawal)) {
            $wallet = Wallet::where('user_id', $withdrawal->user_id)
                ->where('currency', $withdrawal->currency)
                ->first();

            if (! empty($wallet)) {
                $wallet->increment('balance_withdrawal', $withdrawal->amount);

                $withdrawal->update(['status' => 2]);
                Notification::make()
                    ->title('Saque cancelado')
                    ->body('Saque cancelado com sucesso')
                    ->success()
                    ->send();

                return back();
            }

            return back();
        }

        return back();
    }

    /**
     * Verifica se o usuário já fez pelo menos um depósito
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUserDeposits()
    {
        if (auth('api')->check()) {
            $hasDeposits = Deposit::where('user_id', auth('api')->id())
                ->where('status', 1) // Considerando apenas depósitos aprovados
                ->count() > 0;

            return response()->json([
                'hasDeposits' => $hasDeposits,
            ]);
        }

        return response()->json([
            'message' => 'Usuário não autenticado',
        ], 401);
    }

    public function requestWithdrawal(Request $request)
    {
        $setting = Setting::first();

        // Verificar se é afiliado
        if (auth('api')->check()) {
            // Verificar se o usuário já fez pelo menos um depósito
            $hasDeposits = Deposit::where('user_id', auth('api')->id())
                ->where('status', 1) // Considerando apenas depósitos aprovados
                ->count() > 0;

            if (! $hasDeposits) {
                return response()->json([
                    'message' => 'Você precisa fazer pelo menos um depósito antes de solicitar um saque.',
                ], 422);
            }

            // Regras básicas de validação para todos os tipos de saque
            $rules = [
                'amount' => ['required', 'numeric', 'min:'.$setting->min_withdrawal, 'max:'.$setting->max_withdrawal],
                'accept_terms' => 'required',
            ];

            // Regras específicas para cada tipo de saque
            if ($request->type === 'pix') {
                $rules['pix_type'] = 'required';

                switch ($request->pix_type) {
                    case 'document':
                        $rules['pix_key'] = 'required|cpf_ou_cnpj';
                        break;
                    case 'email':
                        $rules['pix_key'] = 'required|email';
                        break;
                    case 'phoneNumber':
                        $rules['pix_key'] = 'required';
                        break;
                    default:
                        $rules['pix_key'] = 'required';
                }
            } elseif ($request->type === 'bank') {
                $rules['name'] = 'required|string|min:5';
                $rules['bank_info'] = 'required|string|min:10';
            }

            $validated = $request->validate($rules);

            $user = auth('api')->user();
            $wallet = $user->wallet;

            // Verificar limites individuais de saque
            $withdrawalCheck = $user->canWithdraw($request->amount);
            if (! $withdrawalCheck['can_withdraw']) {
                return response()->json([
                    'message' => $withdrawalCheck['message'],
                ], 422);
            }

            if ($wallet->balance_withdrawal >= $request->amount) {
                $wallet->decrement('balance_withdrawal', $request->amount);

                $withdrawal = new Withdrawal;
                $withdrawal->user_id = auth('api')->id();
                $withdrawal->amount = $request->amount;
                $withdrawal->currency = $wallet->currency;
                $withdrawal->pix_type = $request->pix_type ?? null;
                $withdrawal->pix_key = $request->pix_key ?? null;
                $withdrawal->name = $request->name;
                $withdrawal->bank_info = $request->bank_info ?? null;
                $withdrawal->nif = $request->nif ?? null;
                $withdrawal->status = 0;
                $withdrawal->save();

                // Registra o saque nos contadores diários
                $user->registerWithdrawal($request->amount);

                // Enviar SMS/WhatsApp para nova solicitação de saque
                $payload = [
                    'name' => ! empty($user->name) ? $user->name : null,
                    'email' => ! empty($user->email) ? $user->email : null,
                    'type' => 'new-withdraw', // Tipo correto para saques
                    'event_identify' => 'Novo Saque',
                    'phone' => ! empty($user->phone) ? $user->phone : null,
                    'username' => ! empty($user->username) ? $user->username : null,
                    'checkout' => $withdrawal->id,
                    'value' => $withdrawal->amount,
                ];
                AresSMSService::sendSMS($payload);

                return response()->json([
                    'message' => 'Saque solicitado com sucesso',
                ]);
            }

            return response()->json([
                'message' => 'Saldo insuficiente',
            ], 422);
        }

        return response()->json([
            'message' => 'Usuário não autenticado',
        ], 401);
    }
}
