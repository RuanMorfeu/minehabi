<?php

namespace App\Filament\Admin\Resources\UserAccountResource\Pages;

use App\Filament\Admin\Resources\UserAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserAccount extends EditRecord
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
