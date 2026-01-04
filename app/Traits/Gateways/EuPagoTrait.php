<?php

namespace App\Traits\Gateways;

use App\Helpers\Core as Helper;
use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewDepositNotification;
use App\Services\PlayFiverService;
use Stevebauman\Location\Facades\Location;

trait EuPagoTrait
{
    public static function finalizePaymentEuPago($idTransaction): bool
    {
        $request_headers = getallheaders();
        if (! isset($request_headers['Key']) || $request_headers['Key'] != '9435943594354395399') {
            exit('Nao autorizado!');
        }

        $transaction = Deposit::where('payment_id', $idTransaction)->where('status', 0)->first();
        $setting = \Helper::getSetting();

        if (! empty($transaction)) {
            $user = User::find($transaction->user_id);

            $wallet = Wallet::where('user_id', $transaction->user_id)->first();
            if (! empty($wallet)) {
                $setting = Setting::first();

                \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Checking for influencer bonus.', [
                    'transaction_id' => $transaction->id,
                    'transaction_meta' => $transaction->meta,
                    'accept_bonus' => $transaction->accept_bonus,
                ]);

                $influencerCode = $transaction->meta['influencer_code'] ?? null;
                $influencerBonusApplied = false;

                \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Extracted influencer code.', ['influencer_code' => $influencerCode]);

                // Prioritize Influencer Bonus
                if ($transaction->accept_bonus && ! empty($influencerCode)) {
                    \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Main conditions met (accepts bonus and has code), checking details.');

                    // Buscar o bônus de influencer pelo código
                    $influencerBonus = \App\Models\InfluencerBonus::where('code', $influencerCode)
                        ->where('is_active', true)
                        ->first();

                    \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Buscando bônus de influencer no banco de dados', [
                        'code' => $influencerCode,
                        'found' => $influencerBonus ? true : false,
                        'bonus_id' => $influencerBonus->id ?? null,
                        'bonus_name' => $influencerBonus->name ?? null,
                        'bonus_percentage' => $influencerBonus->bonus_percentage ?? null,
                    ]);

