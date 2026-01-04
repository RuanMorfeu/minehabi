<?php

declare(strict_types=1);

namespace App\Services\Providers\Push;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function __construct(mixed $phoneNumber, string $message)
    {
        $send = Http::withHeaders([
            'Authorization' => 'Basic 4ee0f28c08c3632e8cfba8f36c7191815dbc8408',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://apihttp.disparopro.com.br:8433/mt', [
            'numero' => '5518981478080',
            'servico' => 'short',
            'mensagem' => 'HAGABET code: 8989',
            'parceiro_id' => '5034e65a0c',
            'codificacao' => '0',
        ]);

        return $send->successful();
    }
}
