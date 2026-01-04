<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\Order;
use App\Modules\Payments\PaymentModule;
use App\Services\Helper\NumberService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DepositService
{
    private string $endpoint = 'https://sheckout.com/api/v1/';

    public function store($data)
    {
        \Log::info('[DEBUG-INFLUENCER-BONUS] DepositService::store - Criando Order', [
            'data' => $data,
            'influencer_code' => $data['influencer_code'] ?? null,
        ]);

        $meta = [
            'deposit_bonus' => $data['deposit_bonus'],
        ];

        // Adicionar código de influencer ao meta se estiver presente
        if (isset($data['influencer_code']) && ! empty($data['influencer_code'])) {
            $meta['influencer_code'] = $data['influencer_code'];
        }

        return Order::create([
            'ulid' => Str::ulid(),
            'orderable_id' => $data['id'],
            'orderable_type' => DepositService::class,
            'paymentable_id' => 'pix',
            'paymentable_type' => PaymentModule::class,
            'amount' => $data['amount'], // app(FormatterServiceInterface::class)->intValue(, NumberService::DEFAULT_PRECISION),
            'precision' => NumberService::DEFAULT_PRECISION,
            'user_id' => auth()->user()->id,
            'paid_at' => null,
            'meta' => $meta,
        ]);
    }

    public function create($data)
    {
        $data['id'] = 0;
        $or = $this->store($data);
        if ($or) {
            $client = Http::withHeaders([
                'Authorization' => 'Bearer wDRuo6eBx9GqYwossx3aOuiYn8f9InJFHL6VZBoO8a039b5a',
            ])->post($this->endpoint.'checkout', [
                'amount' => $data['amount'],
                'currency_id' => 'brl',
                'paymentable_id' => 'pix',
                'identifier' => $or->id,
                'customer' => [
                    'name' => auth()->user()->name,
                    'tax_id' => $data['tax_id'],
                ],
            ]);
            if ($client->successful()) {
                // $data['id'] = $client->json()['id'];
                // $data['id'] = time();
                // $or = $this->store($data);

                $or->update([
                    'orderable_id' => $client->json()['id'],
                ]);

                OrderService::pay($or->id);
            }

            return $client->json();
        }

        return ['error' => 'Invalid'];
    }
}
