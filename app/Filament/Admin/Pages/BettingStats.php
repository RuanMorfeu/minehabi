<?php

namespace App\Filament\Admin\Pages;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class BettingStats extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.betting-stats';

    protected static ?string $navigationLabel = 'Estatísticas de Apostas';

    protected static ?string $modelLabel = 'Estatísticas de Apostas';

    protected static ?string $title = 'Estatísticas de Apostas';

    protected static ?string $slug = 'estatisticas-apostas';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 5;

    public function getTableQuery(): Builder
    {
        return Order::query()
            ->whereIn('type', ['bet', 'loss', 'win'])
            ->with('user')
            ->latest();
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('user.email')
                ->label('Usuário')
                ->searchable()
                ->sortable(),
            TextColumn::make('game')
                ->label('Jogo')
                ->formatStateUsing(function ($state) {
                    $gameNames = \App\Models\Game::pluck('game_name', 'game_code')->toArray();

                    return $gameNames[$state] ?? $state;
                })
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

    public function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('game_name')
                ->label('Nome do Jogo')
                ->form([
                    \Filament\Forms\Components\TextInput::make('game_name')
                        ->label('Nome do Jogo')
                        ->placeholder('Digite o nome do jogo'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['game_name'])) {
                        return $query;
                    }

                    $search = $data['game_name'];
                    $gameNames = \App\Models\Game::pluck('game_name', 'game_code')->toArray();
                    $gameCodes = array_keys(array_filter($gameNames, function ($name) use ($search) {
                        return stripos($name, $search) !== false;
                    }));

                    return $query->where(function (Builder $query) use ($search, $gameCodes) {
                        // Busca pelo código do jogo
                        $query->where('game', 'like', "%{$search}%");

                        // Se encontrou jogos pelo nome, também busca por esses códigos
                        if (! empty($gameCodes)) {
                            $query->orWhereIn('game', $gameCodes);
                        }
                    });
                }),

            Tables\Filters\Filter::make('created_at')
                ->form([
                    DatePicker::make('from')
                        ->label('De'),
                    DatePicker::make('until')
                        ->label('Até'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($data['from'] ?? null) {
                        $indicators['from'] = 'De: '.Carbon::parse($data['from'])->format('d/m/Y');
                    }

                    if ($data['until'] ?? null) {
                        $indicators['until'] = 'Até: '.Carbon::parse($data['until'])->format('d/m/Y');
                    }

                    return $indicators;
                }),
        ];
    }

    public function getTableHeading(): string
    {
        return 'Últimas Apostas';
    }

    public function getTableDescription(): string
    {
        return 'Lista das apostas mais recentes realizadas na plataforma';
    }

    protected function getTablePaginationOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
