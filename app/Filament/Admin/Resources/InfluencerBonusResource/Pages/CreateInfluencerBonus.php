<?php

namespace App\Filament\Admin\Resources\InfluencerBonusResource\Pages;

use App\Filament\Admin\Resources\InfluencerBonusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInfluencerBonus extends CreateRecord
{
    protected static string $resource = InfluencerBonusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Bônus de influencer criado com sucesso!';
    }
}
