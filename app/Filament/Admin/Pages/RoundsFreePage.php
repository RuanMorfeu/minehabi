<?php

namespace App\Filament\Admin\Pages;

use App\Models\Game;
use App\Models\GamesKey;
use App\Models\User;
use App\Services\PlayFiverService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class RoundsFreePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.rounds-free-page';

    protected static ?string $title = 'Rodadas Grátis';

    protected static ?string $slug = 'rounds-free';

    /**
     * @dev
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin'); // Controla o acesso total à página
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('admin'); // Controla a visualização de elementos específicos
    }

    public ?array $data = [];

    public ?GamesKey $setting;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $users = User::pluck('email', 'email');
        $games = Game::pluck('game_name', 'game_code');

        return $form
            ->schema([
                Section::make('Rodadas Grátis')
                    ->schema([
                        Select::make('email')
                            ->label('Player')
                            ->options($users)
                            ->searchable()
                            ->required(),
                        Select::make('game_code')
                            ->label('Jogo')
                            ->options($games)
                            ->searchable()
                            ->required(),
                        TextInput::make('rounds')
                            ->label('Quantidade de rodadas')
                            ->placeholder('Digite aqui a quantidade de rodadas')
                            ->numeric()
                            ->rules(['min:1', 'max:30'])
                            ->required()

                            ->maxLength(191),

                    ])->columns(3),

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

            $dados = [
                'username' => $this->data['email'],
                'game_code' => $this->data['game_code'],
                'rounds' => $this->data['rounds'],
            ];
            $roundsFree = PlayFiverService::RoundsFree($dados);
            if ($roundsFree['status']) {
                Notification::make()
                    ->title('Rodadas grátis')
                    ->body('As rodadas grátis foram agendadas.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Rodadas grátis')
                    ->body($roundsFree['message'])
                    ->danger()
                    ->send();
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
