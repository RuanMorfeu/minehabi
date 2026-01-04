<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('api')->check() && auth('api')->user()->banned) {
            $user = auth('api')->user();

            // Registrar tentativa de acesso de usuário banido
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->path(),
                    'ban_reason' => $user->ban_reason,
                ])
                ->log('banned_user_access_attempt');

            // Deslogar o usuário
            auth('api')->logout();

            // Preparar mensagem com motivo do banimento
            $message = 'Sua conta está suspensa.';
            if ($user->ban_reason) {
                $message .= ' Motivo: '.$user->ban_reason;
            }

            return response()->json([
                'error' => $message,
                'ban_reason' => $user->ban_reason,
                'banned' => true,
            ], 403);
        }

        return $next($request);
    }
}
