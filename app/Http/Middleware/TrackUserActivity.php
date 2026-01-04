<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class TrackUserActivity
{
    /**
     * Rastreia a atividade do usuário, incluindo o IP
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Só registra atividade para usuários autenticados
        if (Auth::check()) {
            $user = Auth::user();

            // Ignora rotas específicas para não sobrecarregar o log
            $ignoredRoutes = ['api/me', 'api/verify', 'api/refresh'];
            $currentRoute = $request->path();

            $shouldLog = ! collect($ignoredRoutes)->contains(function ($route) use ($currentRoute) {
                return str_contains($currentRoute, $route);
            });

            if ($shouldLog && $request->method() !== 'GET') {
                // Obter informações de localização do IP
                $ipLocation = Location::get($request->ip());

                $locationData = [];
                if ($ipLocation) {
                    $locationData = [
                        'country_name' => $ipLocation->countryName,
                        'country_code' => $ipLocation->countryCode,
                        'region' => $ipLocation->regionName,
                        'city' => $ipLocation->cityName,
                    ];
                }

                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'route' => $request->path(),
                        'method' => $request->method(),
                        'location' => $locationData,
                    ])
                    ->log('user_activity');
            }
        }

        return $response;
    }
}
