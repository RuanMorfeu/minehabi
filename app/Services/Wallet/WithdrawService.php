<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\Order;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Illuminate\Support\Str;

class WithdrawService
{
    public static function methods()
    {
        return [
            1 => 'Transferência bancária',
        ];
    }

    public static function store($data)
    {
        return Order::create([
            'ulid' => Str::ulid(),
            'orderable_id' => $data['id'],
            'orderable_type' => WithdrawService::class,
            'paymentable_id' => $data['method_id'],
            'paymentable_type' => WithdrawService::class,
            'amount' => app(FormatterServiceInterface::class)->intValue($data['amount'], 2),
            'user_id' => auth()->user()->id,
            'paid_at' => null,
            'meta' => [

            ],
        ]);
    }
}
