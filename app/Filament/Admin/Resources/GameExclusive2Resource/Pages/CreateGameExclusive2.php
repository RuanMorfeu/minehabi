<?php

namespace App\Filament\Admin\Resources\GameExclusive2Resource\Pages;

use App\Filament\Admin\Resources\GameExclusive2Resource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameExclusive2 extends CreateRecord
{
    protected static string $resource = GameExclusive2Resource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
