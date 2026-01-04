<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\User;

class BalanceService
{
    public static function get(): void
    {
        $userWallet = auth()->user()->wallets();
    }

    public static function balance(?User $user = null)
    {
        return $user->wallets->whereIn('slug', ['default', 'bounty', 'btc'])->sum('balanceFloat');
    }
}
