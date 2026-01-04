# Integração Mollie Embedded Checkout

## Visão Geral

Esta documentação descreve a implementação completa do **Mollie Embedded Checkout** no projeto dei.bet, que permite aos usuários realizar pagamentos com cartão de crédito diretamente no site, sem redirecionamento para páginas externas.

## Funcionalidades Implementadas

### ✅ Backend (Laravel)

1. **MollieController** - Endpoints da API:
   - `POST /api/mollie/create-payment` - Pagamentos com redirecionamento
   - `POST /api/mollie/create-payment-token` - Pagamentos embebidos com cardToken
   - `GET /api/mollie/config` - Configuração do Mollie (Profile ID)
   - `GET /api/mollie/payment-methods` - Métodos disponíveis
   - `GET /api/mollie/check-status` - Status do pagamento
   - `POST /api/mollie/mollie/webhook` - Webhook público
   - `GET /api/mollie/mollie/return/{paymentId}` - Retorno público

2. **MollieTrait** - Lógica de pagamento:
   - `createMolliePayment()` - Pagamentos tradicionais
   - `createMolliePaymentWithToken()` - Pagamentos embebidos
   - `generateCredentials()` - Credenciais com Profile ID
   - `finalizePayment()` - Finalização via webhook

3. **Database** - Campos adicionados:
   - `mollie_api_key` - Chave da API
   - `mollie_profile_id` - Profile ID para Components
   - `mollie_active` - Status ativo/inativo

4. **Admin Panel** - Configuração:
   - Campo para API Key do Mollie
   - Campo para Profile ID do Mollie
   - Toggle de ativação

### ✅ Frontend (Vue.js)

1. **MollieCardForm.vue** - Componente de checkout embebido:
   - Integração com Mollie.js
   - Formulário de cartão embebido
   - Geração de cardToken
   - Processamento de pagamentos
   - Suporte a 3D Secure

2. **CashIn.vue** - Modal de depósito modificado:
   - Detecção de método Mollie creditcard
   - Exibição do checkout embebido
   - Integração com sistema de bônus
   - Fallback para redirecionamento

## Fluxo de Pagamento Embebido

### 1. Seleção de Método
```javascript
// Usuário seleciona "mollie-creditcard"
if (form.deposit_method_slug === 'mollie-creditcard') {
    showMollieEmbedded.value = true;
    // Mostra o componente MollieCardForm
}
```

### 2. Inicialização do Mollie Components
```javascript
// Carrega Mollie.js e inicializa
this.mollie = window.Mollie(this.profileId, {
    locale: 'pt_PT',
    testmode: true // false em produção
});

// Cria componente de cartão
this.cardComponent = this.mollie.createComponent('card', {
    styles: { /* estilos personalizados */ }
});
```

### 3. Geração do Token
```javascript
// Usuário preenche dados e clica em pagar
const { token, error } = await this.mollie.createToken();
if (token) {
    // Envia token para backend
    const response = await HttpApi.post('/api/mollie/create-payment-token', {
        amount: this.amount,
        cardToken: token
    });
}
```

### 4. Processamento Backend
```php
// MollieTrait::createMolliePaymentWithToken()
$paymentData = [
    'amount' => ['currency' => 'EUR', 'value' => $amount],
    'description' => 'Depósito - ' . config('app.name'),
    'method' => 'creditcard',
    'cardToken' => $request->cardToken,
    'redirectUrl' => url('/mollie/return/' . $transactionId)
];

$payment = $mollie->payments->create($paymentData);
```

### 5. Resposta e 3D Secure
```javascript
if (response.data.requires_3ds && response.data.checkout_url) {
    // Redireciona apenas para 3D Secure
    window.open(response.data.checkout_url, '_blank');
} else {
    // Pagamento processado com sucesso
    this.$emit('payment-success', response.data);
}
```

## Configuração

