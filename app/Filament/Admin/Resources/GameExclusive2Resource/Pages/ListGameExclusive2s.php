<?php

namespace App\Filament\Admin\Resources\GameExclusive2Resource\Pages;

use App\Filament\Admin\Resources\GameExclusive2Resource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameExclusive2s extends ListRecords
{
    protected static string $resource = GameExclusive2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aqui você pode adicionar widgets se necessário
        ];
    }
}
