<?php

namespace App\Filament\Admin\Resources\GameExclusiveResource\Pages;

use App\Filament\Admin\Resources\GameExclusiveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameExclusive extends EditRecord
{
    protected static string $resource = GameExclusiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
