<?php

namespace App\Filament\Admin\Resources\GameExclusive2Resource\Pages;

use App\Filament\Admin\Resources\GameExclusive2Resource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGameExclusive2 extends ViewRecord
{
    protected static string $resource = GameExclusive2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
