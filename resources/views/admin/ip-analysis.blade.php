<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de IPs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Análise de IPs</h1>
        
        @if(session('success'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        @if(isset($message) && isset($message['info']))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ $message['info'] }}</span>
            </div>
        @endif
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Filtros de Busca</h2>
            <form action="{{ route('admin.ip-analysis.search') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="ip" class="block text-sm font-medium text-gray-700 mb-1">Endereço IP</label>
                        <input type="text" name="ip" id="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="Ex: 192.168.1.1" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" name="country" id="country" value="{{ $filters['country'] ?? '' }}" placeholder="Ex: Brazil ou BR" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="user_email" class="block text-sm font-medium text-gray-700 mb-1">Email do Usuário</label>
                        <input type="email" name="user_email" id="user_email" value="{{ $filters['user_email'] ?? '' }}" placeholder="Ex: usuario@exemplo.com" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Buscar</button>
                </div>
            </form>
        </div>
        
        @if(count($ipResults) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 overflow-x-auto">
                <h2 class="text-xl font-semibold mb-4">Resultados da Busca</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">País</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($ipResults as $activity)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->causer->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->causer->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->properties['ip'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['country_name']))
                                        {{ $activity->properties['location']['country_name'] }} 
                                        @if(isset($activity->properties['location']['country_code']))
                                            ({{ $activity->properties['location']['country_code'] }})
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['city']))
                                        {{ $activity->properties['location']['city'] }}
                                        @if(isset($activity->properties['location']['region']))
                                            , {{ $activity->properties['location']['region'] }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <div class="flex space-x-4 mb-8">
            <a href="{{ route('admin.ip-analysis.check-suspicious') }}" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                Verificar IPs Suspeitos
            </a>
            <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal')" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                Bloquear IP
            </button>
        </div>
        
        <!-- Seção de IPs Bloqueados -->
        @if(isset($blockedIps) && count($blockedIps) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">IPs Bloqueados</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bloqueado por</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data do Bloqueio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expira em</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($blockedIps as $blockedIp)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->ip }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->reason }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->blocked_by }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->blocked_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $blockedIp->expires_at ? $blockedIp->expires_at->format('d/m/Y H:i:s') : 'Permanente' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="{{ route('admin.ip-analysis.unblock', $blockedIp->id) }}" class="text-blue-600 hover:text-blue-900" onclick="return confirm('Tem certeza que deseja desbloquear este IP?')">
                                        Desbloquear
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <!-- Seção de IPs Suspeitos -->
        @if(isset($suspiciousIps) && count($suspiciousIps) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">IPs Suspeitos (Limite de Risco: {{ $threshold ?? 50 }})</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuários</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível de Risco</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pontuação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivos</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($suspiciousIps as $ipInfo)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['ip'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['users'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                    @if($ipInfo['risk_level'] == 'Alto') text-red-600
                                    @elseif($ipInfo['risk_level'] == 'Médio') text-yellow-600
                                    @else text-blue-600 @endif">
                                    {{ $ipInfo['risk_level'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['risk_score'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <ul class="list-disc pl-5">
                                        @foreach($ipInfo['reasons'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="{{ route('admin.ip-analysis.search', ['ip' => $ipInfo['ip']]) }}" class="text-blue-600 hover:text-blue-900 block mb-2">Analisar</a>
                                    <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal'); document.getElementById('ip-to-block').value = '{{ $ipInfo['ip'] }}'; document.getElementById('block-reason').value = 'IP suspeito: {{ implode(", ", $ipInfo['reasons']) }}'" class="text-red-600 hover:text-red-900">
                                        Bloquear
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Top 10 IPs Mais Ativos</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contagem</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($topIps as $ip)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ip->ip }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ip->count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <a href="{{ route('admin.ip-analysis.search', ['ip' => $ip->ip]) }}" class="text-blue-600 hover:text-blue-900 block mb-2">Analisar</a>
                                <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal'); document.getElementById('ip-to-block').value = '{{ $ip->ip }}'" class="text-red-600 hover:text-red-900">
                                    Bloquear
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal para bloquear IP -->
    <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'block-ip-modal') open = true" @keydown.escape.window="open = false" x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="open = false" class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Bloquear IP</h3>
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="{{ route('admin.ip-analysis.block') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="ip-to-block" class="block text-sm font-medium text-gray-700 mb-1">Endereço IP</label>
                        <input type="text" name="ip" id="ip-to-block" required placeholder="Ex: 192.168.1.1" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="block-reason" class="block text-sm font-medium text-gray-700 mb-1">Motivo do Bloqueio</label>
                        <textarea name="reason" id="block-reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Descreva o motivo do bloqueio"></textarea>
                    </div>
                    
                    <div>
                        <label for="expires-at" class="block text-sm font-medium text-gray-700 mb-1">Expira em (opcional)</label>
                        <input type="datetime-local" name="expires_at" id="expires-at" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <p class="text-sm text-gray-500 mt-1">Deixe em branco para um bloqueio permanente</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="open = false" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">Bloquear IP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise de Segurança de IPs</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Análise de Segurança de IPs</h1>
            <p class="mt-2 text-sm text-gray-600">Monitore, analise e gerencie as atividades de IP no sistema.</p>
        </div>
        
        @if(session('success'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        @if(isset($message) && isset($message['info']))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ $message['info'] }}</span>
            </div>
        @endif
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Filtros de Busca</h2>
            <form action="{{ route('admin.ip-analysis.search') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="ip" class="block text-sm font-medium text-gray-700 mb-1">Endereço IP</label>
                        <input type="text" name="ip" id="ip" value="{{ $filters['ip'] ?? '' }}" placeholder="Ex: 192.168.1.1" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" name="country" id="country" value="{{ $filters['country'] ?? '' }}" placeholder="Ex: Brazil ou BR" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="user_email" class="block text-sm font-medium text-gray-700 mb-1">Email do Usuário</label>
                        <input type="email" name="user_email" id="user_email" value="{{ $filters['user_email'] ?? '' }}" placeholder="Ex: usuario@exemplo.com" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Buscar</button>
                </div>
            </form>
        </div>
        
        @if(count($ipResults) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 overflow-x-auto">
                <h2 class="text-xl font-semibold mb-4">Resultados da Busca</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">País</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($ipResults as $activity)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->causer->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->causer->email ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->properties['ip'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['country_name']))
                                        {{ $activity->properties['location']['country_name'] }} 
                                        @if(isset($activity->properties['location']['country_code']))
                                            ({{ $activity->properties['location']['country_code'] }})
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['city']))
                                        {{ $activity->properties['location']['city'] }}
                                        @if(isset($activity->properties['location']['region']))
                                            , {{ $activity->properties['location']['region'] }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <div class="flex space-x-4 mb-8">
            <a href="{{ route('admin.ip-analysis.check-suspicious') }}" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                Verificar IPs Suspeitos
            </a>
            <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal')" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                Bloquear IP
            </button>
        </div>
        
        <!-- Seção de IPs Bloqueados -->
        @if(isset($blockedIps) && count($blockedIps) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">IPs Bloqueados</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bloqueado por</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data do Bloqueio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expira em</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($blockedIps as $blockedIp)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->ip }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->reason }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->blocked_by }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $blockedIp->blocked_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $blockedIp->expires_at ? $blockedIp->expires_at->format('d/m/Y H:i:s') : 'Permanente' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="{{ route('admin.ip-analysis.unblock', $blockedIp->id) }}" class="text-blue-600 hover:text-blue-900" onclick="return confirm('Tem certeza que deseja desbloquear este IP?')">
                                        Desbloquear
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <!-- Seção de IPs Suspeitos -->
        @if(isset($suspiciousIps) && count($suspiciousIps) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">IPs Suspeitos (Limite de Risco: {{ $threshold ?? 50 }})</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuários</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível de Risco</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pontuação</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivos</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($suspiciousIps as $ipInfo)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['ip'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['users'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                    @if($ipInfo['risk_level'] == 'Alto') text-red-600
                                    @elseif($ipInfo['risk_level'] == 'Médio') text-yellow-600
                                    @else text-blue-600 @endif">
                                    {{ $ipInfo['risk_level'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ipInfo['risk_score'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <ul class="list-disc pl-5">
                                        @foreach($ipInfo['reasons'] as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="{{ route('admin.ip-analysis.search', ['ip' => $ipInfo['ip']]) }}" class="text-blue-600 hover:text-blue-900 block mb-2">Analisar</a>
                                    <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal'); document.getElementById('ip-to-block').value = '{{ $ipInfo['ip'] }}'; document.getElementById('block-reason').value = 'IP suspeito: {{ implode(", ", $ipInfo['reasons']) }}'" class="text-red-600 hover:text-red-900">
                                        Bloquear
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Painel de Atividade Recente por IP</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">IP / Hostname</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Localização / ISP</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Contagem</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Última Atividade</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($topIps as $ip)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="font-bold">{{ $ip->ip }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($ip->hostname ?? 'N/A', 35) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                    @php
                                        $country = $ip->location['countryName'] ?? $ip->location['country_name'] ?? 'N/A';
                                        $city = $ip->location['cityName'] ?? $ip->location['city'] ?? 'N/A';
                                    @endphp
                                    <div>{{ $country }}, {{ $city }}</div>
                                    <div class="text-xs text-gray-500">{{ $ip->isp ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-center">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $ip->count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                    {{ \Carbon\Carbon::parse($ip->last_activity_at)->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ $ip->abuseIpdbLink }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-4">Verificar Reputação</a>
                                    <button x-data="{}" @click="$dispatch('open-modal', 'block-ip-modal'); document.getElementById('ip-to-block').value = '{{ $ip->ip }}'" class="text-red-600 hover:text-red-900">
                                        Bloquear
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum dado de IP encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($topIps instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-6">
                {{ $topIps->links() }}
            </div>
            @endif
        </div>

        <!-- IPs Bloqueados -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">IPs Bloqueados</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">IP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloqueado em</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Expira em</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($blockedIps ?? [] as $blockedIp)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $blockedIp->ip }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $blockedIp->reason }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $blockedIp->blocked_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                    {{ $blockedIp->expires_at ? $blockedIp->expires_at->format('d/m/Y H:i') : 'Permanente' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.ip-analysis.unblock', $blockedIp->id) }}" class="text-blue-600 hover:text-blue-900" onclick="return confirm('Tem certeza que deseja desbloquear este IP?')">
                                        Desbloquear
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum IP bloqueado encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal para bloquear IP -->
    <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'block-ip-modal') open = true" @keydown.escape.window="open = false" x-show="open" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="open = false" class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Bloquear IP</h3>
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="{{ route('admin.ip-analysis.block') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="ip-to-block" class="block text-sm font-medium text-gray-700 mb-1">Endereço IP</label>
                        <input type="text" name="ip" id="ip-to-block" required placeholder="Ex: 192.168.1.1" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="block-reason" class="block text-sm font-medium text-gray-700 mb-1">Motivo do Bloqueio</label>
                        <textarea name="reason" id="block-reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Descreva o motivo do bloqueio"></textarea>
                    </div>
                    
                    <div>
                        <label for="expires-at" class="block text-sm font-medium text-gray-700 mb-1">Expira em (opcional)</label>
                        <input type="datetime-local" name="expires_at" id="expires-at" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <p class="text-sm text-gray-500 mt-1">Deixe em branco para um bloqueio permanente</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="open = false" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">Bloquear IP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
