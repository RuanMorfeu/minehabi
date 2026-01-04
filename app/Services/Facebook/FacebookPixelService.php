<?php

declare(strict_types=1);

namespace App\Services\Facebook;

use App\Models\Deposit;
use App\Models\User;

class FacebookPixelService
{
    private string $pixelId;

    private string $accessToken;

    public function __construct()
    {
        // Obter configurações do banco de dados
        $settings = \App\Models\Setting::first();

        // Usar valores do banco de dados ou valores padrão
        $this->pixelId = $settings->facebook_pixel_id ?? '641305108716070';
        $this->accessToken = $settings->facebook_access_token ?? 'EAAO9hYqUMOYBO428jfPpkLxvSrapZAfFeFkunEg23z7e5GmAHt3LX386zZCDvxdxXpf4M41KnwuXl9kZCqSW6sShtD5vrcZCRYxzBKQv4ba8g65yE0ll9zh5D2ZASZABb1BkWhl0qXi5ZAbQalxbtWhVH3LsrzTZBKomAFolxzvb1MClKULBBwwHLM3YJPXhcyVftQZDZD';
    }

    public function sendPurchaseEvent(string $depositId): void
    {
        $deposit = Deposit::find($depositId);

        if (! $deposit) {
            throw new \InvalidArgumentException('Depósito não encontrado.');
        }

        $user = User::find($deposit->user_id);
        if (! $user) {
            throw new \InvalidArgumentException('Usuário não encontrado.');
        }

        $nomeCompleto = $user->name ?? '';
        $firstname = explode(' ', $nomeCompleto)[0] ?? '';
        $lastname = '';
        $strParts = strstr($nomeCompleto, ' ');
        if (is_string($strParts)) {
            $lastname = trim($strParts);
        }
        $phone = $user->phone ?? '';
        $totalAmount = $deposit->amount ?? 0.0;

        $url = "https://graph.facebook.com/v14.0/{$this->pixelId}/events?access_token={$this->accessToken}";
        $hashedData = [
            'fn' => hash('sha256', $firstname),
            'ln' => hash('sha256', $lastname),
            'ph' => hash('sha256', $phone),
            'external_id' => hash('sha256', $depositId),
        ];

        // Adicionar IP e User Agent se disponíveis
        if (! empty($user->last_login_ip)) {
            $hashedData['client_ip_address'] = $user->last_login_ip;
        }

        if (! empty($user->user_agent)) {
            $hashedData['client_user_agent'] = $user->user_agent;
        }

        // Adicionar o fbc (Facebook Click ID) se disponível
        // O fbc geralmente é armazenado em um cookie chamado '_fbc'
        $fbc = null;

        // Tentar obter o fbc do cookie, se disponível
        if (isset($_COOKIE['_fbc'])) {
            $fbc = $_COOKIE['_fbc'];
        }
        // Ou tentar obter de um campo do usuário, se você estiver armazenando
        elseif (! empty($user->facebook_click_id)) {
            $fbc = $user->facebook_click_id;
        }

        if ($fbc) {
            $hashedData['fbc'] = $fbc;
        }

        $data = [
            [
                'event_name' => 'Purchase',
                'event_time' => time(),
                'user_data' => $hashedData,
                'custom_data' => [
                    'currency' => $deposit->currency ?? 'BRL',
                    'value' => number_format((float) $totalAmount, 2, '.', ''),
                ],
            ],
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['data' => $data]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['fbtrace_id'])) {
                // Evento enviado com sucesso
                \Log::info("Facebook Purchase Event enviado com sucesso. ID do evento: {$result['fbtrace_id']}");
            } else {
                // Erro ao enviar o evento
                \Log::error('Erro ao enviar o Facebook Purchase Event: '.json_encode($result));
            }
        } else {
            // Ocorreu um erro ao enviar o evento
            \Log::error("Ocorreu um erro ao enviar o Facebook Purchase Event: {$error}");
        }
    }
}
