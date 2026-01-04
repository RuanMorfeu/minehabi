<?php

namespace App\Filament\Admin\Pages;

use App\Models\GamesKey;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;

class GamesKeyPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.games-key-page';

    protected static ?string $title = 'PLAY FIVER API';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public ?array $data = [];

    public ?GamesKey $setting;

    public function mount(): void
    {
        $gamesKey = GamesKey::first();
        if (! empty($gamesKey)) {
            $this->setting = $gamesKey;
            $this->form->fill($this->setting->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Play Fiver')
                    ->description(new HtmlString('
                        <div style="display: flex; align-items: center;">
                            Acesse o painel da API e faça uma recarga:
                            <a class="dark:text-white" 
                               style="
                                   width: 137px; 
                                   display: flex; 
                                   background-color: #A000EC; 
                                   padding: 10px; 
                                   border-radius: 20px; 
                                   justify-content: center; 
                                   margin-left: 10px;
                               " 
                               href="https://playfiver.com" 
                               target="_blank">
                                Recarregar API
                            </a>
                        </div>
                    '))
                    ->schema([
                        TextInput::make('playfiver_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui o código secreto do agente')
                            ->maxLength(191),
                        TextInput::make('playfiver_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o código do agente')
                            ->maxLength(191),
                        TextInput::make('playfiver_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o token do agente')
                            ->maxLength(191),
                    ])->columns(3),

                Section::make('AGGR API')
                    ->description('Ajustes de credenciais para a AGGR')
                    ->schema([
                        TextInput::make('agentApi')
                            ->label('Agent API')
                            ->placeholder('Digite aqui o Agent API')
                            ->maxLength(191),
                        TextInput::make('agentPassword')
                            ->label('Agent Password')
                            ->placeholder('Digite aqui o Agent Password')
                            ->maxLength(191),
                        TextInput::make('agentApi')
                            ->label('Endpoint')
                            ->placeholder('Digite aqui o Endpoint')
                            ->maxLength(191),
                    ])
                    ->columns(3),

                Section::make('Drakon API')
                    ->description(new HtmlString('Compre direto pelo site: <a href="https://gator.drakon.casino" target="_blank" style="color: red;">gator.drakon.casino</a> Telegram: <a href="https://t.me/drakongator" target="_blank" style="color: red;">@drakonsuporte</a>'))
                    ->schema([
                        TextInput::make('drakon_agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('drakon_agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('drakon_agent_secret')
                            ->label('Agent Secret')
                            ->placeholder('Digite aqui a Agente Secret')
                            ->maxLength(191),
                    ])
                    ->columns(3),

                Section::make('Fivers API')
                    ->description('Ajustes de credenciais para a Fivers')
                    ->schema([
                        TextInput::make('agent_code')
                            ->label('Agent Code')
                            ->placeholder('Digite aqui o Agent Code')
                            ->maxLength(191),
                        TextInput::make('agent_token')
                            ->label('Agent Token')
                            ->placeholder('Digite aqui o Agent Token')
                            ->maxLength(191),
                        TextInput::make('agent_secret_key')
                            ->label('Agent Secret Key')
                            ->placeholder('Digite aqui o Agent Secret Key')
                            ->maxLength(191),
                        TextInput::make('api_endpoint')
                            ->label('Api Endpoint')
                            ->placeholder('Digite aqui a API Endpoint')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),

                Section::make('TBS API')
                    ->description(new HtmlString('Configurações essenciais da API TBS para sincronização de jogos Evolution, Spribe e Galaxsys'))
                    ->schema([
                        TextInput::make('tbs_hall')
                            ->label('Hall ID')
                            ->placeholder('Ex: 3207131')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('tbs_key')
                            ->label('API Key')
                            ->placeholder('Ex: 1qa2wszxc')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('tbs_endpoint')
                            ->label('API Endpoint')
                            ->placeholder('Ex: https://tbs2api.lvslot.net/API/openGame/')
                            ->required()
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar esta alteração na versão demo')
                    ->danger()
                    ->send();

                return;
            }

            $setting = GamesKey::first();
            if (! empty($setting)) {
                if ($setting->update($this->data)) {
                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            } else {
                if (GamesKey::create($this->data)) {
                    Notification::make()
                        ->title('Chaves Criadas')
                        ->body('Suas chaves foram criadas com sucesso!')
                        ->success()
                        ->send();
                }
            }
        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Erro ao alterar dados!')
                ->danger()
                ->send();
        }
    }
}
