<?php

namespace App\Services\Wallet;

use Bavix\Wallet\Services\FormatterServiceInterface;

class MoneyService
{
    public static function toInt($amount): string
    {
        return app(FormatterServiceInterface::class)->intValue($amount, 8);
    }

    public static function toDecimal(mixed $amount = 0): float|int
    {
        if (empty($amount)) {
            return 0;
        }

        return app(FormatterServiceInterface::class)->floatValue($amount, 8);
    }
}
