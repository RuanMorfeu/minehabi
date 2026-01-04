<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '15s';

    protected static bool $isLazy = true;

    /*** @return array|Stat[]
     */
    protected function getStats(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $totalApostas = Order::whereIn('type', ['bet', 'loss'])->sum('amount');
        $totalWins = Order::where('type', 'win')->sum('amount');
        $today = Carbon::today();

        $totalWonLast7Days = $totalWins;
        $totalLoseLast7Days = $totalApostas;

        $saldodosplayers = DB::table('users')->join('wallets', function ($join) {
            $join->on('users.id', '=', 'wallets.user_id')
                ->where('users.id', '!=', 1)
                ->where('users.is_demo_agent', 0);
        })
            ->select(DB::raw('SUM(wallets.balance + wallets.balance_withdrawal + wallets.balance_bonus) as total_balance'))
            ->value('total_balance');

        $saldoSacavel = DB::table('users')->join('wallets', function ($join) {
            $join->on('users.id', '=', 'wallets.user_id')
                ->where('users.id', '!=', 1)
                ->where('users.is_demo_agent', 0);
        })
            ->sum('wallets.balance_withdrawal');

        $totalDepositedToday = DB::table('deposits')
            ->where('created_at', '>=', $today->copy()->startOfDay())
            ->where('created_at', '<=', $today->copy()->endOfDay())
            ->where('status', 1) // Filtrar apenas os depósitos aprovados (integer)
            ->sum('amount');
        $totalsacadoToday = DB::table('withdrawals')
            ->where('created_at', '>=', $today->copy()->startOfDay())
            ->where('created_at', '<=', $today->copy()->endOfDay())
            ->where('status', 1) // Filtrar apenas os saques aprovados (integer)
            ->sum('amount');
        $totalReferRewardsLast7Days = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('users.id', '!=', 1)
            ->where('users.is_demo_agent', 0)
            ->where('orders.type', 'refer_rewards')
            ->where('orders.created_at', '>=', $sevenDaysAgo)
            ->sum('orders.amount');

        $depositCounts = DB::table('deposits')
            ->select('user_id', DB::raw('count(*) as deposit_count'))
            ->where('status', 1)
            ->groupBy('user_id')
            ->get();

        $usersWithSingleDeposit = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 1;
        });

        $numberOfUsersWithSingleDeposit = $usersWithSingleDeposit->count();

        $usersWithTwoDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 2;
        });
        $numberOfUsersWithTwoDeposits = $usersWithTwoDeposits->count();

        $usersWithThreeDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 3;
        });
        $numberOfUsersWithThreeDeposits = $usersWithThreeDeposits->count();

        $usersWithFourOrMoreDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count >= 4;
        });
        $numberOfUsersWithFourOrMoreDeposits = $usersWithFourOrMoreDeposits->count();

        // Usuários depositantes orgânicos (sem indicação)
        $depositantesOrganicos = User::whereNull('inviter')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deposits')
                    ->whereColumn('deposits.user_id', 'users.id')
                    ->where('deposits.status', 1);
            })
            ->where('is_demo_agent', 0)
            ->count();

        // Usuários depositantes indicados
        $depositantesIndicados = User::whereNotNull('inviter')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deposits')
                    ->whereColumn('deposits.user_id', 'users.id')
                    ->where('deposits.status', 1);
            })
            ->where('is_demo_agent', 0)
            ->count();

        return [
            Stat::make('Usuários Orgânicos', \Helper::formatNumber($depositantesOrganicos))
                ->description('Depositantes sem indicação')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),
            Stat::make('Usuários Indicados', $depositantesIndicados)
                ->description('Depositantes por indicação')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('Total Usuários', User::count())
                ->description('Novos usuários')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Total Depositado Hoje', \Helper::amountFormatDecimal($totalDepositedToday))
                ->description('Total depositado hoje')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Total Sacado Hoje', \Helper::amountFormatDecimal($totalsacadoToday))
                ->description('Total depositado hoje')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Saldo dos Players', \Helper::amountFormatDecimal($saldodosplayers))
                ->description('Saldo dos players')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Pode ser Sacado', \Helper::amountFormatDecimal($saldoSacavel))
                ->description('Saldo disponível para saque')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Ganhos Afiliados (7 dias)', \Helper::amountFormatDecimal($totalReferRewardsLast7Days))
                ->description('Ganhos dos Afiliados nos últimos 7 dias')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Pessoas Que Depositaram 1 Vez', $numberOfUsersWithSingleDeposit)
                ->description('Pessoas Que Depositaram 1 Vez')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Pessoas Que Depositaram 2 Vezes', $numberOfUsersWithTwoDeposits)
                ->description('Pessoas Que Depositaram 2 Vezes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Pessoas Que Depositaram 3 Vezes', $numberOfUsersWithThreeDeposits)
                ->description('Pessoas Que Depositaram 3 Vezes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Pessoas Que Depositaram 4 Vezes ou mais', $numberOfUsersWithFourOrMoreDeposits)
                ->description('Pessoas Que Depositaram 4 Vezes ou mais')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
