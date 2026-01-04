# Sistema de Dificuldade Dinâmica - Jetpack

## Visão Geral
O sistema de dificuldade dinâmica do Jetpack ajusta automaticamente os parâmetros do jogo baseado no progresso do jogador, criando uma experiência mais equilibrada e desafiadora.

## Arquitetura

### Backend (VGames2Controller.php)
- Envia `jetpack_difficulty` (easy/medium/hard) e `is_demo_agent` para o frontend
- Não mapeia valores específicos - deixa o sistema dinâmico decidir
- Suporta configurações específicas para influencers e demo agents

### Frontend (index.php)
- Recebe e configura variáveis globais `window.jetpack_difficulty` e `window.is_demo_agent`
- Inicializa o sistema de dificuldade quando a página carrega

### Sistema Dinâmico (dynamic-difficulty.js)
- Aplica configurações iniciais baseadas na dificuldade
- Ajusta parâmetros dinamicamente durante o jogo
- Monitora progresso da meta e aplica penalidades para Over Meta

## Configurações de Dificuldade

### Easy
- **Player Speed**: 600-800
- **Missile Speed**: 800-1000
- **Spawn Rate**: 20-25 (menor = mais difícil)
- **Firing Rate**: 0.05-0.08
- **Shell Rate**: 0.10-0.15
- **Worker Chance**: 30-50%

### Medium
- **Player Speed**: 800-1200
- **Missile Speed**: 1200-1600
- **Spawn Rate**: 15-20
- **Firing Rate**: 0.08-0.12
- **Shell Rate**: 0.15-0.20
- **Worker Chance**: 50-70%

### Hard
- **Player Speed**: 1000-1600
- **Missile Speed**: 1600-2200
- **Spawn Rate**: 10-15
- **Firing Rate**: 0.12-0.25
- **Shell Rate**: 0.20-0.35
- **Worker Chance**: 70-95%

## Funcionamento

### Inicialização
1. `initializeJetpackDifficulty()` é chamada no carregamento da página
2. Aplica valores mínimos da configuração de dificuldade
3. Aguarda disponibilidade do `window.a0zn.gameSetting`
4. Força aplicação das configurações iniciais

### Ajuste Dinâmico
1. `applyJetpackDynamicDifficulty()` é chamada pelo game3.js quando coleta moedas
2. Calcula progresso da meta (currentGain / metaTarget * 100)
3. Ajusta parâmetros baseado no progresso:
   - 0% = valores mínimos
   - 100% = valores máximos
   - 105%+ = Over Meta (penalidade máxima)

### Over Meta (105%+)
Quando o jogador excede 105% da meta (exceto demo agents):
- **Player Speed**: 2000 (extremo)
- **Missile Speed**: 2500 (extremo)
- **Spawn Rate**: 5 (máxima dificuldade)
- **Firing Rate**: 0.4 (tiro intenso)
- **Shell Rate**: 0.45 (projéteis intensos)
- **Worker Chance**: 98% (inimigos constantes)

## Variáveis do Jogo Afetadas

### Velocidades
- `start_velocity`: Velocidade inicial do jogador
- `end_velocity`: Velocidade máxima do jogador (2x start_velocity)
- `velocity_missile_x`: Velocidade dos mísseis (negativa)

### Spawn e Hostilidade
- `spawn_rate_obstacles`: Taxa de spawn de obstáculos
- `rate_firing_weapon_from_jetpack`: Taxa de tiro dos inimigos
- `rate_spawning_shell_from_jetpack`: Taxa de spawn de projéteis
- `chance_spawn_worker`: Chance de spawn de workers inimigos

## Sistema de Debug
- Display visual no canto superior direito durante o jogo
- Mostra dificuldade atual, progresso da meta e valores aplicados
- Indica status Over Meta e Demo Agent
- Console logs detalhados para debugging

## Validação e Segurança
- Todos os valores são validados dentro de limites seguros
- Player Speed: 200-2000
- Missile Speed: 200-2500
- Spawn Rate: 5-30
- Firing Rate: 0.01-0.5
- Shell Rate: 0.05-0.5
- Worker Chance: 10-100%

## Demo Agents
- Não sofrem penalidades Over Meta
- Mantêm progressão normal mesmo acima de 105%
- Identificados pela flag `is_demo_agent`

## Integração com o Jogo
- Sistema é chamado automaticamente pelo `game3.js` na linha 14712-14713
- Integração transparente sem modificar lógica principal do jogo
- Compatível com sistema existente de moedas e metas
