<?php

namespace App\Filament\Admin\Resources\InfluencerBonusResource\Pages;

use App\Filament\Admin\Resources\InfluencerBonusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInfluencerBonus extends EditRecord
{
    protected static string $resource = InfluencerBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Excluir')
                ->modalHeading('Excluir bônus de influencer')
                ->modalDescription('Tem certeza de que deseja excluir este bônus? Esta ação não pode ser desfeita.')
                ->modalSubmitActionLabel('Sim, excluir')
                ->successNotificationTitle('Bônus excluído com sucesso!'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Bônus de influencer atualizado com sucesso!';
    }
}