### 1. Mollie Dashboard
1. Acesse [Mollie Dashboard](https://my.mollie.com/)
2. Obtenha a **API Key** (test_ ou live_)
3. Obtenha o **Profile ID** (pfl_xxxxxxxxxxxxxxxx)
4. Configure webhook URL: `https://seudominio.com/api/mollie/mollie/webhook`

### 2. Admin Panel
1. Acesse `/admin/gateway-page`
2. Seção "Mollie":
   - **API Key**: Cole a chave da API
   - **Profile ID**: Cole o Profile ID
   - Salve as configurações

### 3. Ambiente de Desenvolvimento
```bash
# Instalar dependências se necessário
composer require mollie/mollie-api-php

# Executar migrações
php artisan migrate

# Limpar cache
php artisan config:clear
php artisan route:clear
```

## Métodos de Pagamento Suportados

### Checkout Embebido
- ✅ **Cartão de Crédito** (`mollie-creditcard`) - Embebido com Mollie Components

### Checkout com Redirecionamento
- ✅ **Apple Pay** (`mollie-applepay`)
- ✅ **Google Pay** (`mollie-googlepay`)
- ✅ **MB WAY** (`mollie-mbway`)
- ✅ **Multibanco** (`mollie-multibanco`)
- ✅ **Pay by Bank** (`mollie-paybybank`)
- ✅ **Transferência Bancária** (`mollie-banktransfer`)

## Segurança e Compliance

### PCI-DSS
- ✅ Dados do cartão nunca passam pelo servidor
- ✅ Mollie Components são PCI-DSS compliant
- ✅ Tokens são gerados no frontend de forma segura

### 3D Secure
- ✅ Suporte automático a 3D Secure 2.0
- ✅ Redirecionamento apenas quando necessário
- ✅ Retorno automático após autenticação

## Logs e Debug

### Backend
```php
// Logs detalhados em storage/logs/laravel.log
[MOLLIE-DEBUG] Iniciando createMolliePaymentWithToken
[MOLLIE-DEBUG] Credenciais obtidas, inicializando cliente Mollie
[MOLLIE-DEBUG] Dados do pagamento com cardToken preparados
[MOLLIE-DEBUG] Pagamento com cardToken criado
```

### Frontend
```javascript
// Console do navegador
[MOLLIE-EMBEDDED] Pagamento processado com sucesso
[MOLLIE-EMBEDDED] Pagamento iniciado, requer 3DS
[MOLLIE-EMBEDDED] Erro no pagamento
```

## Testes

### Cartões de Teste (Mollie)
```
Aprovado: 4242 4242 4242 4242
3D Secure: 4000 0000 0000 3220
Recusado: 4000 0000 0000 0002
```

### URLs de Teste
```
API: https://api.mollie.com/v2/
Webhook: https://seudominio.com/api/mollie/mollie/webhook
Return: https://seudominio.com/mollie/return/{paymentId}
```

## Troubleshooting

### Erro: "Profile ID não encontrado"
- Verifique se o Profile ID está correto no admin panel
- Confirme se o Profile ID pertence à conta da API Key

### Erro: "Mollie.js não carregado"
- Verifique conexão com internet
- Confirme se o script está sendo carregado corretamente

### Erro: "Token inválido"
- Verifique se os dados do cartão estão válidos
- Confirme se o componente foi inicializado corretamente

### Pagamento não finalizado
- Verifique logs do webhook
- Confirme se a URL do webhook está acessível publicamente
- Verifique se o status do pagamento está sendo atualizado

## Próximos Passos

1. **Produção**: Alterar `testmode: false` no MollieCardForm.vue
2. **Webhook**: Configurar URL pública HTTPS para webhooks
3. **Monitoramento**: Implementar alertas para falhas de pagamento
4. **Analytics**: Adicionar tracking de conversão de pagamentos
5. **UX**: Melhorar feedback visual durante processamento

## Suporte

Para questões técnicas sobre a integração Mollie:
- [Documentação Mollie](https://docs.mollie.com/)
- [Mollie Components](https://docs.mollie.com/components/overview)
- [Suporte Mollie](https://help.mollie.com/)

---

**Status**: ✅ Implementação completa e funcional
**Última atualização**: 20/08/2025
