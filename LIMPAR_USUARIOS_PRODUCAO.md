# Como Limpar Usuários em Produção (Mantendo Admin)

## ⚠️ AVISO IMPORTANTE

Este processo irá **APAGAR PERMANENTEMENTE** todos os usuários do banco de dados, exceto o administrador. Esta ação é **IRREVERSÍVEL**.

## 📋 Passos Obrigatórios

### 1. Fazer Backup Completo

```bash
# Execute o script de backup
./backup_before_clear_users.sh

# Ou manualmente:
mysqldump -u [usuario] -p [nome_do_banco] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Verificar Admin

```bash
# Verifique quem é o admin no banco
php artisan tinker
>>> User::where('is_admin', true)->get(['id', 'email', 'name']);
```

### 3. Executar Limpeza

```bash
# Modo interativo (recomendado)
php artisan users:clear

# Modo automático (sem confirmação)
php artisan users:clear --force
```

## 🔧 O que o comando faz?

1. **Encontra o admin** pelo campo `is_admin = true`
2. **Remove todos os outros usuários** usando o método `delete()` do modelo
3. **Limpa automaticamente** todas as relações:
   - Wallet, AffiliateHistory, AffiliateWithdraw
   - Deposits, Withdrawals, Transactions
   - Games favoritos, likes, reviews
   - Missions, Orders, Documents
   - E todas as outras tabelas relacionadas

## 🛡️ Segurança

- **Transação ACID**: Todo o processo é envolvido em transação
- **Rollback automático**: Se ocorrer erro, nada é alterado
- **Log de auditoria**: Ação é registrada nos logs
- **Confirmação obrigatória**: Requer confirmação explícita

## 📝 Após a Execução

1. **Verifique se funcionou**:
   ```bash
   php artisan tinker
   >>> User::count();  // Deve retornar 1 (apenas admin)
   ```

2. **Verifique logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep "Users cleared"
   ```

3. **Mantenha o backup** em local seguro por pelo menos 30 dias

## 🚨 Em Caso de Problemas

Se algo der errado, restaure o backup:

```bash
gunzip backup_arquivo.sql.gz
mysql -u [usuario] -p [nome_do_banco] < backup_arquivo.sql
```

## 📞 Suporte

Se encontrar problemas:
1. Verifique os logs em `storage/logs/laravel.log`
2. Confirme se o admin existe antes de executar
3. Tenha certeza de que fez o backup

## ⚡ Performance

O comando processa usuários em lotes de 100 para evitar:
- Sobrecarga de memória
- Timeout de execução
- Bloqueios prolongados no banco