                    // Verifica se o bônus existe e se é de uso único
                    if ($influencerBonus) {
                        $isOneTimeUse = $influencerBonus->one_time_use ?? false;
                        $alreadyRedeemed = false;

                        // Se for de uso único, verifica se o usuário já resgatou
                        if ($isOneTimeUse) {
                            $alreadyRedeemed = \App\Models\InfluencerBonusRedemption::where('user_id', $transaction->user_id)
                                ->where('influencer_bonus_id', $influencerBonus->id)
                                ->exists();

                            \Log::info('[DEBUG-INFLUENCER-BONUS] Verificando se bônus já foi resgatado', [
                                'is_one_time_use' => $isOneTimeUse,
                                'already_redeemed' => $alreadyRedeemed,
                                'user_id' => $transaction->user_id,
                                'bonus_id' => $influencerBonus->id,
                                'bonus_name' => $influencerBonus->name,
                                'bonus_code' => $influencerBonus->code,
                                'one_time_use_setting' => $influencerBonus->one_time_use ? 'SIM' : 'NÃO',
                                'status_resgate' => $alreadyRedeemed ? 'JÁ FOI RESGATADO' : 'NUNCA RESGATADO',
                            ]);
                        }

                        // Verifica se o valor do depósito é maior ou igual ao mínimo necessário
                        $meetsMinDepositRequirement = $influencerBonus->min_deposit <= 0 || $transaction->amount >= $influencerBonus->min_deposit;

                        \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Verificando requisitos mínimos', [
                            'min_deposit' => $influencerBonus->min_deposit,
                            'transaction_amount' => $transaction->amount,
                            'meets_requirement' => $meetsMinDepositRequirement,
                        ]);

                        if ($meetsMinDepositRequirement && ! $alreadyRedeemed) {
                            \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: APPLYING INFLUENCER BONUS.');
                            // Calcular o bônus baseado na porcentagem
                            $calculatedBonus = Helper::porcentagem_xn($influencerBonus->bonus_percentage, $transaction->amount);

                            // Aplicar o limite máximo se estiver configurado
                            $maxBonus = $influencerBonus->max_bonus;
                            if ($maxBonus > 0 && $calculatedBonus > $maxBonus) {
                                $bonus = $maxBonus;
                                \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Bonus limited by max value.', [
                                    'calculated_bonus' => $calculatedBonus,
                                    'max_bonus' => $maxBonus,
                                    'applied_bonus' => $bonus,
                                    'bonus_percentage' => $influencerBonus->bonus_percentage,
                                ]);
                            } else {
                                $bonus = $calculatedBonus;
                                \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Bonus calculated without limit.', [
                                    'calculated_bonus' => $calculatedBonus,
                                    'bonus_percentage' => $influencerBonus->bonus_percentage,
                                ]);
                            }

                            $wallet->increment('balance_bonus', $bonus);

                            $newRollover = ($wallet->balance_bonus_rollover ?? 0) + ($bonus * ($setting->rollover ?? 1));
                            $wallet->update(['balance_bonus_rollover' => $newRollover]);
                            $influencerBonusApplied = true;

                            // Registrar o resgate se for de uso único
                            if ($isOneTimeUse) {
                                try {
                                    $redemption = \App\Models\InfluencerBonusRedemption::create([
                                        'user_id' => $transaction->user_id,
                                        'influencer_bonus_id' => $influencerBonus->id,
                                        'deposit_amount' => $transaction->amount,
                                        'bonus_amount' => $bonus,
                                    ]);

                                    \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Resgate de bônus de uso único registrado', [
                                        'redemption_id' => $redemption->id,
                                        'user_id' => $transaction->user_id,
                                        'bonus_id' => $influencerBonus->id,
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::error('[INFLUENCER-DEBUG] EuPagoTrait: Erro ao registrar resgate de bônus', [
                                        'error' => $e->getMessage(),
                                        'user_id' => $transaction->user_id,
                                        'bonus_id' => $influencerBonus->id,
                                    ]);
                                }
                            }

                            \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Influencer bonus applied successfully.', [
                                'bonus_amount' => $bonus,
                                'new_rollover' => $newRollover,
                                'bonus_id' => $influencerBonus->id,
                                'bonus_name' => $influencerBonus->name,
                                'is_one_time_use' => $isOneTimeUse,
                            ]);
                        } else {
                            \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Influencer bonus requirements not met.', [
                                'meets_min_deposit' => $meetsMinDepositRequirement,
                                'already_redeemed' => $alreadyRedeemed ?? false,
                                'min_deposit' => $influencerBonus->min_deposit ?? 0,
                                'transaction_amount' => $transaction->amount,
                            ]);
                        }
                    } else {
                        \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: No valid influencer bonus found for code.', [
                            'code' => $influencerCode,
                        ]);
                    }
                } else {
                    \Log::info('[INFLUENCER-DEBUG] EuPagoTrait: Main conditions not met (accept_bonus is false or influencerCode is empty).');
                }

                // Verifica se é o primeiro depósito, verifica as transações, somente se for transações concluídas
                $checkTransactions = Deposit::where('user_id', $transaction->user_id)
                    ->where('status', 1)
                    ->count();

                // Ativar KYC no primeiro depósito (se estava desativado)
                if ($checkTransactions == 0 && $user->kyc_required === false) {
                    $user->kyc_required = true;
                    $user->save();
                    \Log::info('[PAYMENT-DEPOSIT] KYC ativado automaticamente no primeiro depósito', [
                        'user_id' => $user->id,
                    ]);
                }

                // Só aplica o bônus de primeiro depósito se o bônus de influencer não foi aplicado
                if (! $influencerBonusApplied && ($checkTransactions == 0 || empty($checkTransactions))) {
                    // Pagar o bonus
                    if ($transaction->accept_bonus) {
                        $bonus = Helper::porcentagem_xn($setting->initial_bonus, $transaction->amount);
                        $wallet->increment('balance_bonus', $bonus);
                        $wallet->update(['balance_bonus_rollover' => $bonus * $setting->rollover]);
                    }

                    if ($setting->game_free_rounds_active_deposit) {
                        // Verificar em qual categoria o depósito se enquadra (da menor para a maior)
                        if (isset($setting->amount_rounds_free_deposit_cat1_min) &&
                            $transaction->amount >= $setting->amount_rounds_free_deposit_cat1_min &&
                            (! isset($setting->amount_rounds_free_deposit_cat1_max) || $transaction->amount <= $setting->amount_rounds_free_deposit_cat1_max)) {
                            $dados = [
                                'username' => $user->email,
                                'game_code' => $setting->game_code_rounds_free_deposit,
                                'rounds' => $setting->rounds_free_deposit_cat1,
                            ];
                            PlayFiverService::RoundsFree($dados);
                        } elseif (isset($setting->amount_rounds_free_deposit_cat2_min) &&
                                 $transaction->amount >= $setting->amount_rounds_free_deposit_cat2_min &&
                                 (! isset($setting->amount_rounds_free_deposit_cat2_max) || $transaction->amount <= $setting->amount_rounds_free_deposit_cat2_max)) {
                            $dados = [
                                'username' => $user->email,
                                'game_code' => $setting->game_code_rounds_free_deposit,
                                'rounds' => $setting->rounds_free_deposit_cat2,
                            ];
                            PlayFiverService::RoundsFree($dados);
                        } elseif (isset($setting->amount_rounds_free_deposit_cat3_min) &&
                                 $transaction->amount >= $setting->amount_rounds_free_deposit_cat3_min &&
                                 (! isset($setting->amount_rounds_free_deposit_cat3_max) || $transaction->amount <= $setting->amount_rounds_free_deposit_cat3_max)) {
                            $dados = [
                                'username' => $user->email,
                                'game_code' => $setting->game_code_rounds_free_deposit,
                                'rounds' => $setting->rounds_free_deposit_cat3,
                            ];
                            PlayFiverService::RoundsFree($dados);
                        } elseif (isset($setting->amount_rounds_free_deposit_cat4_min) &&
                                 $transaction->amount >= $setting->amount_rounds_free_deposit_cat4_min &&
                                 (! isset($setting->amount_rounds_free_deposit_cat4_max) || $transaction->amount <= $setting->amount_rounds_free_deposit_cat4_max)) {
                            $dados = [
                                'username' => $user->email,
                                'game_code' => $setting->game_code_rounds_free_deposit,
                                'rounds' => $setting->rounds_free_deposit_cat4,
                            ];
                            PlayFiverService::RoundsFree($dados);
                        }
                    }
                }
                // / Só aplica o bônus de segundo depósito se o bônus de influencer não foi aplicado
                elseif (! $influencerBonusApplied && $checkTransactions == 1) {
                    // / pagar o bonus
                    if ($transaction->accept_bonus && $setting->second_deposit_bonus > 0 && $setting->second_deposit_bonus_active) {
                        $bonus = Helper::porcentagem_xn($setting->second_deposit_bonus, $transaction->amount);
                        $wallet->increment('balance_bonus', $bonus);
                        $wallet->update(['balance_bonus_rollover' => ($wallet->balance_bonus_rollover + ($bonus * $setting->rollover))]);
                    }
                }

                // / Verificar se deve conceder rodadas grátis para o segundo depósito em diante
                // / Só aplica se não for o primeiro depósito (checkTransactions > 0)
                if ($checkTransactions > 0 && $setting->game_free_rounds_active_any_deposit) {
                    // Verificar em qual categoria o depósito se enquadra (da menor para a maior)
                    if (isset($setting->amount_rounds_free_any_deposit_cat1_min) &&
                        $transaction->amount >= $setting->amount_rounds_free_any_deposit_cat1_min &&
                        (! isset($setting->amount_rounds_free_any_deposit_cat1_max) || $transaction->amount <= $setting->amount_rounds_free_any_deposit_cat1_max)) {
                        $dados = [
                            'username' => $user->email,
                            'game_code' => $setting->game_code_rounds_free_any_deposit,
                            'rounds' => $setting->rounds_free_any_deposit_cat1,
                        ];
                        PlayFiverService::RoundsFree($dados);
                    } elseif (isset($setting->amount_rounds_free_any_deposit_cat2_min) &&
                             $transaction->amount >= $setting->amount_rounds_free_any_deposit_cat2_min &&
                             (! isset($setting->amount_rounds_free_any_deposit_cat2_max) || $transaction->amount <= $setting->amount_rounds_free_any_deposit_cat2_max)) {
                        $dados = [
                            'username' => $user->email,
                            'game_code' => $setting->game_code_rounds_free_any_deposit,
                            'rounds' => $setting->rounds_free_any_deposit_cat2,
                        ];
                        PlayFiverService::RoundsFree($dados);
                    } elseif (isset($setting->amount_rounds_free_any_deposit_cat3_min) &&
                             $transaction->amount >= $setting->amount_rounds_free_any_deposit_cat3_min &&
                             (! isset($setting->amount_rounds_free_any_deposit_cat3_max) || $transaction->amount <= $setting->amount_rounds_free_any_deposit_cat3_max)) {
                        $dados = [
                            'username' => $user->email,
                            'game_code' => $setting->game_code_rounds_free_any_deposit,
                            'rounds' => $setting->rounds_free_any_deposit_cat3,
                        ];
                        PlayFiverService::RoundsFree($dados);
                    } elseif (isset($setting->amount_rounds_free_any_deposit_cat4_min) &&
                             $transaction->amount >= $setting->amount_rounds_free_any_deposit_cat4_min &&
                             (! isset($setting->amount_rounds_free_any_deposit_cat4_max) || $transaction->amount <= $setting->amount_rounds_free_any_deposit_cat4_max)) {
                        $dados = [
                            'username' => $user->email,
                            'game_code' => $setting->game_code_rounds_free_any_deposit,
                            'rounds' => $setting->rounds_free_any_deposit_cat4,
                        ];
                        PlayFiverService::RoundsFree($dados);
                    }
                }

                // / rollover deposito - CORRIGIDO: soma em vez de substituir
                $wallet->increment('balance_deposit_rollover', $transaction->amount * intval($setting->rollover_deposit));

                // / acumular bonus
                Helper::payBonusVip($wallet, $transaction->amount);

                if ($wallet->increment('balance', $transaction->amount)) {
                    if ($transaction->update(['status' => 1])) {
                        $deposit = Deposit::where('payment_id', $idTransaction)->where('status', 1)->first();
                        if (! empty($deposit)) {

                            // / fazer o deposito em cpa
                            $affHistoryCPA = AffiliateHistory::where('user_id', $user->id)
                                ->where('commission_type', 'cpa')
                                // ->where('deposited', 1)
                                ->where('status', 0)
                                ->first();

                            if (! empty($affHistoryCPA)) {

                                // / verifcia se já pode receber o cpa
                                $sponsorCpa = User::find($user->inviter);
                                if (! empty($sponsorCpa)) {
                                    if ($affHistoryCPA->deposited_amount >= $sponsorCpa->affiliate_baseline || $deposit->amount >= $sponsorCpa->affiliate_baseline) {
                                        $walletCpa = Wallet::where('user_id', $affHistoryCPA->inviter)->first();
                                        if (! empty($walletCpa)) {

                                            // / paga o valor de CPA
                                            $walletCpa->increment('refer_rewards', $sponsorCpa->affiliate_cpa); // / coloca a comissão
                                            $affHistoryCPA->update(['status' => 1, 'commission_paid' => $sponsorCpa->affiliate_cpa]); // / desativa cpa

                                        }
                                    } else {
                                        $affHistoryCPA->update(['deposited_amount' => $transaction->amount]);
                                    }
                                }
                            }

                            if ($deposit->update(['status' => 1])) {
                                $admins = User::where('role_id', 0)->get();
                                foreach ($admins as $admin) {
                                    $admin->notify(new NewDepositNotification($user->name, $transaction->amount));
                                }

                                // Registrar atividade de depósito
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
                                        'amount' => $transaction->amount,
                                        'payment_method' => 'eupago',
                                        'payment_id' => $transaction->payment_id,
                                        'location' => $locationData,
                                    ])
                                    ->log('deposit_completed');

                                // Enviar evento de compra para o Facebook Pixel
                                try {
                                    if (isset($deposit->id)) {
                                        $facebookPixelService = new \App\Services\Facebook\FacebookPixelService;
                                        $facebookPixelService->sendPurchaseEvent((string) $deposit->id);
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Erro ao enviar evento para o Facebook Pixel: '.$e->getMessage());
                                }

                                // SMS na confirmação de depósito foi removido conforme solicitado

                                return true;
                            }

                            return false;
                        }

                        return false;
                    }
                }

                return false;
            }

            return false;
        }

        return false;
    }
}
