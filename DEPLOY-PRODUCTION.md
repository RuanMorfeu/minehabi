# 🚀 Deploy em Produção - dei.bet

## Problemas Resolvidos

### ✅ Vulnerabilidades de Segurança Corrigidas
- **CVE-2025-54068**: Livewire RCE vulnerability (CRÍTICA) ✅
- **CVE-2025-46734**: league/commonmark XSS vulnerability (MÉDIA) ✅  
- **CVE-2025-27515**: Laravel File Validation Bypass (MÉDIA) ✅

### ✅ Sistema de Email Otimizado
- Dependências atualizadas (symfony/postmark-mailer v7.3.0)
- Configurações corrigidas (timeout, fallback)
- Sistema de fallback robusto: postmark → smtp → log
- Memory limit otimizado (512MB em produção)

## 📋 Pré-requisitos

### Servidor
- PHP 8.1+ com extensões: curl, json, mbstring, openssl, pdo, tokenizer, xml
- MySQL 8.0+ ou MariaDB 10.3+
- Redis (recomendado para cache e sessões)
- Nginx ou Apache com SSL/HTTPS
- Composer 2.x
- Node.js 18+ e NPM (para assets)

### Configurações PHP Recomendadas
```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
```

## 🔧 Processo de Deploy

### 1. Preparação dos Arquivos
```bash
# No servidor de desenvolvimento
npm run build
composer install --optimize-autoloader --no-dev
```

### 2. Upload para o Servidor
Faça upload de todos os arquivos exceto:
- `.env` (será criado no servidor)
- `node_modules/`
- `storage/logs/*`
- `storage/framework/cache/*`

### 3. Configuração no Servidor
```bash
# 1. Copiar configuração de produção
cp .env.production.example .env

# 2. Editar .env com configurações reais
nano .env

# 3. Executar script de deploy
./deploy-production.sh
```

## 📝 Configurações Críticas no .env

### Aplicação
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dei.bet
```

### Banco de Dados
```env
DB_HOST=seu_host_mysql
DB_DATABASE=deibet_production
DB_USERNAME=deibet_user
DB_PASSWORD=senha_super_segura
```

### Email (Configuração Otimizada)
```env
MAIL_MAILER=failover
POSTMARK_TOKEN=seu_token_postmark
MAIL_FROM_ADDRESS=support@dei.bet
```

### Cache e Sessões
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔒 Configurações de Segurança

### Nginx (Recomendado)
```nginx
server {
    listen 443 ssl http2;
    server_name dei.bet www.dei.bet;
    root /var/www/dei.bet/public;
    
    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Laravel Configuration
    index index.php;
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Permissões de Arquivos
```bash
# Propriedade dos arquivos
chown -R www-data:www-data /var/www/dei.bet

# Permissões
find /var/www/dei.bet -type f -exec chmod 644 {} \;
find /var/www/dei.bet -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/dei.bet/storage
chmod -R 775 /var/www/dei.bet/bootstrap/cache
```

## 🔍 Comandos de Diagnóstico

### Verificar Saúde do Sistema
```bash
php artisan system:health-check
```

### Testar Sistema de Email
```bash
php artisan email:test admin@dei.bet
```

### Verificar Configurações
```bash
php artisan config:show mail
php artisan route:list
```

## 📊 Monitoramento

### Logs Importantes
- `storage/logs/laravel.log` - Logs gerais da aplicação
- `/var/log/nginx/error.log` - Logs do Nginx
- `/var/log/php8.2-fpm.log` - Logs do PHP-FPM

### Comandos de Manutenção
```bash
# Limpeza de logs antigos (executar diariamente)
find storage/logs -name "*.log" -mtime +7 -delete

# Otimização de cache (executar após mudanças)
php artisan optimize

# Backup do banco de dados
mysqldump -u user -p deibet_production > backup_$(date +%Y%m%d).sql
```

## 🚨 Troubleshooting

### Problema: Email não funciona
```bash
# Testar diferentes mailers
php artisan email:test admin@dei.bet --mailer=postmark
php artisan email:test admin@dei.bet --mailer=smtp
php artisan email:test admin@dei.bet --mailer=log
```

### Problema: Erro 500
```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Limpar caches
php artisan optimize:clear
```

### Problema: Sessões não funcionam
```bash
# Verificar Redis
redis-cli ping

# Regenerar chave da aplicação
php artisan key:generate --force
```

## 📞 Suporte

Em caso de problemas:
1. Verificar logs em `storage/logs/laravel.log`
2. Executar `php artisan system:health-check`
3. Verificar configurações do servidor web
4. Contatar suporte técnico com logs específicos

## 🔄 Atualizações Futuras

Para atualizações:
1. Fazer backup completo (arquivos + banco)
2. Testar em ambiente de staging
3. Executar `./deploy-production.sh`
4. Verificar funcionalidades críticas
5. Monitorar logs por 24h
