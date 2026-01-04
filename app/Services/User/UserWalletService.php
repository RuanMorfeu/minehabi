<?php

declare(strict_types=1);

namespace App\Services\User;

class UserWalletService
{
    public static function wallets(): array
    {
        return [
            [
                'id' => 'eur',
                'symbol' => 'EUR',
                'prefix' => '€',
                'name' => 'MultiBanco',
                'icon' => 'https://placehold.co/40x40/EEE/31343C',
                'balance' => 100,
                'decimal' => 2,
                'format' => '0,0.00',
                'current' => false,
                'slug' => 'mbank',
            ],
            [
                'id' => 'eur',
                'symbol' => 'EUR',
                'prefix' => '€',
                'name' => 'MBWay',
                'icon' => 'https://placehold.co/40x40/EEE/31343C',
                'balance' => 100,
                'decimal' => 2,
                'format' => '0,0.00',
                'current' => false,
                'slug' => 'mbway',
            ],
        ];
    }
}
