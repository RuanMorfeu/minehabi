<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;

class IpTrackingService
{
    /**
     * Verifica se um IP é suspeito com base em vários critérios
     */
    public static function checkSuspiciousIp(string $ip): array
    {
        $result = [
            'suspicious' => false,
            'reasons' => [],
            'risk_score' => 0,
        ];

        // Verificar se o IP está associado a muitos usuários diferentes
        $userCount = Activity::query()
            ->whereJsonContains('properties->ip', $ip)
            ->distinct('causer_id')
            ->count('causer_id');

        if ($userCount > 3) {
            $result['suspicious'] = true;
            $result['reasons'][] = "IP usado por {$userCount} usuários diferentes";
            $result['risk_score'] += min(($userCount * 10), 50);
        }

        // Verificar se houve muitos logins em um curto período de tempo
        $recentLoginCount = Activity::query()
            ->whereJsonContains('properties->ip', $ip)
            ->where('description', 'login')
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentLoginCount > 5) {
            $result['suspicious'] = true;
            $result['reasons'][] = "{$recentLoginCount} logins na última hora";
            $result['risk_score'] += min(($recentLoginCount * 5), 30);
        }

        // Verificar se o IP está em alguma lista de bloqueio (opcional, requer API externa)
        try {
            // Você pode usar uma API como ipqualityscore.com, abuseipdb.com, etc.
            // Este é apenas um exemplo e requer uma chave de API válida
            // $response = Http::get("https://api.abuseipdb.com/api/v2/check", [
            //     'ipAddress' => $ip,
            //     'key' => config('services.abuseipdb.key'),
            // ]);
            //
            // if ($response->successful() && $response->json('data.abuseConfidenceScore') > 50) {
            //     $result['suspicious'] = true;
            //     $result['reasons'][] = "IP em lista de bloqueio com pontuação " . $response->json('data.abuseConfidenceScore');
            //     $result['risk_score'] += $response->json('data.abuseConfidenceScore');
            // }
        } catch (\Exception $e) {
            // Falha silenciosa se a API não estiver disponível
        }

        // Classificar o nível de risco
        if ($result['risk_score'] >= 70) {
            $result['risk_level'] = 'Alto';
        } elseif ($result['risk_score'] >= 40) {
            $result['risk_level'] = 'Médio';
        } elseif ($result['risk_score'] > 0) {
            $result['risk_level'] = 'Baixo';
        } else {
            $result['risk_level'] = 'Nenhum';
        }

        return $result;
    }

    /**
     * Obtém os IPs mais ativos no sistema
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getMostActiveIps(int $limit = 10)
    {
        return Activity::query()
            ->whereNotNull('properties->ip')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('ip')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém o histórico de IPs de um usuário específico
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getUserIpHistory(int $userId)
    {
        return Activity::query()
            ->where('causer_id', $userId)
            ->where('causer_type', 'App\\Models\\User')
            ->whereNotNull('properties->ip')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip')
            ->selectRaw('MAX(created_at) as last_seen')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('ip')
            ->orderByDesc('last_seen')
            ->get();
    }
}
