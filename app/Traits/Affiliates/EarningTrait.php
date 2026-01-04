<?php

namespace App\Traits\Affiliates;

use App\Models\AffiliateHistory;
use App\Models\User;
use App\Models\Wallet;

trait EarningTrait
{
    /*** @param User $user // ID do afiliado
     * @return void
     */
    public static function affiliateRevshare(User $user)
    {
        $affiliateHistories = AffiliateHistory::where('inviter', $user->id)->where('commission_type', 'revshare')->where('status', 0)->get();
        if (count($affiliateHistories) > 0) {
            foreach ($affiliateHistories as $affiliateHistory) {

                // / o valor de perda é maior que o valor depositado
                if ($affiliateHistory->losses_amount >= $affiliateHistory->deposited_amount) {

                    // / pega a porcentagem do ganho
                    $gains = \Helper::porcentagem_xn($affiliateHistory->commission, $affiliateHistory->losses_amount);
                    $wallet = Wallet::where('user_id', $user->id)->first();
                    $wallet->increment('refer_rewards', $gains);
                }
            }
        }
    }

    /*** @param User $user // ID do afiliado
     * @return void
     */
    public static function affiliateCpa(User $user)
    {
        // Check if CPA is enabled for this user
        if (! $user->cpa_enabled) {
            return;
        }

        // Apply percentage-based CPA counting
        if ($user->cpa_percentage < 100) {
            // Generate a random number between 1 and 100
            $random = rand(1, 100);
            // If random number is greater than the percentage, skip CPA
            if ($random > $user->cpa_percentage) {
                return;
            }
        }

        $affiliateHistories = AffiliateHistory::where('inviter', $user->id)->where('commission_type', 'cpa')->where('status', 0)->get();
        if (count($affiliateHistories) > 0) {
            foreach ($affiliateHistories as $affiliateHistory) {
                // / o valor de perda é maior que o valor depositado
                if ($affiliateHistory->losses_amount >= $affiliateHistory->deposited_amount) {
                    // / pega a porcentagem do ganho
                    $wallet = Wallet::where('user_id', $user->id)->first();
                    $wallet->increment('refer_rewards', $user->affiliate_cpa);
                }
            }
        }
    }
}
