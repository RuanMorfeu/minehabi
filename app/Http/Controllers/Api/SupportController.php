<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomLayout;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    /**
     * Verifica se o suporte está ativado
     */
    public function checkStatus(): JsonResponse
    {
        $customLayout = CustomLayout::first();

        return response()->json([
            'supportActive' => $customLayout ? (bool) $customLayout->support_active : false,
            'supportLink' => $customLayout ? $customLayout->link_suporte : null,
        ]);
    }
}
