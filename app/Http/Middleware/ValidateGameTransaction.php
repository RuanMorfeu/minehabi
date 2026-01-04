<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ValidateGameTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            Log::warning('Tentativa de acesso não autenticado aos jogos', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route()->getName(),
            ]);

            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        // Para endpoints de win/lost, validar se há transação válida
        if ($request->isMethod('post') && (str_contains($request->route()->getName(), 'win') || str_contains($request->route()->getName(), 'lost'))) {
            $ganho = $request->input('ganho', 0);

            // Validar se o ganho não é absurdamente alto (possível manipulação)
            if ($ganho > 10000) {
                Log::alert('Tentativa de ganho suspeito', [
                    'user_id' => $user->id,
                    'ganho' => $ganho,
                    'ip' => $request->ip(),
                    'route' => $request->route()->getName(),
                ]);

                return response()->json(['error' => 'Valor inválido'], 400);
            }

            // Log da transação para auditoria
            Log::info('Transação de jogo processada', [
                'user_id' => $user->id,
                'type' => str_contains($request->route()->getName(), 'win') ? 'win' : 'lost',
                'amount' => $ganho,
                'ip' => $request->ip(),
                'game' => $request->route('game') ?? $request->route('slug'),
            ]);
        }

        return $next($request);
    }
}
