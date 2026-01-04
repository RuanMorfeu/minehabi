<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BettingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '15s';

    protected static bool $isLazy = true;

    protected int $defaultPeriodDays = 7;

    protected int $defaultLimit = 10;

    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->whereIn('type', ['bet', 'loss', 'win'])
            ->with('user')
            ->latest()
            ->limit($this->defaultLimit);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('user.name')
                ->label('Usuário')
                ->searchable()
                ->sortable(),
            TextColumn::make('game')
                ->label('Jogo')
                ->searchable()
                ->sortable(),
            TextColumn::make('amount')
                ->label('Valor')
                ->money('BRL')
                ->sortable(),
            TextColumn::make('type')
                ->label('Tipo')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Aposta' => 'warning',
                    'Ganho' => 'success',
                    'Perda' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('created_at')
                ->label('Data')
                ->dateTime('d/m/Y H:i:s')
                ->sortable(),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Últimas Apostas';
    }

    protected function getFooter(): ?string
    {
        // Jogos mais jogados hoje
        $today = Carbon::today();
        $topGamesToday = Order::select('game', DB::raw('count(*) as total'))
            ->whereIn('type', ['bet', 'loss', 'win'])
            ->where('created_at', '>=', $today->copy()->startOfDay())
            ->where('created_at', '<=', $today->copy()->endOfDay())
            ->groupBy('game')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Jogos mais jogados nos últimos X dias
        $periodStart = Carbon::now()->subDays($this->defaultPeriodDays);
        $topGamesPeriod = Order::select('game', DB::raw('count(*) as total'))
            ->whereIn('type', ['bet', 'loss', 'win'])
            ->where('created_at', '>=', $periodStart)
            ->groupBy('game')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $todayGamesHtml = '<div class="mt-4"><h3 class="text-lg font-medium">Jogos Mais Jogados Hoje</h3><ul class="list-disc pl-5 mt-2">';
        foreach ($topGamesToday as $game) {
            $todayGamesHtml .= '<li>'.e($game->game).' - '.$game->total.' apostas</li>';
        }
        $todayGamesHtml .= '</ul></div>';

        $periodGamesHtml = '<div class="mt-4"><h3 class="text-lg font-medium">Jogos Mais Jogados nos Últimos '.$this->defaultPeriodDays.' Dias</h3><ul class="list-disc pl-5 mt-2">';
        foreach ($topGamesPeriod as $game) {
            $periodGamesHtml .= '<li>'.e($game->game).' - '.$game->total.' apostas</li>';
        }
        $periodGamesHtml .= '</ul></div>';

        return $todayGamesHtml.$periodGamesHtml;
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
