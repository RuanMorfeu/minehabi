<?php

namespace App\Livewire;

use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = -2;

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $setting = \Helper::getSetting();
        $dataAtual = Carbon::now();
        $depositQuery = Deposit::query();
        $withdrawalQuery = Withdrawal::query();

        if (empty($startDate) && empty($endDate)) {
            $depositQuery->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year); // Adiciona filtro do ano
        } else {
            $depositQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Executa a consulta para obter a soma dos depósitos para o mês atual
        $sumDepositMonth = $depositQuery
            ->where('status', 1)
            ->sum('amount');

        $withdrawalQuery->where('status', 1);

        if (empty($startDate) && empty($endDate)) {
            $withdrawalQuery->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year); // Adiciona filtro do ano
        } else {
            $withdrawalQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $sumWithdrawalMonth = $withdrawalQuery->sum('amount');

        // Consulta para Revshare e CPA seguindo o mesmo período dos outros cards
        $revshareQuery = AffiliateHistory::where('commission_type', 'revshare');
        $cpaQuery = AffiliateHistory::where('commission_type', 'cpa');

        // Aplicar o mesmo filtro de data que é aplicado aos depósitos e saques
        if (empty($startDate) && empty($endDate)) {
            $revshareQuery->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year); // Adiciona filtro do ano
            $cpaQuery->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year); // Adiciona filtro do ano
        } else {
            $revshareQuery->whereBetween('created_at', [$startDate, $endDate]);
            $cpaQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $revshare = $revshareQuery->sum('commission_paid');
        $cpaCommission = $cpaQuery->sum('commission_paid');

        return [
            Stat::make('Depositos', \Helper::amountFormatDecimal($sumDepositMonth))
                ->description('Total de Depositos')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Saques', \Helper::amountFormatDecimal($sumWithdrawalMonth))
                ->description('Total de saques')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Revshare', \Helper::amountFormatDecimal($revshare))
                ->description('Ganhos da Plataforma')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('CPA', \Helper::amountFormatDecimal($cpaCommission))
                ->description('Comissões CPA')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
