<?php

namespace App\Http\Controllers\Panel\Games;

use App\Models\GameSetting;

class Jetpack implements GameInterface
{
    public function getId(): string
    {
        return 'jetpack';
    }

    public function getName(): string
    {
        return 'Jetpack Joyride';
    }

    public function getDescription(): array
    {
        return [
            'title' => '🚀 Voe Alto e Ganhe Mais!',
            'text' => 'Controle seu jetpack, desvie de obstáculos e colete moedas em uma aventura emocionante pelos céus!',
        ];
    }

    public function getIcon(): string
    {
        return 'assets/games/jetpack/icon.png';
    }

    public function getBanner(): string
    {
        return 'assets/games/jetpack/banner.png';
    }

    public function getPresellSettings(): array
    {
        return [
            'coin_rate' => (float) GameSetting::getSetting('GAME_JETPACK_PRESELL_COIN_RATE', 0.01),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_JETPACK_PRESELL_META_MULTIPLIER', 1),
            'player_speed' => (int) GameSetting::getSetting('GAME_JETPACK_PRESELL_PLAYER_SPEED', 1000),
            'missile_speed' => (int) GameSetting::getSetting('GAME_JETPACK_PRESELL_MISSILE_SPEED', 2000),
        ];
    }

    public function getRealSettings(): array
    {
        return [
            'coin_rate' => (float) GameSetting::getSetting('GAME_JETPACK_REAL_COIN_RATE', 0.01),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_JETPACK_REAL_META_MULTIPLIER', 4),
            'player_speed' => (int) GameSetting::getSetting('GAME_JETPACK_REAL_PLAYER_SPEED', 1000),
            'missile_speed' => (int) GameSetting::getSetting('GAME_JETPACK_REAL_MISSILE_SPEED', 2000),
        ];
    }
}
