/**
 * Sistema Dinâmico de Dificuldade para Jetpack - Integrado no jogo
 * Baseado no sistema do Pacman - chamado diretamente pelo game3.js
 */

// Configurações de dificuldade por nível - Corrigidas (menor spawn_rate = mais difícil)
const DIFFICULTY_CONFIGS = {
    easy: {
        player_speed: { min: 600, max: 1500 },
        missile_speed: { min: 800, max: 1600 },
        spawn_rate_obstacles: { min: 15, max: 20 }, // Valores maiores = mais fácil (menos spawns)
        rate_firing_weapon: { min: 0.05, max: 0.08 },
        rate_spawning_shell: { min: 0.10, max: 0.15 },
        chance_spawn_worker: { min: 30, max: 50 }
    },
    medium: {
        player_speed: { min: 600, max: 1900 },
        missile_speed: { min: 800, max: 1900 },
        spawn_rate_obstacles: { min: 10, max: 15 }, // Valores médios
        rate_firing_weapon: { min: 0.08, max: 0.12 },
        rate_spawning_shell: { min: 0.15, max: 0.20 },
        chance_spawn_worker: { min: 50, max: 70 }
    },
    hard: {
        player_speed: { min: 1500, max: 2100 },
        missile_speed: { min: 1600, max: 2200 },
        spawn_rate_obstacles: { min: 5, max: 10 }, // Valores menores = mais difícil (mais spawns)
        rate_firing_weapon: { min: 0.12, max: 0.25 },
        rate_spawning_shell: { min: 0.20, max: 0.35 },
        chance_spawn_worker: { min: 70, max: 95 }
    }
};

// Debug removido para produção

// Função para aplicar dificuldade ANTES do jogo iniciar
function initializeJetpackDifficulty() {
    try {
        const difficulty = window.jetpack_difficulty || 'medium';
        
        // Aplicar configurações iniciais baseadas na dificuldade
        const config = DIFFICULTY_CONFIGS[difficulty] || DIFFICULTY_CONFIGS.medium;
        
        // Aguardar até que a0zn esteja disponível
        const checkAndApply = () => {
            if (window.a0zn && window.a0zn.gameSetting) {
                // Aplicar valores iniciais da configuração
                const playerSpeed = config.player_speed.min;
                const missileSpeed = config.missile_speed.min;
                const spawnRate = config.spawn_rate_obstacles.min;
                const firingRate = config.rate_firing_weapon?.min || 0.08;
                const shellRate = config.rate_spawning_shell?.min || 0.15;
                const workerChance = config.chance_spawn_worker?.min || 50;
                
                // Forçar aplicação das configurações com validação
                window.a0zn.gameSetting.start_velocity = Math.max(playerSpeed, 200);
                window.a0zn.gameSetting.end_velocity = Math.max(playerSpeed * 2, 400);
                window.a0zn.gameSetting.velocity_missile_x = -Math.max(missileSpeed, 200);
                window.a0zn.gameSetting.spawn_rate_obstacles = Math.max(Math.min(spawnRate, 30), 5);
                window.a0zn.gameSetting.rate_firing_weapon_from_jetpack = Math.max(Math.min(firingRate, 0.5), 0.01);
                window.a0zn.gameSetting.rate_spawning_shell_from_jetpack = Math.max(Math.min(shellRate, 0.5), 0.05);
                window.a0zn.gameSetting.chance_spawn_worker = Math.max(Math.min(workerChance, 100), 10);
                
                // Também aplicar nas variáveis globais se existirem
                if (typeof player_speed !== 'undefined') window.player_speed = playerSpeed;
                if (typeof missile_speed !== 'undefined') window.missile_speed = missileSpeed;
                if (typeof spawn_rate_obstacles !== 'undefined') window.spawn_rate_obstacles = spawnRate;
                
                console.log('🚀 Dificuldade inicial FORÇADA:', {
                    difficulty: difficulty.toUpperCase(),
                    playerSpeed,
                    missileSpeed: -missileSpeed,
                    spawnRate,
                    applied: true
                });
                
                return true;
            }
            return false;
        };
        
        // Tentar aplicar imediatamente
        if (!checkAndApply()) {
            // Se não conseguir, tentar novamente em intervalos
            let attempts = 0;
            const interval = setInterval(() => {
                attempts++;
                if (checkAndApply() || attempts > 10) {
                    clearInterval(interval);
                }
            }, 500);
        }
        
    } catch (error) {
        console.error('❌ Erro ao inicializar dificuldade:', error);
    }
}

