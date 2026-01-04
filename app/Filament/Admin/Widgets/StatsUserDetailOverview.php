<?php

namespace App\Filament\Admin\Widgets;

use App\Helpers\Core as Helper;
use App\Models\AffiliateHistory;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsUserDetailOverview extends BaseWidget
{
    public User $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    /*** @return array|Stat[]
     */
    protected function getStats(): array
    {
        // Total de ganhos (vitórias)
        $totalGanhos = Order::where('user_id', $this->record->id)
            ->where('type', 'win')
            ->where('status', 1)
            ->sum('amount');

        // Total de apostas
        $totalApostas = Order::where('user_id', $this->record->id)
            ->where('type', 'bet')
            ->where('status', 1)
            ->sum('amount');

        // Perdas = Total apostado - Total ganho
        $totalPerdas = $totalApostas - $totalGanhos;

        // Total sacado pelo usuário
        $totalSacado = DB::table('withdrawals')
            ->where('user_id', $this->record->id)
            ->where('status', 1)
            ->sum('amount');

        $totalAfiliadosRevshare = AffiliateHistory::where('inviter', $this->record->id)
            ->where('commission_type', 'revshare')
            ->sum('commission_paid');
        $totalAfiliadosCPA = AffiliateHistory::where('inviter', $this->record->id)
            ->where('commission_type', 'cpa')
            ->sum('commission_paid');
        $saldoAfiliadoSaque = Wallet::where('user_id', $this->record->id)
            ->where('refer_rewards', '>', 0)
            ->sum('refer_rewards');

        // Número total de indicados
        $totalIndicados = User::where('inviter', $this->record->id)
            ->where('is_demo_agent', 0) // Excluir contas demo
            ->count();

        // Número de indicados que são afiliados ativos (possuem link ativo)
        $indicadosAfiliados = User::where('inviter', $this->record->id)
            ->where('is_demo_agent', 0) // Excluir contas demo
            ->where(function ($query) {
                // Um usuário é considerado afiliado ativo se tiver código de convite
                // e tiver configurações de afiliado (revshare ou CPA) ativas
                $query->whereNotNull('inviter_code')
                    ->where(function ($q) {
                        $q->where('affiliate_revenue_share', '>', 0)
                            ->orWhere('affiliate_cpa', '>', 0);
                    });
            })
            ->count();

        // Total de depósitos dos indicados
        $indicadosIds = User::where('inviter', $this->record->id)
            ->where('is_demo_agent', 0)
            ->pluck('id');

        $totalDepositosIndicados = DB::table('deposits')
            ->whereIn('user_id', $indicadosIds)
            ->where('status', 1)
            ->sum('amount');

        // Detalhes dos primeiros depósitos
        $primeiroDepositoIndicados = DB::table('deposits as d1')
            ->whereIn('d1.user_id', $indicadosIds)
            ->where('d1.status', 1)
            ->whereNotExists(function ($query) {
                $query->from('deposits as d2')
                    ->whereRaw('d1.user_id = d2.user_id')
                    ->whereRaw('d2.created_at < d1.created_at')
                    ->where('d2.status', 1);
            })
            ->sum('d1.amount');

        // Contar indicados que fizeram primeiro depósito
        $indicadosComPrimeiroDeposito = DB::table('deposits as d1')
            ->whereIn('d1.user_id', $indicadosIds)
            ->where('d1.status', 1)
            ->whereNotExists(function ($query) {
                $query->from('deposits as d2')
                    ->whereRaw('d1.user_id = d2.user_id')
                    ->whereRaw('d2.created_at < d1.created_at')
                    ->where('d2.status', 1);
            })
            ->distinct()
            ->count('d1.user_id');

        // Para debug: Vamos logar os detalhes
        Log::info('Detalhes dos depósitos do afiliado '.$this->record->id, [
            'total_indicados' => $totalIndicados,
            'ids_indicados' => $indicadosIds,
            'total_depositos' => $totalDepositosIndicados,
            'total_primeiros_depositos' => $primeiroDepositoIndicados,
            'indicados_com_primeiro_deposito' => $indicadosComPrimeiroDeposito,
        ]);

        $colorRevshare = $totalAfiliadosRevshare >= 0 ? 'success' : 'danger';
        $iconRevshare = $totalAfiliadosRevshare >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return [
            Stat::make('Total de Indicados', number_format($totalIndicados, 0, ',', '.'))
                ->description('Número de usuários indicados')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            Stat::make('Indicados Afiliados Ativos', number_format($indicadosAfiliados, 0, ',', '.'))
                ->description('Indicados com link de afiliação ativo')
                ->descriptionIcon('heroicon-m-link')
                ->color('primary'),
            Stat::make('Indicados com Primeiro Depósito', Helper::formatNumber($indicadosComPrimeiroDeposito))
                ->description('Número de indicados que fizeram depósito')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('Total de Depósitos (Indicados)', Helper::amountFormatDecimal($totalDepositosIndicados))
                ->description('Soma de todos os depósitos dos indicados')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Primeiro Depósito (Indicados)', Helper::amountFormatDecimal($primeiroDepositoIndicados))
                ->description('Soma dos primeiros depósitos dos indicados')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total de Ganhos', Helper::amountFormatDecimal($totalGanhos))
                ->description('Total de Ganhos na plataforma')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total de Perdas', Helper::amountFormatDecimal($totalPerdas))
                ->description('Total de Perdas na plataforma')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Total Sacado', Helper::amountFormatDecimal($totalSacado))
                ->description('Total de saques realizados')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make('Ganhos Revshare', Helper::amountFormatDecimal($totalAfiliadosRevshare))
                ->description('Ganhos como afiliado (Revshare)')
                ->descriptionIcon($iconRevshare)
                ->color($colorRevshare),
            Stat::make('Ganhos CPA', Helper::amountFormatDecimal($totalAfiliadosCPA))
                ->description('Ganhos como afiliado (CPA)')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Disponível para Saque (Afiliado)', Helper::amountFormatDecimal($saldoAfiliadoSaque))
                ->description('Saldo disponível para saque como afiliado')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Link de Indicação', $this->record->inviter_code ? 'Ativo' : 'Inativo')
                ->description($this->record->inviter_code ?
                    config('app.url').'/register?code='.$this->record->inviter_code :
                    'Usuário não possui código de indicação'
                )
                ->descriptionIcon('heroicon-m-link')
                ->color($this->record->inviter_code ? 'success' : 'gray')
                ->extraAttributes([
                    'style' => $this->record->inviter_code ? 'cursor: pointer;' : '',
                    'onclick' => $this->record->inviter_code ?
                        "navigator.clipboard.writeText('".config('app.url').'/register?code='.$this->record->inviter_code."').then(() => alert('Link copiado!'))" :
                        '',
                ]),
        ];
    }
}
