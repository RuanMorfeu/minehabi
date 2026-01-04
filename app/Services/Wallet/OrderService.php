<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Jobs\WalletRolloverJob;
use App\Models\Order;
use App\Models\Rollover;
use App\Models\User;
use App\Services\Bounty\DepositBountyService;
use App\States\Order\Paid;

class OrderService
{
    public static function getUnpaidBy(string|int $value, string $param = 'id'): mixed
    {
        return Order::with(['user'])->unpaid()->where($param, $value)->first();
    }

    public static function getPaidBy(string|int $value, string $param = 'id')
    {
        return Order::with(['user'])
            // ->where('state', Paid::class)
            ->whereNotNull('paid_at')
            ->where($param, $value)
            ->first();
    }

    public static function pay(int $id): bool
    {
        \Log::info('[DEBUG-INFLUENCER-BONUS] Iniciando OrderService::pay', [
            'order_id' => $id,
        ]);

        $order = self::getUnpaidBy($id, 'id');

        if ($order) {
            $order->update([
                'audit' => [
                    'balance' => $order->user->balance,
                ],
            ]);
            $transaction = $order->user->deposit(
                MoneyService::toInt($order->amount),
                [
                    'order_id' => $order->id,
                    'description' => 'deposit',
                ]
            );

            $order->state->transitionTo(Paid::class);
            $order->paid_at = now();
            $order->transaction_id = $transaction->id;

            if ($transaction && $order->save()) {
                WalletRolloverJob::dispatch($order);

                self::payBounty($order->id);
                DepositBountyService::payFirstDepositBonus($order);

                return true;
            }
        }

        return false;
    }

    public static function payBounty($orderId)
    {

        $order = self::getPaidBy($orderId, 'id');

        if ($order) {

            $user = User::find($order->user_id);

            if ($order->meta['deposit_bonus']) {

                $walletBounty = $user->getWallet('bounty');

                if (! $walletBounty) {
                    $walletBounty = $user->createWallet([
                        'name' => 'bounty',
                        'slug' => 'bounty',
                    ]);
                }

                $amountBounty = $order->amount * 0.3;

                $bountyDeposit = $walletBounty->deposit(
                    MoneyService::toInt($amountBounty),
                    [
                        'order_id' => $order->id,
                        'origin' => 'deposit',
                        'description' => 'deposit_bonus',
                    ]
                );

                $fulfilled = 0;
                $target = 0;

                $rolloverCurrent = Rollover::query()
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

                Rollover::create([
                    'active' => 1,
                    'wallet_id' => $walletBounty->id,
                    'rollover_id' => 0,
                    'rollover' => $walletBounty->slug,
                    'meta' => [$bountyDeposit],
                    'target' => $target + ($amountBounty * 2),
                    'fulfilled' => $fulfilled,
                ]);
            }

            return false;
        }
    }
}
