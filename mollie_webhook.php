<?php

/**
 * Webhook Intermediário para Mollie
 *
 * Este arquivo deve ser colocado em um site intermediário para camuflar
 * o webhook real da plataforma de e-commerce
 *
 * Configuração necessária:
 * 1. Alterar $mollieApiKey para sua chave API do Mollie
 * 2. Alterar $mollieProfileId para seu Profile ID do Mollie
 * 3. Alterar $signingSecret para o valor correto do Mollie
 * 4. Alterar $targetWebhookUrl para a URL real do seu webhook
 * 5. Configurar este arquivo como webhook URL no painel Mollie
 */

// ============================================================================
// CONFIGURAÇÕES - ALTERE CONFORME NECESSÁRIO
// ============================================================================

// Credenciais do Mollie (obtidas no painel Mollie)
$mollieApiKey = 'test_6AJypJvbj3nQFuKs5pR5wqnPN74Nv5'; // ou test_SEU_API_KEY_AQUI para testes
$mollieProfileId = 'pfl_oA69gFvKfj';
$signingSecret = 'tEVAEfbEVNqNAa8pQahfdmNNz97bP6Fu';

// URL do webhook real da plataforma dei.bet
$targetWebhookUrl = 'https://2d147934b715.ngrok-free.app/api/mollie/webhook';

// Timeout para requisição (segundos)
$requestTimeout = 10;

// Ativar logs detalhados (recomendado apenas para debug)
$enableDetailedLogs = true;

// ============================================================================
// PROCESSAMENTO DO WEBHOOK
// ============================================================================

// Função para log seguro
function logMessage($message, $isError = false)
{
    global $enableDetailedLogs;

    if ($enableDetailedLogs || $isError) {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? '[ERROR]' : '[INFO]';
        error_log("$prefix [$timestamp] Mollie Webhook Intermediary: $message");
    }
}

// 1. Aceitar apenas POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage('Método não permitido: '.$_SERVER['REQUEST_METHOD'], true);
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

// 2. Ler dados da requisição
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_MOLLIE_SIGNATURE'] ?? '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

logMessage('Webhook recebido - Content-Type: '.$contentType);
logMessage('User-Agent: '.$userAgent);

// 3. Validar origem (verificar se vem do Mollie)
if (strpos($userAgent, 'Mollie') === false) {
    logMessage('User-Agent suspeito: '.$userAgent, true);
    http_response_code(403);
    exit('Forbidden');
}

// 4. Verificar se é um evento de ping primeiro (antes da verificação de assinatura)
$isPingEvent = false;
if (strpos($contentType, 'application/json') !== false) {
    $event = json_decode($body, true);
    if ($event && isset($event['type']) && $event['type'] === 'hook.ping') {
        $isPingEvent = true;
        logMessage('Evento de ping detectado - pulando verificação de assinatura');
    }
}

// 5. Verificar assinatura se presente (obrigatório para next-gen webhooks, exceto pings)
if (! empty($signature) && ! $isPingEvent) {
    logMessage('Verificando assinatura webhook...');

    // Remover prefixo sha256= se presente
    $receivedSignature = str_replace('sha256=', '', $signature);

    // Calcular HMAC SHA-256 usando body + signing secret
    $calculatedSignature = hash_hmac('sha256', $body, $signingSecret);

    // Verificar se as assinaturas coincidem (comparação segura)
    if (! hash_equals($calculatedSignature, $receivedSignature)) {
        logMessage('Assinatura inválida - Recebida: '.$receivedSignature.' | Calculada: '.$calculatedSignature, true);
        http_response_code(401);
        exit('Unauthorized - Invalid signature');
    }

    logMessage('Assinatura verificada com sucesso');
} elseif (! $isPingEvent) {
    logMessage('Webhook sem assinatura (webhook clássico)');
}

// 6. Processar dados do webhook
$paymentId = null;
$eventType = null;

if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
    // Webhook clássico: id=tr_abc123
    parse_str($body, $data);
    $paymentId = $data['id'] ?? null;
    logMessage('Webhook clássico - Payment ID: '.$paymentId);
} else {
    // Next-gen webhook: JSON event object
    if (! isset($event)) {
        $event = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('Erro ao decodificar JSON: '.json_last_error_msg(), true);
            http_response_code(400);
            exit('Invalid JSON');
        }
    }

    $eventType = $event['type'] ?? null;

    // Verificar se é um evento de ping
    if ($eventType === 'hook.ping') {
        logMessage('Ping do webhook recebido - respondendo OK');
        http_response_code(200);
        echo 'OK';
        exit;
    }

    // Extrair payment ID do evento
    $paymentId = $event['entityId'] ?? null;
    logMessage('Next-gen webhook - Tipo: '.$eventType.' | Payment ID: '.$paymentId);
}

// 6. Validar payment ID (apenas para eventos de pagamento)
if ($eventType !== 'hook.ping') {
    if (empty($paymentId)) {
        logMessage('Payment ID não fornecido', true);
        http_response_code(400);
        exit('Payment ID required');
    }

    // Validar formato do payment ID do Mollie
    if (! preg_match('/^(tr_|ord_|sub_)[a-zA-Z0-9]{10,}$/', $paymentId)) {
        logMessage('Payment ID com formato inválido: '.$paymentId, true);
        http_response_code(400);
        exit('Invalid payment ID format');
    }
}

// 7. Preparar dados para repassar
$forwardData = [];

if ($eventType === 'hook.ping') {
    $forwardData = ['type' => 'hook.ping'];
} else {
    $forwardData = ['id' => $paymentId];

    // Adicionar tipo de evento se disponível
    if ($eventType) {
        $forwardData['type'] = $eventType;
    }
}

// 8. Repassar para plataforma dei.bet
logMessage('Repassando webhook para: '.$targetWebhookUrl);

// Adicionar credenciais Mollie aos dados se necessário
$forwardData['_mollie_credentials'] = [
    'api_key' => $mollieApiKey,
    'profile_id' => $mollieProfileId,
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetWebhookUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($forwardData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: Mollie-Webhook-Intermediary/1.0',
        'X-Forwarded-From: '.($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        'X-Mollie-Profile-Id: '.$mollieProfileId,
        'X-Mollie-Api-Key: '.$mollieApiKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => $requestTimeout,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 9. Log da resposta
if ($curlError) {
    logMessage('Erro cURL: '.$curlError, true);
    http_response_code(500);
    echo 'Error - Connection failed';
    exit;
}

logMessage('Resposta da plataforma - HTTP: '.$httpCode.' | Body: '.substr($response, 0, 100));

// 10. Responder ao Mollie rapidamente (conforme documentação)
if ($httpCode >= 200 && $httpCode < 300) {
    logMessage('Webhook processado com sucesso');
    http_response_code(200);
    echo 'OK';
} else {
    logMessage('Erro no processamento - HTTP: '.$httpCode, true);
    http_response_code(500);
    echo 'Error';
}

// 11. Cleanup e finalização
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
