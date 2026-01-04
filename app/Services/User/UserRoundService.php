<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Jobs\RolloverUpdateJob;
use App\Models\UserRound;
use App\Services\Wallet\WriteBetService;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Exception as ExceptionAlias;
use Illuminate\Support\Carbon;

class UserRoundService
{
    /**
     * @throws ExceptionInterface
     */
    public static function setNewRound($req)
    {
        $data = [
            'login' => auth('api')->user()->id,
            'sessionId' => $req['identifier'],
            'bet' => (float) $req['amount'],
            'win' => 0,
            'gameId' => $req['identifier'],
            'action' => 'skill',
            'is_green' => 0,
        ];

        try {
            $writeBet = new WriteBetService($data);
            if ($writeBet->getCompleted() && $writeBet->wallet->slug === 'bounty') {
                RolloverUpdateJob::dispatch($writeBet->invoice->id)
                    ->onQueue('default');
            }

            return UserRound::create([
                'stated_at' => Carbon::now(),
                'invoice_id' => $writeBet->invoice->id,
                'session_id' => session()->getId(),
                'ip_address' => request()->getClientIp(),
            ]);

            return $writeBet->invoice;
        } catch (ExceptionInterface $exception) {
            throw new ExceptionAlias($exception->getMessage());
        }
    }

    public static function closeRound($data)
    {
        $data = [
            'login' => auth('api')->user()->id,
            'sessionId' => 1234,
            'bet' => 0,
            'win' => $data->get('win'),
            'gameId' => $data->get('gameId'),
            'action' => 'skill',
            'is_green' => $data->get('win') >= 1,
        ];
        try {
            $writeBet = new WriteBetService($data);
            if ($writeBet->getCompleted() && $writeBet->wallet->slug === 'bounty') {
                RolloverUpdateJob::dispatch($writeBet->invoice->id)
                    ->onQueue('default');
            }

            return $writeBet->invoice;
        } catch (ExceptionInterface $exception) {
            throw new ExceptionAlias($exception->getMessage());
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws \Exception
     */
    public static function store($data, ?string $roundStat = 'start')
    {

        if ($roundStat === 'start') {
            $invoice = self::setNewRound($data);

            return $invoice->userRound()->create([
                'stated_at' => now(),
                'invoice_id' => $invoice->id,
                'session_id' => request()->session()->getId(),
                'ip_address' => request()->ip(),
            ]);
        }

        // return self::closeRound($data);
    }
}
