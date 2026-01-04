<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a game setting value by key (similar to original platform)
     */
    public static function getSetting($key, $default = null)
    {
        $setting = GameSetting::where('key', $key)->first();

        if (! $setting && $default !== null) {
            return self::set($key, $default);
        }

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a game setting value by key
     */
    public static function set($key, $value)
    {
        // se value é um array, ou objeto, converte para string
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        GameSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value ?? '']
        );

        return $value;
    }
}
