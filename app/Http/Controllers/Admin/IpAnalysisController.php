<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class IpAnalysisController extends Controller
{
    /**
     * Mostra a página de análise de IPs
     */
    public function index()
    {
        // Obter os IPs mais ativos
        $topIps = Activity::query()
            ->whereNotNull('properties->ip')
            ->select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip'),
                DB::raw('MAX(JSON_EXTRACT(properties, "$.location")) as location'),
                DB::raw('COUNT(*) as count'),
                DB::raw('MAX(created_at) as last_activity_at')
            )
            ->groupBy('ip')
            ->orderByDesc('count')
            ->paginate(15); // Adicionando paginação

        $topIps->getCollection()->transform(function ($item) {
            // Crie uma variável local para manipular os dados de localização e evitar erros de 'indirect modification'.
            $locationData = [];

            // Decodifique se for uma string JSON ou use se já for um array.
            if (isset($item->location) && is_string($item->location)) {
                $decoded = json_decode($item->location, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $locationData = $decoded;
                }
            } elseif (isset($item->location) && is_array($item->location)) {
                $locationData = $item->location;
            }

            // Normalizar as chaves para garantir compatibilidade (snake_case para camelCase).
            if (isset($locationData['country_name']) && ! isset($locationData['countryName'])) {
                $locationData['countryName'] = $locationData['country_name'];
            }
            if (isset($locationData['city']) && ! isset($locationData['cityName'])) {
                $locationData['cityName'] = $locationData['city'];
            }

            // Fornecer dados de fallback se a localização estiver vazia ou for desconhecida.
            if (empty($locationData) || empty($locationData['countryName']) || $locationData['countryName'] === 'Desconhecido') {
                if ($item->ip === '127.0.0.1' || str_starts_with($item->ip, '192.168.') || str_starts_with($item->ip, '10.')) {
                    $locationData['countryName'] = 'Local';
                    $locationData['cityName'] = 'Rede Interna';
                } else {
                    $locationData['countryName'] = $locationData['countryName'] ?? 'Desconhecido';
                    $locationData['cityName'] = $locationData['cityName'] ?? 'Não disponível';
                }
            }

            // Atribua a matriz modificada de volta ao item em uma única operação.
            $item->location = $locationData;

            // Adicionar informações de segurança.
            try {
                $item->hostname = gethostbyaddr($item->ip);
            } catch (\Exception $e) {
                $item->hostname = 'N/A';
            }
            $item->isp = $item->location['isp'] ?? 'N/A';
            $item->abuseIpdbLink = 'https://www.abuseipdb.com/check/'.$item->ip;

            return $item;
        });

        // Obter IPs bloqueados
        $blockedIps = BlockedIp::where('active', true)
            ->orderByDesc('blocked_at')
            ->get();

        return view('admin.ip-analysis', [
            'topIps' => $topIps,
            'ipResults' => collect(),
            'userResults' => collect(),
            'blockedIps' => $blockedIps,
            'suspiciousIps' => collect(),
        ]);
    }

    /**
     * Busca por IP
     */
    public function searchByIp(Request $request)
    {
        $request->validate([
            'ip' => 'nullable|string',
            'country' => 'nullable|string',
            'user_email' => 'nullable|email',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $query = Activity::query();

        // Filtrar por IP se fornecido
        if (! empty($request->ip)) {
            $query->whereJsonContains('properties->ip', $request->ip);
        }

        // Filtrar por país se fornecido
        if (! empty($request->country)) {
            $country = strtolower($request->country);

            // Busca pelo nome do país ou código do país
            $query->where(function ($q) use ($country) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.location.country_name'))) LIKE ?", ["%{$country}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.location.country_code'))) LIKE ?", ["%{$country}%"]);
            });
        }

        // Filtrar por email do usuário se fornecido
        if (! empty($request->user_email)) {
            // Buscar o usuário pelo email
            $user = User::where('email', $request->user_email)->first();

            if ($user) {
                $query->where('causer_id', $user->id)
                    ->where('causer_type', 'App\\Models\\User');
            } else {
                // Se o usuário não for encontrado, retornar um conjunto vazio
                return view('admin.ip-analysis', [
                    'topIps' => Activity::query()
                        ->whereNotNull('properties->ip')
                        ->select(
                            DB::raw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip'),
                            DB::raw('MAX(JSON_EXTRACT(properties, "$.location")) as location'),
                            DB::raw('COUNT(*) as count')
                        )
                        ->groupBy('ip')
                        ->orderByDesc('count')
                        ->limit(10)
                        ->get()
                        ->map(function ($item) {
                            // Processar os dados de localização para garantir formato correto
                            if (isset($item->location) && ! empty($item->location)) {
                                // Se for uma string JSON, decodificar
                                if (is_string($item->location)) {
                                    $decoded = json_decode($item->location, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $item->location = $decoded;
                                    }
                                }

                                // Se ainda for uma string ou estiver vazio após tentativa de decodificação
                                if (! is_array($item->location) || empty($item->location)) {
                                    // Fornecer dados de fallback baseados no IP
                                    if ($item->ip === '127.0.0.1' || str_starts_with($item->ip, '192.168.') || str_starts_with($item->ip, '10.')) {
                                        $item->location = [
                                            'countryName' => 'Local',
                                            'cityName' => 'Rede Interna',
                                        ];
                                    } else {
                                        $item->location = [
                                            'countryName' => 'Desconhecido',
                                            'cityName' => 'Não disponível',
                                        ];
                                    }
                                }
                            } else {
                                // Se não houver dados de localização
                                $item->location = [
                                    'countryName' => 'Desconhecido',
                                    'cityName' => 'Não disponível',
                                ];
                            }

                            return $item;
                        }),
                    'ipResults' => collect(),
                    'userResults' => collect(),
                    'filters' => $request->all(),
                    'blockedIps' => BlockedIp::where('active', true)->orderByDesc('blocked_at')->get(),
                    'suspiciousIps' => collect(),
                    'message' => ['error' => 'Usuário com o email "'.$request->user_email.'" não encontrado.'],
                ]);
            }
        }

        // Filtrar por data
        if (! empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if (! empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $results = $query->with('causer')->get();

        // Obter os IPs mais ativos
        $topIps = Activity::query()
            ->whereNotNull('properties->ip')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('ip')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Obter IPs bloqueados
        $blockedIps = BlockedIp::where('active', true)
            ->orderByDesc('blocked_at')
            ->get();

        return view('admin.ip-analysis', [
            'topIps' => $topIps,
            'ipResults' => $results,
            'userResults' => collect(),
            'filters' => $request->all(),
            'blockedIps' => $blockedIps,
            'suspiciousIps' => collect(),
        ]);
    }

    /**
     * Verificar IPs suspeitos
     */
    public function checkSuspicious(Request $request)
    {
        $threshold = $request->input('threshold', 40);

        // Obter os IPs mais ativos
        $activeIps = Activity::query()
            ->whereNotNull('properties->ip')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('ip')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $suspiciousIps = collect();

        foreach ($activeIps as $ipData) {
            $ip = $ipData->ip;
            $result = $this->checkSuspiciousIp($ip);

            if ($result['risk_score'] >= $threshold) {
                // Obter usuários associados a este IP
                $users = Activity::query()
                    ->whereJsonContains('properties->ip', $ip)
                    ->with('causer')
                    ->get()
                    ->pluck('causer.name', 'causer.id')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $result['ip'] = $ip;
                $result['users'] = $users;
                $suspiciousIps->push($result);
            }
        }

        // Verificar se encontrou IPs suspeitos
        $message = null;
        if ($suspiciousIps->isEmpty()) {
            $message = ['info' => 'Nenhum IP suspeito foi encontrado com o limite de risco atual.'];
        }

        // Obter IPs bloqueados
        $blockedIps = BlockedIp::where('active', true)
            ->orderByDesc('blocked_at')
            ->get();

        // Obter os IPs mais ativos
        $topIps = Activity::query()
            ->whereNotNull('properties->ip')
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.ip")) as ip')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('ip')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('admin.ip-analysis', [
            'topIps' => $topIps,
            'ipResults' => collect(),
            'userResults' => collect(),
            'blockedIps' => $blockedIps,
            'suspiciousIps' => $suspiciousIps,
            'threshold' => $threshold,
            'message' => $message,
        ]);
    }

    /**
     * Bloquear um IP
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date',
        ]);

        $ip = $request->input('ip');
        $reason = $request->input('reason') ?: 'IP suspeito bloqueado manualmente';
        $expiresAt = $request->input('expires_at');

        // Verificar se o IP já está bloqueado
        $existingBlock = BlockedIp::where('ip', $ip)
            ->where('active', true)
            ->first();

        if ($existingBlock) {
            return redirect()->back()->with('error', "O IP {$ip} já está bloqueado.");
        }

        // Criar o bloqueio
        BlockedIp::create([
            'ip' => $ip,
            'reason' => $reason,
            'blocked_by' => Auth::user() ? Auth::user()->name : 'Sistema',
            'blocked_at' => now(),
            'expires_at' => $expiresAt,
            'active' => true,
        ]);

        return redirect()->back()->with('success', "O IP {$ip} foi bloqueado com sucesso.");
    }

    /**
     * Desbloquear um IP
     */
    public function unblockIp(Request $request, $id)
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $ip = $blockedIp->ip;

        $blockedIp->update([
            'active' => false,
        ]);

        return redirect()->back()->with('success', "O IP {$ip} foi desbloqueado com sucesso.");
    }

    /**
     * Verifica se um IP é suspeito
     */
    private function checkSuspiciousIp(string $ip): array
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

        // Verificar acessos de países diferentes
        $countries = Activity::query()
            ->whereJsonContains('properties->ip', $ip)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('properties->location')
            ->get()
            ->pluck('properties.location.country_code')
            ->filter()
            ->unique();

        if ($countries->count() > 1) {
            $result['suspicious'] = true;
            $result['reasons'][] = "Acessos de {$countries->count()} países diferentes nas últimas 24h";
            $result['risk_score'] += min(($countries->count() * 15), 45);
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
}
