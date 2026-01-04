<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;

class AresSMSService extends Controller
{
    public static function sendSMS($payload)
    {
        $data = $payload;

        // Log inicial com os dados recebidos
        \Log::info('[SMS-INTEGRATION] Iniciando envio de SMS', [
            'payload_recebido' => $data,
        ]);

        // URL de integração
        $urlIntegration = 'https://sms.aresfun.com/integration/4f1eab92-94ce-4e15-9d6f-b39c1e11a2e9';
        \Log::info('[SMS-INTEGRATION] URL de integração', ['url' => $urlIntegration]);

        // Verificar se o tipo existe
        $types = ['new', 'new-pix', 'pix-paid', 'new-withdraw'];
        $type = '';
        if (! in_array($data['type'], $types)) {
            \Log::error('[SMS-INTEGRATION] Tipo de evento inválido', [
                'tipo_recebido' => $data['type'],
                'tipos_validos' => $types,
            ]);

            return false;
        } else {
            $type = $data['type'];
            \Log::info('[SMS-INTEGRATION] Tipo de evento válido', ['tipo' => $type]);
        }

        // Verificar se o envio de SMS para este tipo de evento está ativo
        $smsSetting = \App\Models\SmsSetting::where('event_type', $type)->first();
        if (! $smsSetting || ! $smsSetting->is_active) {
            \Log::info('[SMS-INTEGRATION] Envio de SMS desativado para o evento.', ['evento' => $type]);

            return false; // Interrompe o envio se estiver desativado
        }

        // Formatar número de telefone com '+' na frente
        $phone = $data['phone'];
        if (! empty($phone)) {
            // Remover qualquer '+' existente e outros caracteres não numéricos
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Adicionar o '+' na frente
            $phone = '+'.$phone;

            \Log::info('[SMS-INTEGRATION] Número de telefone formatado', [
                'original' => $data['phone'],
                'formatado' => $phone,
            ]);
        }

        // Payload
        $payload = [
            'cpf' => null, // Não utilizamos CPF no sistema
            'name' => $data['name'],
            'email' => $data['email'],
            'event' => $type,
            'event_identify' => $data['event_identify'],
            'phone' => $phone,
            'username' => $data['username'],
            'checkout' => $data['checkout'],
            'value' => $data['value'],
        ];

        \Log::info('[SMS-INTEGRATION] Payload formatado para envio', [
            'payload' => $payload,
        ]);

        // Verificar se o telefone está presente
        if (empty($phone)) {
            \Log::warning('[SMS-INTEGRATION] Telefone não fornecido, SMS não será enviado');

            return false;
        }

        // Enviar SMS
        $jsonData = json_encode($payload);
        \Log::info('[SMS-INTEGRATION] Iniciando requisição CURL', [
            'json_payload' => $jsonData,
        ]);

        try {
            $ch = curl_init($urlIntegration);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $response = curl_exec($ch);

            // Verificar erros de CURL
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                \Log::error('[SMS-INTEGRATION] Erro CURL', [
                    'erro' => $error,
                    'codigo_erro' => curl_errno($ch),
                ]);
                curl_close($ch);

                return false;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            \Log::info('[SMS-INTEGRATION] Resposta da API', [
                'http_code' => $httpCode,
                'resposta' => $response,
            ]);

            // Verificar se a requisição foi bem-sucedida
            // Aceita códigos 200 e 201 como sucesso, e resposta 'OK' ou contendo "message"
            if (($httpCode != 200 && $httpCode != 201) || ($response !== 'OK' && strpos($response, 'message') === false)) {
                \Log::error('[SMS-INTEGRATION] Falha no envio de SMS', [
                    'http_code' => $httpCode,
                    'resposta' => $response,
                    'telefone' => $phone,
                    'evento' => $type,
                ]);

                return false;
            }

            // Se a resposta contém "Automation not found", ainda é considerado sucesso
            // pois o serviço AresSMS está recebendo corretamente, apenas não tem automação configurada

            \Log::info('[SMS-INTEGRATION] SMS enviado com sucesso', [
                'telefone' => $phone,
                'evento' => $type,
                'identificador' => $data['event_identify'],
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('[SMS-INTEGRATION] Exceção ao enviar SMS', [
                'mensagem' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
            ]);

            return false;
        }
    }
}
