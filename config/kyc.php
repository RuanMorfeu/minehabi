<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KYC Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de verificação KYC
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Reenvio de Documentos
    |--------------------------------------------------------------------------
    |
    | Controla se usuários podem reenviar documentos após rejeição
    | true = permite reenvio | false = bloqueia reenvio
    |
    */
    'allow_resubmission_after_rejection' => true,

    /*
    |--------------------------------------------------------------------------
    | Limite de Tentativas
    |--------------------------------------------------------------------------
    |
    | Número máximo de tentativas de envio de documentos
    | 0 = ilimitado | 1 = apenas uma tentativa | 3 = três tentativas
    |
    */
    'max_submission_attempts' => 3,

    /*
    |--------------------------------------------------------------------------
    | Tempo de Espera (em horas)
    |--------------------------------------------------------------------------
    |
    | Tempo que o usuário deve esperar antes de reenviar após rejeição
    | 0 = sem cooldown | 24 = 24 horas | 48 = 48 horas
    |
    */
    'resubmission_cooldown_hours' => 0,

    /*
    |--------------------------------------------------------------------------
    | Bloqueio Automático de Jogos de Habilidade
    |--------------------------------------------------------------------------
    |
    | Configurações para bloqueio automático após vitórias em jogos de habilidade
    |
    */

    /*
    | Ativar bloqueio automático após vitórias
    | true = ativa bloqueio automático | false = desativa
    */
    'auto_block_skill_games_on_win' => true,

    /*
    | Valor mínimo de vitória para ativar bloqueio (em moeda base)
    | 0 = qualquer vitória | 100 = apenas vitórias acima de 100
    */
    'auto_block_min_win_amount' => 0,

    /*
    | Número de vitórias consecutivas antes de bloquear
    | 1 = bloqueia na primeira vitória | 3 = bloqueia após 3 vitórias seguidas
    */
    'auto_block_consecutive_wins' => 1,

    /*
    |--------------------------------------------------------------------------
    | Aumento Automático do Valor Mínimo (Alternativa ao Bloqueio)
    |--------------------------------------------------------------------------
    |
    | Em vez de bloquear, pode aumentar o valor mínimo para jogos de habilidade
    |
    */

    /*
    | Usar aumento de valor mínimo em vez de bloqueio
    | true = aumenta valor mínimo | false = bloqueia completamente
    */
    'auto_increase_min_amount_instead_of_block' => false,

    /*
    | Valor inicial mínimo quando não definido pelo usuário
    | Usado como base para cálculos de aumento
    */
    'default_skill_games_min_amount' => 2.00,

    /*
    | Multiplicador para aumento do valor mínimo
    | 2.0 = dobra o valor | 1.5 = aumenta 50% | 3.0 = triplica
    */
    'min_amount_increase_multiplier' => 3.00,

    /*
    | Valor máximo que o valor mínimo pode atingir
    | 0 = sem limite | 1000 = máximo de 1000
    */
    'max_skill_games_min_amount' => 6.00,

    /*
    | Influencers são isentos das regras de bloqueio automático
    | true = influencers podem jogar livremente | false = aplicar regras normais
    */
    'exempt_influencers_from_auto_block' => true,

    /*
    |--------------------------------------------------------------------------
    | Configurações de Dificuldade dos Jogos de Habilidade
    |--------------------------------------------------------------------------
    |
    | Opções de dificuldade gerais aplicáveis a todos os jogos de habilidade
    | (Pacman, Subway Surfers, Jetpack, etc.)
    |
    */

    /*
    | Opções de dificuldade para jogos de habilidade
    | Usado por: Pacman, Subway Surfers, Jetpack e outros jogos de habilidade
    */
    'skill_games_difficulty_options' => [
        'easy' => 'Fácil (Easy)',
        'medium' => 'Médio (Medium)',
        'hard' => 'Difícil (Hard)',
    ],
];
