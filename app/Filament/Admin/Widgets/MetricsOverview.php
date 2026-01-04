<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Deposit;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MetricsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '15s';

    protected static bool $isLazy = true;

    /**
     * @return array|Stat[]
     */
    protected function getStats(): array
    {
        // Período para cálculos (últimos 30 dias)
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        // ARPU (Average Revenue Per User)
        $arpu = $this->calculateARPU($startDate, $endDate);

        // Churn Rate
        $churnRate = $this->calculateChurnRate($startDate, $endDate);

        // LTV (Lifetime Value) - Calculado mas não exibido
        // $ltv = $this->calculateLTV($startDate, $endDate);

        return [
            Stat::make('ARPU (30 dias)', \Helper::amountFormatDecimal($arpu))
                ->description('Receita média por usuário')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Churn Rate (30 dias)', number_format($churnRate, 2).'%')
                ->description('Taxa de abandono de usuários')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger')
                ->chart([3, 5, 4, 6, 5, 7, 6, 8]),

            // LTV métrica removida conforme solicitado
        ];
    }

    /**
     * Calcula o ARPU (Average Revenue Per User)
     */
    private function calculateARPU(Carbon $startDate, Carbon $endDate): float
    {
        // Total de depósitos no período
        $totalDeposits = Deposit::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->sum('amount');

        // Número de usuários ativos no período (que fizeram pelo menos um depósito)
        $activeUsers = Deposit::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->distinct('user_id')
            ->count('user_id');

        // Evitar divisão por zero
        if ($activeUsers === 0) {
            return 0;
        }

        return $totalDeposits / $activeUsers;
    }

    /**
     * Calcula a taxa de Churn (abandono)
     */
    private function calculateChurnRate(Carbon $startDate, Carbon $endDate): float
    {
        // Período anterior para comparação
        $previousStartDate = (clone $startDate)->subDays(30);
        $previousEndDate = (clone $startDate)->subDay();

        // Usuários ativos no período anterior
        $previousActiveUsers = Deposit::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->where('status', 1)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        // Usuários ativos no período atual
        $currentActiveUsers = Deposit::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        // Usuários que estavam ativos no período anterior mas não estão ativos no período atual
        $churnedUsers = count(array_diff($previousActiveUsers, $currentActiveUsers));

        // Evitar divisão por zero
        if (count($previousActiveUsers) === 0) {
            return 0;
        }

        // Taxa de abandono = (usuários perdidos / usuários do período anterior) * 100
        return ($churnedUsers / count($previousActiveUsers)) * 100;
    }

    /**
     * Calcula o LTV (Lifetime Value)
     */
    private function calculateLTV(Carbon $startDate, Carbon $endDate): float
    {
        // Cálculo do lucro usando a mesma fórmula do ProfitOverview

        // Total de depósitos no período
        $totalDeposits = Deposit::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->sum('amount');

        // Total de saques no período
        $totalWithdrawals = DB::table('withdrawals')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->sum('amount');

        // Total de comissões pagas no período (revshare + cpa)
        $totalCommissions = DB::table('affiliate_histories')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('commission_paid');

        // Custo de provedores (15% dos depósitos)
        $providerCost = $totalDeposits * 0.15;

        // Lucro total no período: Depósitos - Comissões - Saques - 15% de provedor
        $totalProfit = $totalDeposits - $totalCommissions - $totalWithdrawals - $providerCost;

        // Número de usuários ativos no período
        $activeUsers = Deposit::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 1)
            ->distinct('user_id')
            ->count('user_id');

        // Lucro médio por usuário
        $averageProfitPerUser = $activeUsers > 0 ? $totalProfit / $activeUsers : 0;

        // Tempo médio de vida do cliente em meses (baseado em dados históricos)
        // Para um cálculo mais preciso, seria necessário analisar dados históricos completos
        $averageLifetimeMonths = 6; // Estimativa de 6 meses como tempo médio de vida do cliente

        // LTV = Lucro médio por usuário * Tempo médio de vida
        return $averageProfitPerUser * $averageLifetimeMonths;
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
