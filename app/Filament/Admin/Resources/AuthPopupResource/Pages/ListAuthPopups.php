<?php

namespace App\Filament\Admin\Resources\AuthPopupResource\Pages;

use App\Filament\Admin\Resources\AuthPopupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuthPopups extends ListRecords
{
    protected static string $resource = AuthPopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
