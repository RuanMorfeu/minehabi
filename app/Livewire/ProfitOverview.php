<?php

namespace App\Livewire;

use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    // Sem atualização automática
    protected int|string|array $columnSpan = 'full';

    // Define o template personalizado
    protected static string $view = 'filament.widgets.profit-overview';

    // Função para atualizar os dados manualmente
    public function refresh(): void
    {
        $this->dispatch('refresh');
    }

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        // Cálculo do lucro diário
        $selectedDate = null;

        // Verificar se temos filtros de data
        if (! empty($startDate) && ! empty($endDate)) {
            // Se temos filtros de data, usamos a data inicial
            $selectedDate = Carbon::parse($startDate);
        } else {
            // Caso contrário, usamos a data atual
            $selectedDate = Carbon::today();
        }

        // Entradas (depósitos) do dia
        $depositosHoje = Deposit::where('created_at', '>=', $selectedDate->copy()->startOfDay())
            ->where('created_at', '<=', $selectedDate->copy()->endOfDay())
            ->where('status', 1)
            ->sum('amount');

        // Saques do dia
        $saquesHoje = Withdrawal::where('created_at', '>=', $selectedDate->copy()->startOfDay())
            ->where('created_at', '<=', $selectedDate->copy()->endOfDay())
            ->where('status', 1)
            ->sum('amount');

        // Comissões do dia (revshare + cpa)
        $comissoesHoje = AffiliateHistory::where('created_at', '>=', $selectedDate->copy()->startOfDay())
            ->where('created_at', '<=', $selectedDate->copy()->endOfDay())
            ->sum('commission_paid');

        // Cálculo do lucro diário: Entradas - Comissões - Saques - 15% de provedor
        $provedorHoje = $depositosHoje * 0.15; // 15% de provedor
        $lucroDiario = $depositosHoje - $comissoesHoje - $saquesHoje - $provedorHoje;

        // Cálculo do lucro geral
        $depositQuery = Deposit::query()->where('status', 1);
        $withdrawalQuery = Withdrawal::query()->where('status', 1);
        $commissionsQuery = AffiliateHistory::query();

        // Aplicar filtros de data se existirem
        if (! empty($startDate) && ! empty($endDate)) {
            $depositQuery->whereBetween('created_at', [$startDate, $endDate]);
            $withdrawalQuery->whereBetween('created_at', [$startDate, $endDate]);
            $commissionsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Calcular valores totais
        $depositosTotal = $depositQuery->sum('amount');
        $saquesTotal = $withdrawalQuery->sum('amount');
        $comissoesTotal = $commissionsQuery->sum('commission_paid');

        // Cálculo do lucro geral: Entradas - Comissões - Saques - 15% de provedor
        $provedorTotal = $depositosTotal * 0.15; // 15% de provedor
        $lucroGeral = $depositosTotal - $comissoesTotal - $saquesTotal - $provedorTotal;

        return [
            Stat::make('Lucro Diário', \Helper::amountFormatDecimal($lucroDiario))
                ->description('Lucro do dia selecionado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($lucroDiario >= 0 ? 'success' : 'danger'),

            Stat::make('Entradas do Dia', \Helper::amountFormatDecimal($depositosHoje))
                ->description('Depósitos do dia selecionado')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Saques do Dia', \Helper::amountFormatDecimal($saquesHoje))
                ->description('Saques do dia selecionado')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Comissões do Dia', \Helper::amountFormatDecimal($comissoesHoje))
                ->description('Comissões pagas no dia selecionado')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Provedor do Dia (15%)', \Helper::amountFormatDecimal($provedorHoje))
                ->description('Taxa do provedor no dia selecionado')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),

            Stat::make('Lucro Geral', \Helper::amountFormatDecimal($lucroGeral))
                ->description('Lucro total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($lucroGeral >= 0 ? 'success' : 'danger'),

            Stat::make('Entradas Total', \Helper::amountFormatDecimal($depositosTotal))
                ->description('Depósitos totais')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Saques Total', \Helper::amountFormatDecimal($saquesTotal))
                ->description('Saques totais')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Comissões Total', \Helper::amountFormatDecimal($comissoesTotal))
                ->description('Comissões pagas total')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Provedor Total (15%)', \Helper::amountFormatDecimal($provedorTotal))
                ->description('Taxa do provedor total')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
