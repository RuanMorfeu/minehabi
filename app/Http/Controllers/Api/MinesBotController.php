<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class MinesBotController extends Controller
{
    /**
     * Verifica se o bot está habilitado
     */
    public function getStatus()
    {
        $setting = Setting::first();

        return response()->json([
            'enabled' => $setting ? $setting->mines_bot_enabled : false,
        ]);
    }
}
