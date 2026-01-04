<x-filament::page>
    <div class="space-y-6">
        {{ $this->table }}

        <!-- Últimos Ganhos em Jogos de Habilidade -->
        <x-filament::section>
            <x-slot name="heading">
                Últimos Ganhos em Jogos de Habilidade
            </x-slot>

            @php
                $skillGameWins = \App\Models\Order::with('user:id,email')
                    ->where('type', 'win')
                    ->whereIn('providers', ['exclusive', 'exclusive2'])
                    ->where('amount', '>', 0)
                    ->whereHas('user', function($query) {
                        $query->where('is_demo_agent', false);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            @endphp

            @if($skillGameWins->count() > 0)
                <ul class="divide-y">
                    @foreach($skillGameWins as $win)
                        <li class="py-2 flex justify-between items-center">
                            <span class="text-sm">
                                {{ $win->user->email ?? 'user#' . $win->user_id }} - 
                                {{ $win->created_at->format('d/m/Y H:i') }}
                            </span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-500 text-white">
                                R$ {{ number_format($win->amount, 2, ',', '.') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-gray-500 text-center py-4">
                    Nenhum ganho registrado
                </div>
            @endif
        </x-filament::section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Jogos Mais Jogados Hoje
                </x-slot>

                <x-slot name="description">
                    Lista dos jogos mais populares nas últimas 24 horas
                </x-slot>

                @php
                    $today = \Carbon\Carbon::today();
                    $topGamesToday = \App\Models\Order::select('game', DB::raw('count(*) as total'))
                        ->whereIn('type', ['bet', 'loss', 'win'])
                        ->whereDate('created_at', $today)
                        ->groupBy('game')
                        ->orderByDesc('total')
                        ->limit(10)
                        ->get();
                    
                    // Obter mapeamento de códigos de jogos para nomes
                    $gameNames = \App\Models\Game::pluck('game_name', 'game_code')->toArray();
                @endphp

                <div class="space-y-4">
                    @if($topGamesToday->count() > 0)
                        <ul class="divide-y">
                            @foreach($topGamesToday as $game)
                                <li class="py-2 flex justify-between items-center">
                                    <span class="font-medium">{{ $gameNames[$game->game] ?? $game->game }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary-500 text-white">
                                        {{ $game->total }} apostas
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-gray-500 text-center py-4">
                            Nenhuma aposta registrada hoje
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Jogos Mais Jogados nos Últimos 7 Dias
                </x-slot>

                <x-slot name="description">
                    Lista dos jogos mais populares na última semana
                </x-slot>

                @php
                    $periodStart = \Carbon\Carbon::now()->subDays(7);
                    $topGamesPeriod = \App\Models\Order::select('game', DB::raw('count(*) as total'))
                        ->whereIn('type', ['bet', 'loss', 'win'])
                        ->where('created_at', '>=', $periodStart)
                        ->groupBy('game')
                        ->orderByDesc('total')
                        ->limit(10)
                        ->get();
                    
                    // Usar o mesmo mapeamento de códigos para nomes em todas as seções
                    if (!isset($gameNames)) {
                        $gameNames = \App\Models\Game::pluck('game_name', 'game_code')->toArray();
                    }
                @endphp

                <div class="space-y-4">
                    @if($topGamesPeriod->count() > 0)
                        <ul class="divide-y">
                            @foreach($topGamesPeriod as $game)
                                <li class="py-2 flex justify-between items-center">
                                    <span class="font-medium">{{ $gameNames[$game->game] ?? $game->game }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary-500 text-white">
                                        {{ $game->total }} apostas
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-gray-500 text-center py-4">
                            Nenhuma aposta registrada nos últimos 7 dias
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Período Personalizado
            </x-slot>

            <x-slot name="description">
                Selecione um período específico para ver os jogos mais jogados
            </x-slot>

            <form method="GET" action="" id="custom-period-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Inicial</label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            value="{{ request('start_date') }}" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm"
                        >
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Final</label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            value="{{ request('end_date') }}" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm"
                        >
                    </div>
                </div>

                <div>
                    <button type="submit" class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset filament-button dark:focus:ring-offset-0 h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 filament-page-button-action">
                        <span>Buscar</span>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                @php
                    $startDate = request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->startOfDay() : null;
                    $endDate = request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->endOfDay() : null;
                    
                    $customGames = [];
                    
                    if ($startDate && $endDate) {
                        $customGames = \App\Models\Order::select('game', DB::raw('count(*) as total'))
                            ->whereIn('type', ['bet', 'loss', 'win'])
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->groupBy('game')
                            ->orderByDesc('total')
                            ->limit(10)
                            ->get();
                    }
                    
                    // Usar o mesmo mapeamento de códigos para nomes em todas as seções
                    if (!isset($gameNames)) {
                        $gameNames = \App\Models\Game::pluck('game_name', 'game_code')->toArray();
                    }
                @endphp

                @if($startDate && $endDate)
                    @if($customGames->count() > 0)
                        <ul class="divide-y">
                            @foreach($customGames as $game)
                                <li class="py-2 flex justify-between items-center">
                                    <span class="font-medium">{{ $gameNames[$game->game] ?? $game->game }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary-500 text-white">
                                        {{ $game->total }} apostas
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-gray-500 text-center py-4">
                            Nenhuma aposta registrada no período selecionado
                        </div>
                    @endif
                @else
                    <div class="text-gray-500 text-center py-4">
                        Selecione um período para ver os jogos mais jogados
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
