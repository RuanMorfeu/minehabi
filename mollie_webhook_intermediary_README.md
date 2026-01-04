# Webhook Intermediário Mollie - Guia de Configuração

## Objetivo
Este arquivo PHP serve como um webhook intermediário para camuflar a URL real do webhook da plataforma dei.bet, adicionando uma camada extra de segurança.

## Configuração no Site Intermediário

### 1. Upload do Arquivo
- Faça upload do arquivo `mollie_webhook_intermediary.php` para seu site intermediário
- Certifique-se de que o PHP está habilitado no servidor

### 2. Configurações Obrigatórias
Edite as seguintes variáveis no arquivo:

```php
// Signing Secret do Mollie (obtido no painel Mollie)
$signingSecret = 'SEU_SIGNING_SECRET_AQUI';

// URL do webhook real da plataforma dei.bet
$targetWebhookUrl = 'https://sua-plataforma.com/api/gateways/mollie/webhook';
```

### 3. Configuração no Painel Mollie
- Acesse seu painel Mollie
- Vá em Configurações > Webhooks
- Configure a URL do webhook como: `https://seu-site-intermediario.com/mollie_webhook_intermediary.php`

## Recursos de Segurança

### ✅ Verificações Implementadas
- **Método HTTP**: Aceita apenas POST
- **Assinatura HMAC**: Verifica autenticidade usando signing secret
- **User-Agent**: Valida se a requisição vem do Mollie
- **Formato Payment ID**: Valida formato dos IDs (tr_, ord_, sub_)
- **JSON Validation**: Verifica integridade dos dados JSON
- **SSL/TLS**: Força verificação de certificados

### ✅ Logs e Monitoramento
- Logs detalhados para debug (configurável)
- Timestamps em todos os logs
- Separação entre logs de info e erro
- Log de todas as respostas da plataforma

## Tipos de Webhook Suportados

### Webhook Clássico
```
Content-Type: application/x-www-form-urlencoded
Body: id=tr_abc123
```

### Next-Gen Webhook
```json
{
  "type": "payment.paid",
  "entityId": "tr_abc123",
  "timestamp": "2023-01-01T12:00:00Z"
}
```

### Ping Webhook
```json
{
  "type": "hook.ping"
}
```

## Configurações Opcionais

### Debug Detalhado
```php
$enableDetailedLogs = true; // Ativar apenas para debug
```

### Timeout Personalizado
```php
$requestTimeout = 15; // Aumentar se necessário
```

## Monitoramento

### Logs do Servidor
Verifique os logs de erro do PHP para monitorar:
- Webhooks recebidos
- Erros de assinatura
- Falhas de conexão
- Respostas da plataforma

### Exemplo de Log
```
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Webhook recebido - Content-Type: application/json
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Verificando assinatura webhook...
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Assinatura verificada com sucesso
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Repassando webhook para: https://sua-plataforma.com/api/gateways/mollie/webhook
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Resposta da plataforma - HTTP: 200 | Body: OK
[INFO] [2023-01-01 12:00:00] Mollie Webhook Intermediary: Webhook processado com sucesso
```

## Troubleshooting

### Erro 401 (Unauthorized)
- Verifique se o `$signingSecret` está correto
- Confirme se o Mollie está enviando a assinatura

### Erro 403 (Forbidden)
- Verifique se o User-Agent contém "Mollie"
- Confirme se a requisição vem do Mollie

### Erro 500 (Internal Server Error)
- Verifique se a `$targetWebhookUrl` está correta e acessível
- Confirme se o servidor da plataforma está respondendo

### Timeout
- Aumente o `$requestTimeout` se necessário
- Verifique a conectividade entre servidores

## Segurança Adicional

### Recomendações
1. Use HTTPS em ambos os sites (intermediário e principal)
2. Configure firewall para aceitar apenas IPs do Mollie
3. Monitore logs regularmente
4. Mantenha o signing secret seguro
5. Considere rate limiting se necessário

### IPs do Mollie (para whitelist)
Consulte a documentação oficial do Mollie para os IPs atuais dos webhooks.
