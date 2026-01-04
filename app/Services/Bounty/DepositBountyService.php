<?php

namespace App\Services\Bounty;

use App\Models\Deposit;
use App\Models\InfluencerBonus;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Wallet\MoneyService;
use Illuminate\Support\Facades\Log;

class DepositBountyService
{
    /**
     * Processa o bônus de depósito (primeiro ou segundo depósito)
     */
    /**
     * Obtém o código de influencer do pedido
     */
    /**
     * Obtém o bônus de influencer ativo para um pedido
     */
    private static function getInfluencerBonus(Order $order)
    {
        Log::info('[DEBUG-INFLUENCER-BONUS] Verificando bônus de influencer para o pedido', [
            'order_id' => $order->id,
            'meta' => $order->meta,
            'has_influencer_code' => isset($order->meta['influencer_code']),
            'influencer_code' => $order->meta['influencer_code'] ?? 'não definido',
        ]);

        // Verifica se há um código de influencer no pedido
        if (empty($order->meta['influencer_code'])) {
            Log::info('[DEBUG-INFLUENCER-BONUS] Nenhum código de influencer encontrado no pedido');

            return null;
        }

        $influencerCode = $order->meta['influencer_code'];

        // Busca o bônus ativo com o código fornecido
        $bonus = InfluencerBonus::where('code', $influencerCode)
            ->where('is_active', true)
            ->first();

        if (! $bonus) {
            Log::info('[DEBUG-INFLUENCER-BONUS] Nenhum bônus ativo encontrado para o código', [
                'influencer_code' => $influencerCode,
            ]);

            return null;
        }

        // Verifica se o bônus é de uso único e se o usuário já o resgatou
        if ($bonus->one_time_use) {
            $user = User::find($order->user_id);
            $alreadyRedeemed = \App\Models\InfluencerBonusRedemption::where('user_id', $user->id)
                ->where('influencer_bonus_id', $bonus->id)
                ->exists();

            if ($alreadyRedeemed) {
                Log::info('[DEBUG-INFLUENCER-BONUS] Bônus de uso único já resgatado pelo usuário', [
                    'user_id' => $user->id,
                    'bonus_id' => $bonus->id,
                    'bonus_code' => $bonus->code,
                ]);

                return null; // Impede a aplicação do bônus
            }
        }

        Log::info('[DEBUG-INFLUENCER-BONUS] Bônus de influencer encontrado', [
            'bonus_id' => $bonus->id,
            'name' => $bonus->name,
            'code' => $bonus->code,
            'bonus_percentage' => $bonus->bonus_percentage,
            'max_bonus' => $bonus->max_bonus,
            'min_deposit' => $bonus->min_deposit,
        ]);

        return $bonus;
    }

    public static function payFirstDepositBonus(Order $order)
    {
        \Log::info('[DEBUG-INFLUENCER-BONUS] 1️⃣ Iniciando payFirstDepositBonus', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'meta' => $order->meta,
            'deposit_bonus' => $order->meta['deposit_bonus'] ?? 'não definido',
            'influencer_code' => $order->meta['influencer_code'] ?? 'não definido',
            'deposit_amount' => $order->amount,
        ]);

        $setting = Setting::first();
        $user = User::find($order->user_id);

