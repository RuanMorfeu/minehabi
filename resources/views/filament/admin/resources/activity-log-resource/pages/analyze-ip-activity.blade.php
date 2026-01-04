<x-filament-panels::page>
    <x-filament-panels::form wire:submit="searchByIp">
        {{ $this->form }}
        
        <div class="mt-4 flex justify-between">
            <x-filament::button type="submit" wire:click="searchByIp">
                Buscar por IP
            </x-filament::button>
            
            <x-filament::button type="button" wire:click="searchByUser">
                Buscar por Usuário
            </x-filament::button>
        </div>
    </x-filament-panels::form>
    
    @if(count($ipResults) > 0)
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Resultados da Busca por IP</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Usuário</th>
                            <th class="py-2 px-4 border-b">Email</th>
                            <th class="py-2 px-4 border-b">Ação</th>
                            <th class="py-2 px-4 border-b">IP</th>
                            <th class="py-2 px-4 border-b">País</th>
                            <th class="py-2 px-4 border-b">Cidade</th>
                            <th class="py-2 px-4 border-b">User Agent</th>
                            <th class="py-2 px-4 border-b">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ipResults as $activity)
                            <tr>
                                <td class="py-2 px-4 border-b">{{ $activity->causer->name ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->causer->email ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->description }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->properties['ip'] ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['country_name']))
                                        {{ $activity->properties['location']['country_name'] }} 
                                        @if(isset($activity->properties['location']['country_code']))
                                            ({{ $activity->properties['location']['country_code'] }})
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-2 px-4 border-b">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['city']))
                                        {{ $activity->properties['location']['city'] }}
                                        @if(isset($activity->properties['location']['region']))
                                            , {{ $activity->properties['location']['region'] }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-2 px-4 border-b">{{ Str::limit($activity->properties['user_agent'] ?? 'N/A', 50) }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    @if(count($userResults) > 0)
        <div class="mt-8">
            <h2 class="text-xl font-bold mb-4">Resultados da Busca por Usuário</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">Ação</th>
                            <th class="py-2 px-4 border-b">IP</th>
                            <th class="py-2 px-4 border-b">País</th>
                            <th class="py-2 px-4 border-b">Cidade</th>
                            <th class="py-2 px-4 border-b">User Agent</th>
                            <th class="py-2 px-4 border-b">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($userResults as $activity)
                            <tr>
                                <td class="py-2 px-4 border-b">{{ $activity->description }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->properties['ip'] ?? 'N/A' }}</td>
                                <td class="py-2 px-4 border-b">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['country_name']))
                                        {{ $activity->properties['location']['country_name'] }} 
                                        @if(isset($activity->properties['location']['country_code']))
                                            ({{ $activity->properties['location']['country_code'] }})
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-2 px-4 border-b">
                                    @if(isset($activity->properties['location']) && isset($activity->properties['location']['city']))
                                        {{ $activity->properties['location']['city'] }}
                                        @if(isset($activity->properties['location']['region']))
                                            , {{ $activity->properties['location']['region'] }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="py-2 px-4 border-b">{{ Str::limit($activity->properties['user_agent'] ?? 'N/A', 50) }}</td>
                                <td class="py-2 px-4 border-b">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    <div class="mt-8">
        <h2 class="text-xl font-bold mb-4">Top 10 IPs Mais Ativos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">IP</th>
                        <th class="py-2 px-4 border-b">Contagem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->topIps as $ip)
                        <tr>
                            <td class="py-2 px-4 border-b">{{ str_replace('"', '', $ip->ip) }}</td>
                            <td class="py-2 px-4 border-b">{{ $ip->count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
