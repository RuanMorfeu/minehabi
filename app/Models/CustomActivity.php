<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class CustomActivity extends SpatieActivity
{
    /**
     * Get the real IP address of the user, checking for common proxy headers.
     */
    protected function getIp(Request $request): string
    {
        // Headers to check for the real IP address, in order of preference.
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if ($request->server->has($header)) {
                // Take the first IP address from the list (it's the most likely to be the client).
                $ip = trim(explode(',', $request->server->get($header))[0]);

                // Validate the IP address and ensure it's not a private or reserved range.
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }

        // Fallback to Laravel's default ip() method if no valid IP is found in headers.
        return $request->ip();
    }

    /**
     * The "booting" method of the model.
     * Overrides the default behavior to use our custom IP getter.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $activity) {
            $activity->properties = $activity->properties ?? new Collection;

            // Check if a request is available (real or simulated)
            if (! app()->bound('request')) {
                // If there's no request (e.g., a seeder), use a default value.
                $ip = '127.0.0.1'; // Default Console IP
                $locationData = null;
                $userAgent = 'Console';
            } else {
                // If there is a request, use our enhanced logic.
                $request = request();
                $ip = (new self)->getIp($request);

                // Tentar obter localização com o IP atual
                $locationData = \Stevebauman\Location\Facades\Location::get($ip);

                // Se não conseguiu e é um IPv6, tentar obter o IPv4 do usuário
                if (! $locationData && (str_starts_with($ip, '2001:') || str_contains($ip, ':'))) {
                    // Tentar obter IPv4 de cabeçalhos alternativos
                    $alternativeIps = [
                        $request->header('HTTP_X_FORWARDED_FOR'),
                        $request->header('HTTP_X_REAL_IP'),
                        $request->header('HTTP_CLIENT_IP'),
                        $request->ip(),
                    ];

                    // Filtrar IPs válidos que não são IPv6
                    $alternativeIps = array_filter($alternativeIps, function ($altIp) {
                        return $altIp && filter_var($altIp, FILTER_VALIDATE_IP) && ! str_contains($altIp, ':');
                    });

                    // Tentar cada IP alternativo até conseguir uma localização
                    foreach ($alternativeIps as $altIp) {
                        $altLocationData = \Stevebauman\Location\Facades\Location::get($altIp);
                        if ($altLocationData) {
                            $locationData = $altLocationData;
                            // Manter o IP original, mas usar a localização do IP alternativo
                            break;
                        }
                    }
                }

                $userAgent = $request->userAgent();
            }

            // Save IP and Location
            if (config('activitylog.log_ip')) {
                $activity->properties = $activity->properties->put('ip', $ip);
            }

            // Tratamento mais robusto para dados de localização
            $locationInfo = [];

            if ($locationData) {
                // Se temos dados de geolocalização, use-os
                $locationInfo = $locationData->toArray();

                // Garantir que as chaves estejam no formato esperado (camelCase)
                if (isset($locationInfo['country_name']) && ! isset($locationInfo['countryName'])) {
                    $locationInfo['countryName'] = $locationInfo['country_name'];
                }
                if (isset($locationInfo['city']) && ! isset($locationInfo['cityName'])) {
                    $locationInfo['cityName'] = $locationInfo['city'];
                }

                // Adicionar informação sobre o tipo de IP
                if (str_contains($ip, ':')) {
                    $locationInfo['ipVersion'] = 6;
                } else {
                    $locationInfo['ipVersion'] = 4;
                }
            } else {
                // Se não temos dados de geolocalização, forneça informações úteis
                if ($ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
                    $locationInfo = [
                        'countryName' => 'Local',
                        'cityName' => 'Rede Interna',
                        'isLocalNetwork' => true,
                    ];
                } elseif (str_starts_with($ip, '2001:') || str_contains($ip, ':')) {
                    // Tentar usar um serviço alternativo para IPv6 se o principal falhou
                    try {
                        // Tentar usar ipinfo.io como fallback para IPv6
                        $ipInfo = @file_get_contents("https://ipinfo.io/{$ip}/json");
                        if ($ipInfo) {
                            $ipData = json_decode($ipInfo, true);
                            if ($ipData && isset($ipData['country']) && isset($ipData['city'])) {
                                $locationInfo = [
                                    'countryName' => $ipData['country'],
                                    'cityName' => $ipData['city'],
                                    'ipVersion' => 6,
                                    'source' => 'ipinfo.io',
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        // Se falhar, usar valores padrão
                    }

                    // Se ainda não temos dados, usar valores padrão
                    if (empty($locationInfo)) {
                        $locationInfo = [
                            'countryName' => 'Desconhecido',
                            'cityName' => 'Endereço IPv6',
                            'ipVersion' => 6,
                        ];
                    }
                } else {
                    $locationInfo = [
                        'countryName' => 'Desconhecido',
                        'cityName' => 'Não disponível',
                        'noGeoData' => true,
                    ];
                }
            }

            $activity->properties = $activity->properties->put('location', $locationInfo);

            // Save User Agent
            if (config('activitylog.log_user_agent')) {
                $activity->properties = $activity->properties->put('user_agent', $userAgent);
            }
        });
    }
}
