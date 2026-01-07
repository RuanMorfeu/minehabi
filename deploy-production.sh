#!/bin/bash

# Script de Deploy e Otimização para Produção - dei.bet
# Este script deve ser executado no servidor de produção após fazer upload dos arquivos

echo "🚀 Iniciando deploy e otimização para produção..."

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: arquivo artisan não encontrado. Execute este script no diretório raiz do Laravel."
    exit 1
fi

echo "📦 Instalando/atualizando dependências do Composer..."
composer install --optimize-autoloader --no-dev --quiet

echo "🔧 Limpando caches antigos..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 Executando migrações (se necessário)..."
php artisan migrate --force

echo "🔗 Criando link simbólico do storage..."
php artisan storage:link

echo "🧹 Limpando logs antigos (mantendo últimos 7 dias)..."
find storage/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true

echo "🔍 Verificando saúde do sistema..."
php artisan system:health-check

echo "📧 Testando sistema de email..."
php artisan email:test admin@dei.bet --mailer=log

echo "🔒 Configurando permissões de arquivos..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "📊 Verificando configurações críticas..."

# Verificar se APP_ENV está como production
if grep -q "APP_ENV=local" .env 2>/dev/null; then
    echo "⚠️  AVISO: APP_ENV ainda está como 'local'. Altere para 'production'!"
fi

# Verificar se APP_DEBUG está false
if grep -q "APP_DEBUG=true" .env 2>/dev/null; then
    echo "⚠️  AVISO: APP_DEBUG está como 'true'. Altere para 'false' em produção!"
fi

# Verificar se HTTPS está configurado
if grep -q "APP_URL=http://" .env 2>/dev/null; then
    echo "⚠️  AVISO: APP_URL está usando HTTP. Configure HTTPS para produção!"
fi

echo "🎯 Otimizações específicas para dei.bet..."

# Verificar se as tabelas críticas existem
echo "📋 Verificando estrutura do banco de dados..."
php artisan tinker --execute="
try {
    \DB::table('users')->count();
    echo 'Tabela users: OK\n';
} catch (Exception \$e) {
    echo 'Erro na tabela users: ' . \$e->getMessage() . '\n';
}

try {
    \DB::table('transactions')->count();
    echo 'Tabela transactions: OK\n';
} catch (Exception \$e) {
    echo 'Erro na tabela transactions: ' . \$e->getMessage() . '\n';
}

try {
    \DB::table('gateways')->count();
    echo 'Tabela gateways: OK\n';
} catch (Exception \$e) {
    echo 'Erro na tabela gateways: ' . \$e->getMessage() . '\n';
}
"

echo "🔐 Verificando configurações de segurança..."

# Verificar se JWT_SECRET está configurado
if ! grep -q "JWT_SECRET=" .env 2>/dev/null; then
    echo "❌ CRÍTICO: JWT_SECRET não está configurado!"
fi

# Verificar se APP_KEY está configurado
if ! grep -q "APP_KEY=" .env 2>/dev/null; then
    echo "❌ CRÍTICO: APP_KEY não está configurado!"
fi

echo "📈 Estatísticas finais:"
echo "PHP Version: $(php -v | head -n1)"
echo "Laravel Version: $(php artisan --version)"
echo "Composer Version: $(composer --version)"
echo "Memory Limit: $(php -r 'echo ini_get("memory_limit");')"
echo "Max Execution Time: $(php -r 'echo ini_get("max_execution_time");')"

echo ""
# Permissões da pasta do bot
echo "🔧 Ajustando permissões do bot..."
chown -R www-data:www-data bots/mines
chmod -R 775 bots/mines
find bots/mines -type f -name "*.log" -exec chmod 664 {} \;

echo "✅ Deploy finalizado com sucesso!"
echo ""
echo "📋 Próximos passos recomendados:"
echo "1. Verificar se o servidor web (Nginx/Apache) está configurado corretamente"
echo "2. Configurar SSL/HTTPS se ainda não estiver ativo"
echo "3. Configurar backup automático do banco de dados"
echo "4. Configurar monitoramento de logs"
echo "5. Testar todas as funcionalidades críticas"
echo ""
echo "🔧 Comandos úteis para manutenção:"
echo "- php artisan system:health-check (verificar saúde do sistema)"
echo "- php artisan email:test {email} (testar envio de emails)"
echo "- php artisan queue:work (processar filas, se necessário)"
echo "- php artisan schedule:run (executar tarefas agendadas)"
echo ""
