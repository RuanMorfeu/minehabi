<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JWTMiddleWare
{
    /*** Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (auth('api')->check()) {
                return $next($request);
            }
        } catch (\Exception $e) {
            \Log::error('JWT Auth Error: '.$e->getMessage());
        }

        \Log::warning('JWT Auth Failed. Token: '.$request->bearerToken());

        return response()->json(['error' => 'unauthenticated'], 401);
    }
}
