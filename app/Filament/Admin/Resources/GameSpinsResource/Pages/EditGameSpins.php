<?php

namespace App\Filament\Admin\Resources\GameSpinsResource\Pages;

use App\Filament\Admin\Resources\GameSpinsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameSpins extends EditRecord
{
    protected static string $resource = GameSpinsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
