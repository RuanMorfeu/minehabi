<?php

namespace App\Filament\Affiliate\Widgets;

use App\Models\AffiliateHistory;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AffiliateWidgets extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $navigationSort = -2;

    // Desabilita o cache do widget para que ele seja sempre atualizado
    protected static bool $isLazy = false;

    /**
     * Obtém os filtros de data do dashboard
     */
    protected function getFilters(): array
    {
        // $this->filters é preenchido pelo trait InteractsWithPageFilters
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        return [
            'startDate' => $startDate ? Carbon::parse($startDate)->startOfDay() : null,
            'endDate' => $endDate ? Carbon::parse($endDate)->endOfDay() : null,
        ];
    }

    /**
     * Aplica filtros de data à consulta
     */
    protected function applyDateFilters($query, string $dateColumn = 'created_at'): void
    {
        $filters = $this->getFilters();

        if ($filters['startDate']) {
            $query->where($dateColumn, '>=', $filters['startDate']);
        }

        if ($filters['endDate']) {
            $query->where($dateColumn, '<=', $filters['endDate']);
        }
    }

    /*** @return array|Stat[]
     */
    protected function getCards(): array
    {
        $inviterId = auth()->user()->id;
        $filters = $this->getFilters();

        $startDate = $filters['startDate'];
        $endDate = $filters['endDate'];
        $hasDateFilter = ($startDate || $endDate);

        // Número total de indicados
        $usersQuery = User::where('inviter', $inviterId)
            ->where('is_demo_agent', 0);
        if ($hasDateFilter) {
            $this->applyDateFilters($usersQuery);
        }
        $usersIds = $usersQuery->pluck('id');
        $usersTotal = $usersQuery->count();

        // Total de depósitos dos indicados
        $depositsQuery = DB::table('deposits')
            ->whereIn('user_id', $usersIds)
            ->where('status', 1);
        if ($hasDateFilter) {
            $this->applyDateFilters($depositsQuery);
        }
        $totalDeposits = $depositsQuery->sum('amount');

        // Total de primeiros depósitos dos indicados
        $primeiroDepositoQuery = DB::table('deposits as d1')
            ->whereIn('d1.user_id', $usersIds)
            ->where('d1.status', 1)
            ->whereNotExists(function ($query) {
                $query->from('deposits as d2')
                    ->whereRaw('d1.user_id = d2.user_id')
                    ->whereRaw('d2.created_at < d1.created_at')
                    ->where('d2.status', 1);
            });
        if ($hasDateFilter) {
            $this->applyDateFilters($primeiroDepositoQuery, 'd1.created_at');
        }
        $primeiroDepositoIndicados = $primeiroDepositoQuery->sum('d1.amount');

        // Saldo de comissões
        $walletBaseQuery = Wallet::where('user_id', $inviterId);
        if ($hasDateFilter) {
            $this->applyDateFilters($walletBaseQuery);
            $mycomission = $walletBaseQuery->sum('refer_rewards');
        } else {
            // Se não houver filtro de data, busca o total sem filtro de data para comissões
            $mycomission = Wallet::where('user_id', $inviterId)->sum('refer_rewards');
        }

        // Contagem de usuários depositantes (CPA)
        $indicationsQuery = AffiliateHistory::where('inviter', $inviterId)
            ->where('commission_type', 'cpa')
            ->where('commission_paid', '>', 0);
        if ($hasDateFilter) {
            $this->applyDateFilters($indicationsQuery);
        }
        $depositantes = $indicationsQuery->distinct('user_id')->count('user_id');

        $dateFilterInfo = '';
        if ($startDate && $endDate) {
            $dateFilterInfo = ' ('.Carbon::parse($startDate)->format('d/m/Y').' - '.Carbon::parse($endDate)->format('d/m/Y').')';
        } elseif ($startDate) {
            $dateFilterInfo = ' (a partir de '.Carbon::parse($startDate)->format('d/m/Y').')';
        } elseif ($endDate) {
            $dateFilterInfo = ' (até '.Carbon::parse($endDate)->format('d/m/Y').')';
        }

        return [
            Stat::make('Saldo de Comissão'.$dateFilterInfo, \Helper::amountFormatDecimal($mycomission))
                ->description('Seu saldo de comissão atual.')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
            Stat::make('Total de Indicados'.$dateFilterInfo, \Helper::formatNumber($usersTotal))
                ->description('Número total de usuários indicados por você.')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Cadastros Depositantes (CPA)'.$dateFilterInfo, \Helper::formatNumber($depositantes))
                ->description('Usuários indicados que realizaram o primeiro depósito e geraram comissão CPA.')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
            Stat::make('Total Depósitos de Indicados'.$dateFilterInfo, \Helper::amountFormatDecimal($totalDeposits))
                ->description('Soma de todos os depósitos realizados pelos seus indicados.')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Soma de Primeiros Depósitos'.$dateFilterInfo, \Helper::amountFormatDecimal($primeiroDepositoIndicados))
                ->description('Soma dos primeiros depósitos dos seus indicados.')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('afiliado');
    }

    public function copyReferCode($referCode)
    {
        $this->dispatch('copied', referCode: $referCode);
    }
}
