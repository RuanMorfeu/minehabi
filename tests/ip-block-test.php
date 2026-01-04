<?php

// Este script testa o bloqueio de IP sem bloquear seu próprio IP

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\BlockedIp;

// Inicializar o aplicativo Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// IP de teste (um IP que não é o seu)
$testIp = '8.8.8.8'; // Google DNS, apenas para teste

echo "Iniciando teste de bloqueio de IP...\n";

// Verificar se o IP já está bloqueado
if (BlockedIp::isBlocked($testIp)) {
    echo "O IP $testIp já está bloqueado. Removendo bloqueio para teste...\n";

    // Desativar bloqueios existentes para este IP
    BlockedIp::where('ip', $testIp)->update(['active' => false]);

    echo "Bloqueio removido.\n";
}

// Criar um novo bloqueio
echo "Bloqueando o IP $testIp...\n";
BlockedIp::create([
    'ip' => $testIp,
    'reason' => 'Teste de bloqueio',
    'blocked_by' => 'Script de teste',
    'blocked_at' => now(),
    'active' => true,
]);

// Verificar se o bloqueio foi criado
if (BlockedIp::isBlocked($testIp)) {
    echo "✅ SUCESSO: O IP $testIp foi bloqueado com sucesso!\n";
} else {
    echo "❌ ERRO: Falha ao bloquear o IP $testIp.\n";
    exit(1);
}

// Simular uma requisição com o IP bloqueado
echo "\nSimulando uma requisição com o IP bloqueado...\n";

// Criar uma requisição falsa com o IP bloqueado
$request = new Illuminate\Http\Request;
$request->server->set('REMOTE_ADDR', $testIp);

// Criar uma instância do middleware
$middleware = new App\Http\Middleware\CheckBlockedIp;

// Tentar processar a requisição
$response = $middleware->handle($request, function ($req) {
    return new Illuminate\Http\Response('Acesso permitido');
});

// Verificar a resposta
if ($response->getStatusCode() === 403) {
    echo "✅ SUCESSO: O middleware bloqueou corretamente o acesso do IP $testIp (Código 403).\n";
} else {
    echo "❌ ERRO: O middleware NÃO bloqueou o acesso do IP $testIp. Código de status: ".$response->getStatusCode()."\n";
}

// Limpar - remover o bloqueio de teste
echo "\nLimpando: Removendo o bloqueio de teste...\n";
BlockedIp::where('ip', $testIp)->update(['active' => false]);

echo "\nTeste concluído!\n";
