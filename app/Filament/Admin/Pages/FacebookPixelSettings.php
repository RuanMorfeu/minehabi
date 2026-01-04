<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class FacebookPixelSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationLabel = 'Facebook Pixel';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Configurações do Facebook Pixel';

    protected static string $view = 'filament.admin.pages.facebook-pixel-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::first();

        $this->data = [
            'facebook_pixel_id' => $setting->facebook_pixel_id ?? '',
            'facebook_access_token' => $setting->facebook_access_token ?? '',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Facebook Pixel')
                    ->description('Configurações do Facebook Pixel para rastreamento de conversões')
                    ->schema([
                        TextInput::make('facebook_pixel_id')
                            ->label('Pixel ID')
                            ->placeholder('Ex: 641305108716070')
                            ->helperText('ID do Pixel do Facebook para rastreamento de eventos')
                            ->maxLength(191)
                            ->required(),
                        TextInput::make('facebook_access_token')
                            ->label('Access Token')
                            ->placeholder('Ex: EAAO9hYqUMOYBO428jfPpkLxvSrapZAfFe...')
                            ->helperText('Token de acesso para a API do Facebook (Conversions API)')
                            ->password()
                            ->revealable(true)
                            ->maxLength(255)
                            ->required(),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            $setting = Setting::first();

            if (! empty($setting)) {
                if ($setting->update($this->data)) {
                    Cache::forget('setting');
                    Cache::put('setting', $setting);

                    Notification::make()
                        ->title('Configurações salvas')
                        ->body('Configurações do Facebook Pixel foram salvas com sucesso!')
                        ->success()
                        ->send();

                    redirect(route('filament.admin.pages.facebook-pixel-settings'));
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao salvar configurações')
                ->body('Ocorreu um erro ao salvar as configurações: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Salvar Configurações')
                ->action(fn () => $this->submit())
                ->submit('submit'),
        ];
    }
}
