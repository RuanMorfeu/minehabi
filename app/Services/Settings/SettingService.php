<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    protected SettingRepository $settingRepository;

    public static function currency(): array
    {
        $currencies = [
            'EUR' => [
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€',
            ],
            'BRL' => [
                'name' => 'BRL',
                'code' => 'BRL',
                'symbol' => 'R$',
            ],
        ];

        return $currencies[env('CURRENCY', 'EUR')];
    }

    public static function template()
    {
        return [
            'home' => [
                'allow' => [
                    'gift_box' => false,
                    'categories_center' => false,
                ],
            ],
            'section' => [
                'sport' => false,
                'slot' => true,
                'original' => true,
            ],
            'sidebar' => [
                'referral_banner' => false,
                'referral_action' => true,
            ],
            'thumbs' => [
                'size' => 'portrait',
            ],
        ];
    }

    public static function get()
    {
        return (new SettingRepository)->getPublic();
    }

    public static function store($data): void
    {
        Setting::updateOrCreate(
            ['setting_key' => $data['setting_key']],
            [
                'setting_key' => $data['setting_key'],
                'setting_value' => $data['setting_value'],
                'section' => $data['section'],
                'meta' => $data['meta'],
                'is_private' => $data['is_private'],
                'active' => $data['active'],
            ]
        );
    }

    public static function getConfig()
    {
        return 0;
    }

    public static function getConfigBountyFirst(): bool
    {
        return false;
    }

    public static function compile(mixed $event = null): void
    {
        $settings = Setting::all()->toArray();

        if ($event === 'update') {
            foreach ($settings as $setting) {
                self::store($setting);
            }
        }

        Storage::disk('public')->put('3settings.json', json_encode($settings, JSON_UNESCAPED_UNICODE));
    }
}
