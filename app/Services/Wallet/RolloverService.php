<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\Order;
use App\Models\Rollover;
use Illuminate\Support\Arr;

class RolloverService
{
    public static function get(int $rolloverId): Rollover
    {
        return Rollover::where('active', 1)->where('id', $rolloverId)->firstOrFail();
    }

    public static function update($data, int $rolloverId): void
    {
        if (Arr::has($data, 'cash_in')) {
            $rollover = self::get($rolloverId);
            $rollover->fulfilled += $data['cash_in'] + Arr::get($data, 'meta.win', 0);
            $rollover->save();
        }
    }

    public static function onComputed(Order $order)
    {
        $target = 0;
        $fulfilled = 0;
        $rolloverId = 0;

        $rollover = Rollover::where('active', 1)->where('wallet_id', $order->transaction->wallet->id)->orderBy('id', 'desc')->first();

        if ($rollover) {
            $target = $rollover->target;
            $fulfilled = $rollover->fulfilled;
            $rolloverId = $rollover->id;

            $rollover->active = 0;
            $rollover->save();
        }

        $newRollover = [
            'active' => 1,
            'wallet_id' => $order->transaction->wallet->id,
            'rollover_id' => $rolloverId,
            'rollover' => $order->transaction->wallet->slug,
            'meta' => [
                'no' => 'job',
            ],
            'target' => $target + ($order->transaction->amountFloat * 2),
            'fulfilled' => $fulfilled,
        ];

        return Rollover::create($newRollover);
    }
}
