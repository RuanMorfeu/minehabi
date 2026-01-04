<?php

namespace App\Filament\Admin\Resources\SmsSettingResource\Pages;

use App\Filament\Admin\Resources\SmsSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSmsSettings extends ManageRecords
{
    protected static string $resource = SmsSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
