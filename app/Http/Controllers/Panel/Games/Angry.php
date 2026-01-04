<?php

namespace App\Http\Controllers\Panel\Games;

use App\Models\GameSetting;

class Angry implements GameInterface
{
    public function getId(): string
    {
        return 'angry';
    }

    public function getName(): string
    {
        return 'Angry Money';
    }

    public function getDescription(): array
    {
        return [
            'title' => '🐦 Catapulte-se para a Diversão!',
            'text' => 'Mostre sua habilidade em mira e estratégia, derrubando obstáculos e acumulando pontos. Cada nível traz novos desafios e mais emoção!',
        ];
    }

    public function getIcon(): string
    {
        return 'assets/games/angry/icon.png';
    }

    public function getBanner(): string
    {
        return 'assets/games/angry/banner.png';
    }

    public function getPresellSettings(): array
    {
        return [
            'coin_multiplier' => (int) GameSetting::getSetting('GAME_ANGRY_PRESELL_COIN_MULTIPLIER', 1),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_ANGRY_PRESELL_META_MULTIPLIER', 1),
            'game_difficulty' => (int) GameSetting::getSetting('GAME_ANGRY_PRESELL_DIFFICULTY', 1),
        ];
    }

    public function getRealSettings(): array
    {
        return [
            'coin_multiplier' => (int) GameSetting::getSetting('GAME_ANGRY_REAL_COIN_MULTIPLIER', 4),
            'meta_multiplier' => (int) GameSetting::getSetting('GAME_ANGRY_REAL_META_MULTIPLIER', 4),
            'game_difficulty' => (int) GameSetting::getSetting('GAME_ANGRY_REAL_DIFFICULTY', 1),
        ];
    }
}
