#!/bin/bash

# Script de backup antes de limpar usuários
# Uso: ./backup_before_clear_users.sh

echo "🔒 Criando backup do banco antes de limpar usuários..."

# Data e hora para o nome do arquivo
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="backup_before_clear_users_${TIMESTAMP}.sql"

# Verificar se há variáveis de ambiente ou usar padrão
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

# Criar backup
if [ -n "$DB_PASSWORD" ]; then
    mysqldump -h ${DB_HOST} -P ${DB_PORT} -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} > ${BACKUP_FILE}
else
    mysqldump -h ${DB_HOST} -P ${DB_PORT} -u ${DB_USERNAME} ${DB_DATABASE} > ${BACKUP_FILE}
fi

if [ $? -eq 0 ]; then
    echo "✅ Backup criado com sucesso: ${BACKUP_FILE}"
    echo "📦 Compactando backup..."
    gzip ${BACKUP_FILE}
    echo "✅ Backup compactado: ${BACKUP_FILE}.gz"
    echo ""
    echo "⚠️  Mantenha este arquivo em local seguro!"
    echo ""
    echo "Para restaurar se necessário:"
    echo "gunzip ${BACKUP_FILE}.gz"
    echo "mysql -h ${DB_HOST} -P ${DB_PORT} -u ${DB_USERNAME} -p ${DB_DATABASE} < ${BACKUP_FILE}"
else
    echo "❌ Erro ao criar backup!"
    exit 1
fi
