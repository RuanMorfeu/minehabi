<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    /**
     * Verifica se o IP do visitante está bloqueado.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if (BlockedIp::isBlocked($ip)) {
            // Se for uma requisição AJAX ou API, retorna JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Acesso bloqueado. Seu endereço IP foi bloqueado pelo administrador do sistema.',
                    'code' => 'ip_blocked',
                ], 403);
            }

            // Para requisições web normais, retorna uma página de erro
            return response()->view('errors.blocked-ip', [
                'ip' => $ip,
            ], 403);
        }

        return $next($request);
    }
}
