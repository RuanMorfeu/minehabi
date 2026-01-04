<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    /**
     * Get Gateway Settings (Placeholder para compatibilidade)
     */
    public function getGateway(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Gateway settings endpoint - deprecated',
            'data' => [],
        ]);
    }

    /**
     * Update Gateway Settings (Placeholder para compatibilidade)
     */
    public function updateGateway(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Gateway update endpoint - deprecated',
        ]);
    }
}
