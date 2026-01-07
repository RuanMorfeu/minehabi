<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\File;

class MinesBotConfig extends Page implements HasForms
{
    use HasPageSidebar;
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.mines-bot-config';

    public Setting $record;

    public function getTitle(): string
    {
        return 'Configurações do Bot Mines';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->record = Setting::first();

        $configPath = base_path('bots/mines/config.json');

        if (File::exists($configPath)) {
            $config = json_decode(File::get($configPath), true);
            $this->form->fill([
                'telegram_bot_token' => $config['telegram']['bot_token'] ?? '',
                'telegram_chat_id' => $config['telegram']['chat_id'] ?? '',
                'laravel_api_url' => $config['api']['laravel_url'] ?? '',
            ]);
        }
    }

    public function save()
    {
        $data = $this->form->getState();

        $config = [
            'telegram' => [
                'bot_token' => $data['telegram_bot_token'],
                'chat_id' => $data['telegram_chat_id'],
            ],
            'api' => [
                'laravel_url' => $data['laravel_api_url'],
            ],
        ];

        $configPath = base_path('bots/mines/config.json');
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title('Configurações salvas')
            ->body('As configurações do bot foram atualizadas com sucesso!')
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configurações do Telegram')
                    ->description('Configure o token e chat ID do bot do Telegram')
                    ->schema([
                        TextInput::make('telegram_bot_token')
                            ->label('Token do Bot Telegram')
                            ->required()
                            ->password(),
                        TextInput::make('telegram_chat_id')
                            ->label('Chat ID')
                            ->required()
                            ->helperText('ID do canal ou grupo onde o bot enviará as mensagens'),
                    ]),

                Section::make('Configurações da API')
                    ->description('URL da API Laravel para verificação de status')
                    ->schema([
                        TextInput::make('laravel_api_url')
                            ->label('URL da API Laravel')
                            ->required()
                            ->url()
                            ->default('https://dei.bet/api/bot/mines/status'),
                    ]),
            ])
            ->statePath('data');
    }
}
