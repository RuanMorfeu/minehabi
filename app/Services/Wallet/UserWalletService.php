<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\User;

class UserWalletService
{
    public static function list()
    {
        if (auth()->check()) {
            $user = auth()->user();
            $wallets = [
                'default' => MoneyService::toDecimal($user->getWallet('default')?->balance),
                'bounty' => MoneyService::toDecimal($user->getWallet('bounty')?->balance),
                'affiliate' => MoneyService::toDecimal($user->getWallet('affiliate')?->balance),
            ];

            $wallets['total'] = number_format(($wallets['bounty'] + $wallets['default']), 2, '.', '');

            return $wallets;
        }

        return [
            'default' => 0,
        ];
    }

    public static function getWallet($userId)
    {
        $user = User::find($userId);
        if (! $user->hasWallet('default')) {
            $wallet = $user->createWallet([
                'name' => 'default',
                'slug' => 'default',
            ]);

            return $wallet->id;
        }

        return $user->wallet->id;
    }

    public static function getWalletBounty($userId)
    {
        $user = User::find($userId);
        if (! $user->hasWallet('bounty')) {
            $wallet = $user->createWallet([
                'name' => 'bounty',
                'slug' => 'bounty',
            ]);

            return $wallet->id;
        }

        return $user->wallet->id;
    }

    public static function getWalletAffiliate($userId)
    {
        $user = User::find($userId);
        if (! $user->hasWallet('affiliate')) {
            $wallet = $user->createWallet([
                'name' => 'affiliate',
                'slug' => 'affiliate',
            ]);

            return $wallet->id;
        }

        return $user->wallet->id;
    }
}
