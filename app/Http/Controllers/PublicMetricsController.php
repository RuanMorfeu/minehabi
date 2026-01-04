<?php

namespace App\Http\Controllers;

use App\Models\AffiliateHistory;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicMetricsController extends Controller
{
    /**
     * Exibe a página pública de métricas do cassino
     */
    public function show(Request $request)
    {
        // Obter filtros de data
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $dataAtual = Carbon::now();
        $today = Carbon::today();

        // Consultas base
        $depositQuery = Deposit::query()->where('status', 1);
        $withdrawalQuery = Withdrawal::query()->where('status', 1);
        $revshareQuery = AffiliateHistory::where('commission_type', 'revshare');
        $cpaQuery = AffiliateHistory::where('commission_type', 'cpa');

        // Aplicar filtros de data
        if ($startDate && $endDate) {
            $depositQuery->whereBetween('created_at', [$startDate, $endDate]);
            $withdrawalQuery->whereBetween('created_at', [$startDate, $endDate]);
            $revshareQuery->whereBetween('created_at', [$startDate, $endDate]);
            $cpaQuery->whereBetween('created_at', [$startDate, $endDate]);
            $periodoFormatado = $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y');
        } else {
            // Filtrar pelo mês atual por padrão
            $depositQuery->whereMonth('created_at', $dataAtual->month)->whereYear('created_at', $dataAtual->year);
            $withdrawalQuery->whereMonth('created_at', $dataAtual->month)->whereYear('created_at', $dataAtual->year);
            $revshareQuery->whereMonth('created_at', $dataAtual->month)->whereYear('created_at', $dataAtual->year);
            $cpaQuery->whereMonth('created_at', $dataAtual->month)->whereYear('created_at', $dataAtual->year);
            $periodoFormatado = $dataAtual->translatedFormat('F Y');
        }

        // Calcular métricas
        $sumDepositMonth = $depositQuery->sum('amount');
        $sumWithdrawalMonth = $withdrawalQuery->sum('amount');
        $revshare = $revshareQuery->sum('commission_paid');
        $cpaCommission = $cpaQuery->sum('commission_paid');

        // Total de apostas e ganhos
        $totalApostas = Order::whereIn('type', ['bet', 'loss']);
        $totalWins = Order::where('type', 'win');

        if ($startDate && $endDate) {
            $totalApostas->whereBetween('created_at', [$startDate, $endDate]);
            $totalWins->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalApostasValue = $totalApostas->sum('amount');
        $totalWinsValue = $totalWins->sum('amount');

        // Saldo dos jogadores
        $saldoJogadores = DB::table('users')->join('wallets', function ($join) {
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

        // Depósitos e saques de hoje
        $totalDepositedToday = Deposit::where('created_at', '>=', $today->copy()->startOfDay())
            ->where('created_at', '<=', $today->copy()->endOfDay())
            ->where('status', 1)
            ->sum('amount');

        $totalWithdrawnToday = Withdrawal::where('created_at', '>=', $today->copy()->startOfDay())
            ->where('created_at', '<=', $today->copy()->endOfDay())
            ->where('status', 1)
            ->sum('amount');

        // Ganhos de afiliados a pagar
        $totalReferRewards = Wallet::join('users', 'users.id', '=', 'wallets.user_id')
            ->where('users.id', '!=', 1)
            ->where('users.is_demo_agent', 0)
            ->where('wallets.refer_rewards', '>', 0)
            ->sum('wallets.refer_rewards');

        // Usuários por número de depósitos
        $depositCounts = DB::table('deposits')
            ->select('user_id', DB::raw('count(*) as deposit_count'))
            ->where('status', 1)
            ->groupBy('user_id')
            ->get();

        $usersWithSingleDeposit = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 1;
        })->count();

        $usersWithTwoDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 2;
        })->count();

        $usersWithThreeDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count === 3;
        })->count();

        $usersWithFourOrMoreDeposits = $depositCounts->filter(function ($item) {
            return $item->deposit_count >= 4;
        })->count();

        // Usuários depositantes orgânicos vs indicados
        $depositantesOrganicos = User::whereNull('inviter')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deposits')
                    ->whereColumn('deposits.user_id', 'users.id')
                    ->where('deposits.status', 1);
            })
            ->where('is_demo_agent', 0)
            ->count();

        $depositantesIndicados = User::whereNotNull('inviter')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deposits')
                    ->whereColumn('deposits.user_id', 'users.id')
                    ->where('deposits.status', 1);
            })
            ->where('is_demo_agent', 0)
            ->count();

        // Total de usuários registrados
        $totalUsers = User::count();

        // Total de usuários que fizeram depósito
        $totalDepositingUsers = Deposit::where('status', 1)
            ->distinct('user_id')
            ->count('user_id');

        return view('public.metrics', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sumDepositMonth' => $sumDepositMonth,
            'sumWithdrawalMonth' => $sumWithdrawalMonth,
            'revshare' => $revshare,
            'cpaCommission' => $cpaCommission,
            'totalUsers' => $totalUsers,
            'totalDepositingUsers' => $totalDepositingUsers,
            'totalApostas' => $totalApostasValue,
            'totalWins' => $totalWinsValue,
            'saldoJogadores' => $saldoJogadores,
            'saldoSacavel' => $saldoSacavel,
            'totalDepositedToday' => $totalDepositedToday,
            'totalWithdrawnToday' => $totalWithdrawnToday,
            'totalReferRewards' => $totalReferRewards,
            'usersWithSingleDeposit' => $usersWithSingleDeposit,
            'usersWithTwoDeposits' => $usersWithTwoDeposits,
            'usersWithThreeDeposits' => $usersWithThreeDeposits,
            'usersWithFourOrMoreDeposits' => $usersWithFourOrMoreDeposits,
            'depositantesOrganicos' => $depositantesOrganicos,
            'depositantesIndicados' => $depositantesIndicados,
            'periodoFormatado' => $periodoFormatado,
        ]);
    }
}
