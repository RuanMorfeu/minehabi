<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\Invoice;
use App\Models\ProviderGame;
use App\Models\User;
use App\Services\Providers\XpDealService;
use App\Services\Settings\SettingService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Wallet;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @property Collection $data
 */
class WriteBetService
{
    public Wallet $wallet;

    public Invoice $invoice;

    /*
     * spin, reSpin, WIN
     */
    public string $type;

    public bool $completed = false;

    public int $error = 0;

    public User $user;

    private $data;

    public function __construct($data)
    {
        try {
            $this->data = collect($data);
            $this->user = $this->setUser();

            $this->setWallet();

            $this->createInvoice($data);
            $this->setDeposit(); // case win

            $this->completed = true;
        } catch (Exception $e) {
            $this->error = 1;
            Log::error($e->getMessage());
        } catch (ExceptionInterface $e) {
            $this->error = 1;
            Log::error($e->getMessage());
        }
    }

    public function getCompleted(): Invoice
    {
        return $this->invoice;
    }

    public function setUser()
    {
        return User::find($this->data['login']);
    }

    public function setWallet(): void
    {
        $wallet = 'default';
        if (
            SettingService::getConfigBountyFirst() &&
            ($this->user->hasWallet('bounty') &&
                $this->user->getWallet('bounty')?->balance > 0)
        ) {
            $wallet = 'bounty';
        }

        $this->wallet = $this->user->getWallet($wallet);

    }

    /**
     * @throws Exception|ExceptionInterface
     */
    public function setDeposit(): void
    {
        try {
            if ($this->getWinAmount() > 0) {
                $this->wallet->deposit(
                    $this->getWinAmount(),
                    [
                        'description' => 'bet.win.text',
                        'invoice_id' => $this->invoice->id,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $this->error = 1;
            throw new Exception('Deposit fail');
        }
    }

    public function getWinAmount()
    {
        $winAmount = Arr::get($this->data, 'win', 0);

        return MoneyService::toInt($winAmount);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function payInvoice(): void
    {

        try {

            $this->wallet->withdraw(
                MoneyService::toInt($this->invoice->cash_in),
                [
                    'description' => 'bet.label.text',
                    'invoice_id' => $this->invoice->id,
                ]
            );

        } catch (Exception $e) {
            $this->error = 1;
            Log::alert($e->getMessage());
            throw new Exception('Invoice error paid');
        }

    }

    public function createSpentId(array|Collection $data): int
    {
        $spentId = 'ig'.Arr::get($data, 'tradeId', Str::ulid());

        return crc32($spentId);
    }

    public function createInvoice($data): void
    {
        try {
            $this->invoice = Invoice::create([
                'user_id' => $this->user->id,
                'cash_in' => Arr::get($this->data, 'bet', 1),
                'cash_out' => Arr::get($this->data, 'win', 0),
                'amount' => 0,
                'invoiceable_type' => ProviderGame::class,
                'invoiceable_id' => Arr::get($this->data, 'gameId'),
                'title' => __('bet.play.label_to'),
                'meta' => $this->data,
                'providerable_type' => XpDealService::class,
                'providerable_id' => Arr::get($this->data, 'sessionId'),
                'action' => Arr::get($this->data, 'action'),
                'wallet_id' => $this->wallet->id,
                'spent_id' => $this->createSpentId($this->data),
            ]);

            $this->payInvoice();
        } catch (Exception $e) {
            $this->error = 1;
            Log::alert($e->getMessage());
        } catch (ExceptionInterface $e) {
            Log::alert($e->getMessage());
        }
    }

    public function getData(): Collection
    {
        return $this->data;
    }

    public function getUserWallet(): string
    {
        $walletName = 'default';
        if (SettingService::getConfigBountyFirst()) {
            $walletName = 'bounty';
        }

        return $walletName;
    }
}
