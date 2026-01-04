<?php

namespace App\Filament\Admin\Widgets;

use App\Models\UserAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KycStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // Estatísticas unificadas de Verificação KYC
        $totalVerifications = UserAccount::count();
        $pendingVerifications = UserAccount::where('status', 'pending')->count();
        $approvedVerifications = UserAccount::where('status', 'approved')->count();
        $rejectedVerifications = UserAccount::where('status', 'rejected')->count();

        // Cálculo de percentuais
        $approvalRate = $totalVerifications > 0 ? round(($approvedVerifications / $totalVerifications) * 100, 1) : 0;
        $rejectionRate = $totalVerifications > 0 ? round(($rejectedVerifications / $totalVerifications) * 100, 1) : 0;

        // Estatísticas de documentos enviados
        $verificationsWithDocuments = UserAccount::whereHas('user.userDocument')->count();
        $documentsRate = $totalVerifications > 0 ? round(($verificationsWithDocuments / $totalVerifications) * 100, 1) : 0;

        return [
            Stat::make('Verificações Pendentes', $pendingVerifications)
                ->description('Aguardando análise')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Verificações Aprovadas', $approvedVerifications)
                ->description("Taxa: {$approvalRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([15, 4, 10, 22, 15, 4, 12]),

            Stat::make('Verificações Rejeitadas', $rejectedVerifications)
                ->description("Taxa: {$rejectionRate}%")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([2, 1, 4, 2, 1, 3, 2]),

            Stat::make('Total de Verificações', $totalVerifications)
                ->description('Dados pessoais enviados')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('primary')
                ->chart([20, 8, 15, 25, 20, 12, 18]),

            Stat::make('Com Documentos', $verificationsWithDocuments)
                ->description("Taxa: {$documentsRate}%")
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info')
                ->chart([12, 6, 14, 18, 16, 8, 15]),
        ];
    }
}
