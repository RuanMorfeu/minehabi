<?php

namespace App\Filament\Admin\Resources\GameExclusive2Resource\Pages;

use App\Filament\Admin\Resources\GameExclusive2Resource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameExclusive2 extends EditRecord
{
    protected static string $resource = GameExclusive2Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
