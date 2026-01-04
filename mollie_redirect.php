<?php

/**
 * Redirect Intermediário para Mollie
 *
 * Este arquivo deve ser colocado no site intermediário para camuflar
 * a URL de retorno real da plataforma de e-commerce
 *
 * Configuração necessária:
 * 1. Alterar $targetRedirectUrl para a URL real do seu redirect
 * 2. Configurar este arquivo como redirect URL nos pagamentos Mollie
 */

// ============================================================================
// CONFIGURAÇÕES - ALTERE CONFORME NECESSÁRIO
// ============================================================================

// URL de redirect real da plataforma dei.bet
$targetRedirectUrl = 'https://f8347cb0a146.ngrok-free.app/api/mollie/return';

// Ativar logs detalhados (recomendado apenas para debug)
$enableDetailedLogs = true;

// ============================================================================
// PROCESSAMENTO DO REDIRECT
// ============================================================================

// Função para log seguro
function logRedirect($message, $isError = false)
{
    global $enableDetailedLogs;

    if ($enableDetailedLogs || $isError) {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? '[ERROR]' : '[INFO]';
        error_log("$prefix [$timestamp] Mollie Redirect Intermediary: $message");
    }
}

// 1. Aceitar apenas GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    logRedirect('Método não permitido: '.$_SERVER['REQUEST_METHOD'], true);
    http_response_code(405);
    header('Allow: GET');
    exit('Method Not Allowed');
}

// 2. Capturar todos os parâmetros da URL
$queryParams = $_GET;
$paymentId = $queryParams['payment_id'] ?? null;

logRedirect('Redirect recebido - Payment ID: '.$paymentId);
logRedirect('Parâmetros recebidos: '.json_encode($queryParams));

// 3. Validar User-Agent (verificar se vem do Mollie ou navegador)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
logRedirect('User-Agent: '.$userAgent);

// 4. Construir URL de destino apenas com payment_id
$redirectUrl = $targetRedirectUrl;

// Adicionar apenas o payment_id se existir
if (isset($queryParams['payment_id'])) {
    $redirectUrl .= '/'.$queryParams['payment_id'];
}

logRedirect('Redirecionando para: '.$redirectUrl);

// 6. Headers de segurança
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// 7. Realizar redirect
header('Location: '.$redirectUrl, true, 302);

// 8. Cleanup e finalização
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

exit;
