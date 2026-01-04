<?php

namespace Database\Seeders;

use App\Models\GameExclusive2;
use Illuminate\Database\Seeder;

class GameExclusive2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'uuid' => 'pacman-exclusive',
                'name' => 'PacMan Cash',
                'description' => '👻 Aventura Fantasmagórica Espera por Você! Desvie de fantasmas e colete pontos em um dos jogos mais clássicos de todos os tempos.',
                'cover' => '/assets/games/pacman/banner.jpg',
                'icon' => '/assets/games/pacman/icon.webp',
                'game_type' => 'pacman',
                'min_amount' => 1.0,
                'active' => true,
                'visible_in_home' => true,
                // Configurações específicas do Pacman
                'lives' => 0, // GAME_PACMAN_REAL_LIVES
                'coin_rate' => 0.01, // GAME_PACMAN_REAL_COIN_RATE
                'meta_multiplier' => 10.0, // GAME_PACMAN_REAL_META_MULTIPLIER
                'ghost_points' => 0.1, // GAME_PACMAN_REAL_GHOST_POINTS
            ],
            [
                'uuid' => 'jetpack-exclusive',
                'name' => 'Jetpack Joyride',
                'description' => '🚀 Voe alto com seu jetpack e colete moedas! Desvie dos obstáculos e vá o mais longe possível nesta aventura emocionante.',
                'cover' => '/assets/games/jetpack/banner.jpg',
                'icon' => '/assets/games/jetpack/icon.webp',
                'game_type' => 'jetpack',
                'min_amount' => 1.0,
                'active' => true,
                'visible_in_home' => true,
                // Configurações específicas do Jetpack
                'coin_rate' => 0.01, // GAME_JETPACK_REAL_COIN_RATE
                'meta_multiplier' => 4.0, // GAME_JETPACK_REAL_META_MULTIPLIER
                'player_speed' => 1000, // GAME_JETPACK_REAL_PLAYER_SPEED
                'missile_speed' => 2000, // GAME_JETPACK_REAL_MISSILE_SPEED
            ],
            [
                'uuid' => 'angry-exclusive',
                'name' => 'Angry Birds',
                'description' => '🐦 Destrua as estruturas dos porcos verdes! Use a física a seu favor e lance os pássaros com precisão para ganhar pontos.',
                'cover' => '/assets/games/angry/banner.jpg',
                'icon' => '/assets/games/angry/icon.webp',
                'game_type' => 'angry',
                'min_amount' => 1.0,
                'active' => true,
                'visible_in_home' => true,
                // Configurações específicas do Angry Birds
                'coin_multiplier' => 1.0, // GAME_ANGRY_REAL_COIN_MULTIPLIER
                'meta_multiplier' => 4.0, // GAME_ANGRY_REAL_META_MULTIPLIER
                'game_difficulty' => 1, // GAME_ANGRY_REAL_DIFFICULTY
            ],
        ];

        foreach ($games as $gameData) {
            GameExclusive2::updateOrCreate(
                ['uuid' => $gameData['uuid']],
                $gameData
            );
        }

        $this->command->info('Jogos Exclusivos 2 criados com sucesso!');
    }
}
