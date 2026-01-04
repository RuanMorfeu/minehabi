<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    /**
     * Obter banner de promoção de depósito ativo
     */
    public function getDepositPromoBanner(): JsonResponse
    {
        $banner = Banner::where('is_active', true)
            ->where('is_deposit_promo', true)
            ->where('type', 'deposit_promo')
            ->latest()
            ->first();

        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Nenhum banner de promoção de depósito ativo encontrado.']);
        }

        return response()->json([
            'success' => true,
            'banner' => [
                'id' => $banner->id,
                'image' => $banner->image,
                'description' => $banner->description,
                'link' => $banner->link,
            ],
        ]);
    }

    /**
     * Obter banner de login ativo
     */
    public function getLoginBanner(): JsonResponse
    {
        $banner = Banner::where('is_active', true)
            ->where('type', 'login')
            ->latest()
            ->first();

        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Nenhum banner de login ativo encontrado.']);
        }

        return response()->json([
            'success' => true,
            'banner' => [
                'id' => $banner->id,
                'image' => $banner->image,
                'description' => $banner->description,
                'link' => $banner->link,
            ],
        ]);
    }

    /**
     * Obter banner de registro ativo
     */
    public function getRegisterBanner(): JsonResponse
    {
        $banner = Banner::where('is_active', true)
            ->where('type', 'register')
            ->latest()
            ->first();

        if (! $banner) {
            return response()->json(['success' => false, 'message' => 'Nenhum banner de registro ativo encontrado.']);
        }

        return response()->json([
            'success' => true,
            'banner' => [
                'id' => $banner->id,
                'image' => $banner->image,
                'description' => $banner->description,
                'link' => $banner->link,
            ],
        ]);
    }
}
