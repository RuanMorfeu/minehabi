<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getTopGames(Request $request)
    {
        // Verificar se o usuário é um administrador
        if (! Auth::user() || ! Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $topGames = Order::select('game', DB::raw('count(*) as total'))
            ->whereIn('type', ['bet', 'loss', 'win'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('game')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json($topGames);
    }
}
