<?php

namespace Database\Seeders;

use App\Models\GameSetting;
use Illuminate\Database\Seeder;

class GameSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configurações Angry Birds - Presell (Demo)
        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_PRESELL_COIN_MULTIPLIER'],
            ['value' => '1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_PRESELL_META_MULTIPLIER'],
            ['value' => '1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_PRESELL_DIFFICULTY'],
            ['value' => '1']
        );

        // Configurações Angry Birds - Real (baseado na plataforma original)
        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_REAL_COIN_MULTIPLIER'],
            ['value' => '4']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_REAL_META_MULTIPLIER'],
            ['value' => '4']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_ANGRY_REAL_DIFFICULTY'],
            ['value' => '1']
        );

        // Configurações Jetpack - Presell (Demo)
        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_PRESELL_COIN_RATE'],
            ['value' => '0.01']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_PRESELL_META_MULTIPLIER'],
            ['value' => '1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_PRESELL_PLAYER_SPEED'],
            ['value' => '1000']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_PRESELL_MISSILE_SPEED'],
            ['value' => '2000']
        );

        // Configurações Jetpack - Real
        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_REAL_COIN_RATE'],
            ['value' => '0.01']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_REAL_META_MULTIPLIER'],
            ['value' => '4']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_REAL_PLAYER_SPEED'],
            ['value' => '1000']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_JETPACK_REAL_MISSILE_SPEED'],
            ['value' => '2000']
        );

        // Configurações PacMan - Presell (Demo)
        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_PRESELL_LIVES'],
            ['value' => '3']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_PRESELL_META_MULTIPLIER'],
            ['value' => '1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_PRESELL_COIN_RATE'],
            ['value' => '0.1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_PRESELL_GHOST_POINTS'],
            ['value' => '10']
        );

        // Configurações PacMan - Real
        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_REAL_LIVES'],
            ['value' => '3']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_REAL_META_MULTIPLIER'],
            ['value' => '4']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_REAL_COIN_RATE'],
            ['value' => '0.1']
        );

        GameSetting::updateOrCreate(
            ['key' => 'GAME_PACMAN_REAL_GHOST_POINTS'],
            ['value' => '10']
        );

        $this->command->info('Game settings seeded successfully!');
    }
}
