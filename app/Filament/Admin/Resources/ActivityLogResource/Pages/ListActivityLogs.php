<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analyze_ips')
                ->label('Analisar IPs')
                ->url('/admin/ip-analysis')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->openUrlInNewTab(),
        ];
    }
}