        // Verifica se o campo deposit_bonus existe e é true
        if (isset($order->meta['deposit_bonus']) && $order->meta['deposit_bonus']) {
            \Log::info('[DEBUG-INFLUENCER-BONUS] 2️⃣ Deposit bonus está ativado no pedido', [
                'order_id' => $order->id,
                'deposit_bonus' => $order->meta['deposit_bonus'],
            ]);
            // Verifica se é o primeiro ou segundo depósito
            $depositCount = Deposit::where('user_id', $user->id)
                ->where('status', 1)
                ->count();

            \Log::info('[DEBUG-INFLUENCER-BONUS] Contagem de depósitos do usuário', [
                'user_id' => $user->id,
                'deposit_count' => $depositCount,
            ]);

            // Verifica se há um bônus de influencer válido para este pedido
            $influencerBonus = self::getInfluencerBonus($order);
            $hasInfluencerBonus = $influencerBonus !== null;

            \Log::info('[DEBUG-INFLUENCER-BONUS] 3️⃣ Verificação de bônus de influencer', [
                'has_influencer_bonus' => $hasInfluencerBonus,
                'influencer_code' => $order->meta['influencer_code'] ?? 'não definido',
                'bonus_id' => $hasInfluencerBonus ? $influencerBonus->id : 'não aplicável',
                'bonus_name' => $hasInfluencerBonus ? $influencerBonus->name : 'não aplicável',
                'bonus_percentage' => $hasInfluencerBonus ? $influencerBonus->bonus_percentage : 0,
                'min_deposit' => $hasInfluencerBonus ? $influencerBonus->min_deposit : 0,
                'max_bonus' => $hasInfluencerBonus ? $influencerBonus->max_bonus : 0,
                'one_time_use' => $hasInfluencerBonus ? $influencerBonus->one_time_use : false,
            ]);

            // Define as variáveis de bônus
            $bonusPercentage = 0;
            $bonusType = '';
            $maxBonus = 0;
            $minDeposit = 0;
            $bonusName = '';
            $bonusId = null;

            // Se houver um bônus de influencer válido, verifica se atende aos requisitos
            if ($hasInfluencerBonus) {
                $minDeposit = $influencerBonus->min_deposit;
                $meetsMinDepositRequirement = $minDeposit <= 0 || $order->amount >= $minDeposit;

                \Log::info('[DEBUG-INFLUENCER-BONUS] 4️⃣ Verificação de requisitos mínimos', [
                    'min_deposit_required' => $minDeposit,
                    'deposit_amount' => $order->amount,
                    'meets_requirement' => $meetsMinDepositRequirement,
                ]);

                if ($meetsMinDepositRequirement) {
                    // Bônus de influencer (tem prioridade sobre os outros)
                    $bonusPercentage = $influencerBonus->bonus_percentage;
                    $maxBonus = $influencerBonus->max_bonus;
                    $bonusType = 'influencer_bonus';
                    $bonusName = $influencerBonus->name;
                    $bonusId = $influencerBonus->id;

                    \Log::info('[DEBUG-INFLUENCER-BONUS] 5️⃣ Aplicando bônus de influencer', [
                        'bonus_id' => $bonusId,
                        'bonus_name' => $bonusName,
                        'bonus_percentage' => $bonusPercentage,
                        'max_bonus' => $maxBonus,
                        'deposit_amount' => $order->amount,
                        'calculated_bonus' => $order->amount * ($bonusPercentage / 100),
                    ]);

                    Log::info('[DEBUG-INFLUENCER-BONUS] Aplicando bônus de influencer', [
                        'bonus_id' => $bonusId,
                        'bonus_name' => $bonusName,
                        'bonus_percentage' => $bonusPercentage,
                        'max_bonus' => $maxBonus,
                        'min_deposit' => $minDeposit,
                        'bonus_type' => $bonusType,
                    ]);
                } else {
                    Log::info('[DEBUG-INFLUENCER-BONUS] Valor do depósito insuficiente para o bônus de influencer', [
                        'amount' => $order->amount,
                        'min_deposit_required' => $minDeposit,
                        'bonus_id' => $influencerBonus->id,
                        'bonus_name' => $influencerBonus->name,
                    ]);
                }
            } elseif ($depositCount === 1 && $setting->initial_bonus > 0) {
                // Primeiro depósito
                $bonusPercentage = $setting->initial_bonus;
                $bonusType = 'first_deposit_bonus';
                \Log::info('[DEBUG-INFLUENCER-BONUS] Aplicando bônus de primeiro depósito', [
                    'bonus_percentage' => $bonusPercentage,
                    'bonus_type' => $bonusType,
                ]);
            } elseif ($depositCount === 2 && $setting->second_deposit_bonus > 0 && $setting->second_deposit_bonus_active) {
                // Segundo depósito
                $bonusPercentage = $setting->second_deposit_bonus;
                $bonusType = 'second_deposit_bonus';
                \Log::info('[DEBUG-INFLUENCER-BONUS] Aplicando bônus de segundo depósito', [
                    'bonus_percentage' => $bonusPercentage,
                    'bonus_type' => $bonusType,
                ]);
            } else {
                \Log::info('[DEBUG-INFLUENCER-BONUS] Nenhum bônus aplicável', [
                    'has_influencer_bonus' => $hasInfluencerBonus,
                    'deposit_count' => $depositCount,
                    'initial_bonus_active' => $setting->initial_bonus > 0,
                    'second_bonus_active' => $setting->second_deposit_bonus > 0 && $setting->second_deposit_bonus_active,
                ]);
            }

            if ($bonusPercentage > 0) {
                $walletBounty = $user->getWallet('bounty');

                if (! $walletBounty) {
                    $walletBounty = $user->createWallet([
                        'name' => 'bounty',
                        'slug' => 'bounty',
                    ]);
                }

                // Calcula o valor do bônus
                $calculatedBonus = $order->amount * ($bonusPercentage / 100);

                // Aplica o limite máximo para bônus de influencer se configurado
                if ($bonusType === 'influencer_bonus' && $maxBonus > 0 && $calculatedBonus > $maxBonus) {
                    $amountBounty = $maxBonus;
                    \Log::info('[DEBUG-INFLUENCER-BONUS] 6️⃣ Bônus limitado pelo valor máximo', [
                        'calculated_bonus' => $calculatedBonus,
                        'max_bonus' => $maxBonus,
                        'applied_bonus' => $amountBounty,
                        'bonus_id' => $bonusId,
                        'bonus_name' => $bonusName,
                    ]);
                } else {
                    $amountBounty = $calculatedBonus;
                    \Log::info('[DEBUG-INFLUENCER-BONUS] 6️⃣ Valor do bônus calculado', [
                        'calculated_bonus' => $calculatedBonus,
                        'applied_bonus' => $amountBounty,
                        'bonus_id' => $bonusId,
                        'bonus_name' => $bonusName,
                    ]);
                }

                // Deposita o bônus na carteira bounty
                $meta = [
                    'order_id' => $order->id,
                    'origin' => 'deposit',
                    'description' => $bonusName ?: $bonusType,
                ];

                // Adiciona informações adicionais para bônus de influencer
                if ($bonusType === 'influencer_bonus' && $influencerBonus) {
                    $meta['influencer_bonus_id'] = $influencerBonus->id;
                    $meta['influencer_code'] = $influencerBonus->code;
                    $meta['influencer_name'] = $influencerBonus->name;

                    \Log::info('[DEBUG-INFLUENCER-BONUS] 7️⃣ Metadados do bônus de influencer', [
                        'meta' => $meta,
                        'bonus_id' => $influencerBonus->id,
                        'amount_bounty' => $amountBounty,
                    ]);
                }

                try {
                    \Log::info('[DEBUG-INFLUENCER-BONUS] 8️⃣ Tentando depositar bônus na carteira', [
                        'wallet_id' => $walletBounty->id,
                        'amount_bounty' => $amountBounty,
                        'amount_int' => MoneyService::toInt($amountBounty),
                        'meta' => $meta,
                    ]);

                    $bountyDeposit = $walletBounty->deposit(
                        MoneyService::toInt($amountBounty),
                        $meta
                    );

                    \Log::info('[DEBUG-INFLUENCER-BONUS] ✅ Bônus depositado com sucesso', [
                        'transaction_id' => $bountyDeposit->id ?? 'n/a',
                        'wallet_balance' => $walletBounty->balance,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('[DEBUG-INFLUENCER-BONUS] ❌ Erro ao depositar bônus', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

                // Se o bônus for de influencer e de uso único, registra o resgate
                if ($bonusType === 'influencer_bonus' && $influencerBonus && $influencerBonus->one_time_use) {
                    try {
                        $redemption = \App\Models\InfluencerBonusRedemption::create([
                            'user_id' => $user->id,
                            'influencer_bonus_id' => $influencerBonus->id,
                            'deposit_amount' => $order->amount,
                            'bonus_amount' => $amountBounty,
                        ]);

                        \Log::info('[DEBUG-INFLUENCER-BONUS] 9️⃣ ✅ Resgate de bônus de uso único registrado', [
                            'redemption_id' => $redemption->id,
                            'user_id' => $user->id,
                            'bonus_id' => $influencerBonus->id,
                            'deposit_amount' => $order->amount,
                            'bonus_amount' => $amountBounty,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('[DEBUG-INFLUENCER-BONUS] ❌ Erro ao registrar resgate de bônus', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                            'bonus_id' => $influencerBonus->id,
                        ]);
                    }
                }

                // Atualiza o rollover
                $fulfilled = 0;
                $target = 0;

                $rolloverCurrent = \App\Models\Rollover::query()
                    ->where('wallet_id', $walletBounty->id)
                    ->where('rollover', $walletBounty->slug)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($rolloverCurrent) {
                    $rolloverCurrent->active = 0;
                    $rolloverCurrent->save();
                    $fulfilled = $rolloverCurrent->fulfilled;
                    $target = $rolloverCurrent->target;
                }

                \App\Models\Rollover::create([
                    'active' => 1,
                    'wallet_id' => $walletBounty->id,
                    'rollover_id' => 0,
                    'rollover' => $walletBounty->slug,
                    'meta' => [$bountyDeposit],
                    'target' => $target + ($amountBounty * $setting->rollover), // Usa o multiplicador de rollover configurado no admin
                    'fulfilled' => $fulfilled,
                ]);
            }
        }
    }
}
