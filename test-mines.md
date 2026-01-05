# Teste do Jogo Mines

## Passos para testar:

1. Acesse o sistema e faça login
2. Na página inicial, clique no card "Mines" na seção de Jogos
3. O jogo deve abrir em uma nova página
4. Verifique se:
   - O saldo do usuário aparece corretamente
   - É possível selecionar a quantidade de minas
   - É possível ajustar o valor da aposta
   - O botão "Bet" inicia o jogo
   - Clicar nas células revela estrelas ou minas
   - O botão "Cash Out" aparece e funciona
   - O saldo é atualizado corretamente após ganhar ou perder

## Verificações técnicas:

- [ ] As chamadas à API estão autenticadas corretamente
- [ ] O saldo é debitado ao iniciar o jogo
- [ ] O saldo é creditado ao fazer cashout
- [ ] As transações são registradas no banco
- [ ] Os sons são reproduzidos corretamente
- [ ] As animações funcionam bem

## Possíveis problemas:

- Se o saldo não aparecer, verificar se o usuário tem um wallet criado
- Se a API retornar 401, verificar se o token está sendo enviado corretamente
- Se os sons não tocarem, verificar se os arquivos de áudio foram copiados para a pasta correta