// Função principal chamada pelo jogo quando coleta moedas
function applyJetpackDynamicDifficulty(currentGain, metaTarget) {
    try {
        const difficulty = window.jetpack_difficulty || 'medium';
        const isDemoAgent = window.is_demo_agent || false;
        const metaPercentage = metaTarget > 0 ? (currentGain / metaTarget) * 100 : 0;
        
        // Debug visual removido para produção
        
        // Calcula novos valores dinâmicos para todas as variáveis
        const newPlayerSpeed = getDynamicSpeed(difficulty, 'player_speed', metaPercentage, isDemoAgent);
        const newMissileSpeed = getDynamicSpeed(difficulty, 'missile_speed', metaPercentage, isDemoAgent);
        const newSpawnRate = getDynamicSpeed(difficulty, 'spawn_rate_obstacles', metaPercentage, isDemoAgent);
        const newFiringRate = getDynamicSpeed(difficulty, 'rate_firing_weapon', metaPercentage, isDemoAgent);
        const newShellRate = getDynamicSpeed(difficulty, 'rate_spawning_shell', metaPercentage, isDemoAgent);
        const newWorkerChance = getDynamicSpeed(difficulty, 'chance_spawn_worker', metaPercentage, isDemoAgent);
        
        // Aplica nas configurações do jogo com validação baseada no jogo original
        if (window.a0zn && window.a0zn.gameSetting) {
            // Validar valores baseados nos limites do jogo original
            const validPlayerSpeed = Math.max(Math.min(newPlayerSpeed, 2000), 200);
            const validMissileSpeed = Math.max(Math.min(newMissileSpeed, 2500), 200);
            const validSpawnRate = Math.max(Math.min(newSpawnRate, 30), 5);
            const validFiringRate = Math.max(Math.min(newFiringRate, 0.5), 0.01);
            const validShellRate = Math.max(Math.min(newShellRate, 0.5), 0.05);
            const validWorkerChance = Math.max(Math.min(newWorkerChance, 100), 10);
            
            window.a0zn.gameSetting.start_velocity = validPlayerSpeed;
            window.a0zn.gameSetting.end_velocity = validPlayerSpeed * 2;
            window.a0zn.gameSetting.velocity_missile_x = -validMissileSpeed;
            window.a0zn.gameSetting.spawn_rate_obstacles = validSpawnRate;
            window.a0zn.gameSetting.rate_firing_weapon_from_jetpack = validFiringRate;
            window.a0zn.gameSetting.rate_spawning_shell_from_jetpack = validShellRate;
            window.a0zn.gameSetting.chance_spawn_worker = validWorkerChance;
            
            // Debug para verificar valores aplicados
            console.log('🔧 Valores aplicados (seguros):', {
                playerSpeed: validPlayerSpeed,
                playerSpeedMax: validPlayerSpeed * 2,
                missileSpeed: -validMissileSpeed,
                spawnRate: validSpawnRate,
                originalValues: { newPlayerSpeed, newMissileSpeed, newSpawnRate }
            });
        }
        
        // Over Meta detection
        const isOverMeta = metaPercentage >= 105 && !isDemoAgent;
        
        // Display visual removido para produção
        
    } catch (error) {
        console.error('❌ Erro no sistema dinâmico:', error);
    }
}

/**
 * Calcula a velocidade dinâmica baseada na progressão da meta
 */
function getDynamicSpeed(difficulty, paramType, metaPercentage, isDemoAgent = false) {
    const config = DIFFICULTY_CONFIGS[difficulty] || DIFFICULTY_CONFIGS.medium;
    const range = config[paramType];
    
    if (!range) return range?.min || 1000;
    
    // Over Meta: 105% = punição MÁXIMA (exceto demo agents)
    if (metaPercentage >= 105 && !isDemoAgent) {
        // OVER META: Todas as variáveis no máximo para punir
        if (paramType === 'player_speed') {
            return 2000; // Velocidade EXTREMA do jogador no Over Meta
        } else if (paramType === 'missile_speed') {
            return 2500; // Velocidade EXTREMA dos mísseis
        } else if (paramType === 'spawn_rate_obstacles') {
            return 3; // Taxa EXTREMA de spawn (menor = mais difícil) - corrigido
        } else if (paramType === 'rate_firing_weapon') {
            return 0.4; // Taxa EXTREMA de tiro
        } else if (paramType === 'rate_spawning_shell') {
            return 0.45; // Taxa EXTREMA de projéteis
        } else if (paramType === 'chance_spawn_worker') {
            return 98; // Chance EXTREMA de inimigos
        }
    }
    
    // Progressão normal: 0% = min, 100% = max
    const normalizedProgress = Math.min(metaPercentage / 100, 1);
    let calculatedValue;
    
    // Para valores decimais (rates), usar precisão decimal
    if (paramType.includes('rate_')) {
        calculatedValue = range.min + (range.max - range.min) * normalizedProgress;
        calculatedValue = Math.round(calculatedValue * 1000) / 1000; // 3 casas decimais
    } else {
        calculatedValue = Math.round(range.min + (range.max - range.min) * normalizedProgress);
    }
    
    console.log(`📊 ${paramType}: ${calculatedValue} (${range.min}-${range.max}) | Progresso: ${(normalizedProgress * 100).toFixed(1)}%`);
    
    return calculatedValue;
}

// Sistema agora é chamado diretamente pelo game3.js quando coleta moedas
console.log('✅ Sistema Dinâmico de Dificuldade do Jetpack carregado!');
