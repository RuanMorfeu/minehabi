<?php

declare(strict_types=1);

namespace App\Services\Settings;

class MenuService
{
    public static function get(): array
    {
        return [
            [
                'name' => 'Home',
                'url' => 'home',
            ],
            [
                'name' => 'Casino',
                'url' => 'casino.index',
            ],
            [
                'name' => 'Esportes',
                'url' => 'home',
            ],
            [
                'name' => 'Promoções',
                'url' => 'home',
            ],
        ];
    }
}
