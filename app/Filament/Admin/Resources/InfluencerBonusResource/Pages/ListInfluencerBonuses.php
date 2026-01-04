<?php

namespace App\Filament\Admin\Resources\InfluencerBonusResource\Pages;

use App\Filament\Admin\Resources\InfluencerBonusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInfluencerBonuses extends ListRecords
{
    protected static string $resource = InfluencerBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Bônus')
                ->icon('heroicon-o-plus'),
        ];
    }
}
