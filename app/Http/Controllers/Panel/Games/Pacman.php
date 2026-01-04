<?php

namespace App\Http\Controllers\Panel\Games;

use App\Models\GameSetting;

class Pacman implements GameInterface
{
    public function getId(): string
    {
        return 'pacman';
    }

    public function getName(): string
    {
        return 'PacMan Cash';
    }

    public function getDescription(): array
    {
        return [
            'title' => '👻 Fuja dos Fantasmas e Ganhe!',
            'text' => 'Navegue pelo labirinto, colete pontos e evite os fantasmas em uma aventura nostálgica cheia de emoção!',
        ];
    }

    public function getIcon(): string
    {
        return 'assets/games/pacman/icon.png';
    }

    public function getBanner(): string
    {
        return 'assets/games/pacman/banner.png';
    }

    public function getPresellSettings(): array
    {
        return [
            'lives' => (int) GameSetting::getSetting('GAME_PACMAN_PRESELL_LIVES', 3),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_PACMAN_PRESELL_META_MULTIPLIER', 1),
            'coin_rate' => (float) GameSetting::getSetting('GAME_PACMAN_PRESELL_COIN_RATE', 0.1),
            'ghost_points' => (int) GameSetting::getSetting('GAME_PACMAN_PRESELL_GHOST_POINTS', 10),
        ];
    }

    public function getRealSettings(): array
    {
        return [
            'lives' => (int) GameSetting::getSetting('GAME_PACMAN_REAL_LIVES', 3),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_PACMAN_REAL_META_MULTIPLIER', 4),
            'coin_rate' => (float) GameSetting::getSetting('GAME_PACMAN_REAL_COIN_RATE', 0.1),
            'ghost_points' => (int) GameSetting::getSetting('GAME_PACMAN_REAL_GHOST_POINTS', 10),
        ];
    }
}
