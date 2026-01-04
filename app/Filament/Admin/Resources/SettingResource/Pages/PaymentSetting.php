<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Game;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentSetting extends Page implements HasForms
{
    use HasPageSidebar;
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.payment-setting';

    /*** @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return __('Pagamentos');
    }

    public Setting $record;

    public ?array $data = [];

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $setting = Setting::first();
        $this->record = $setting;
        $this->form->fill($setting->toArray());
    }

    /**
     * @return void
     */
    public function save()
    {
        try {
            if (env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar está alteração na versão demo')
                    ->danger()
                    ->send();

                return;
            }

            // Tratar campos vazios como nulos
            $freespinFields = [
                // Categorias de Freespin para Primeiro Depósito
                'amount_rounds_free_deposit_cat1_min', 'amount_rounds_free_deposit_cat1_max', 'rounds_free_deposit_cat1',
                'amount_rounds_free_deposit_cat2_min', 'amount_rounds_free_deposit_cat2_max', 'rounds_free_deposit_cat2',
                'amount_rounds_free_deposit_cat3_min', 'amount_rounds_free_deposit_cat3_max', 'rounds_free_deposit_cat3',
                'amount_rounds_free_deposit_cat4_min', 'amount_rounds_free_deposit_cat4_max', 'rounds_free_deposit_cat4',

                // Categorias de Freespin para Depósitos Subsequentes
                'amount_rounds_free_any_deposit_cat1_min', 'amount_rounds_free_any_deposit_cat1_max', 'rounds_free_any_deposit_cat1',
                'amount_rounds_free_any_deposit_cat2_min', 'amount_rounds_free_any_deposit_cat2_max', 'rounds_free_any_deposit_cat2',
                'amount_rounds_free_any_deposit_cat3_min', 'amount_rounds_free_any_deposit_cat3_max', 'rounds_free_any_deposit_cat3',
                'amount_rounds_free_any_deposit_cat4_min', 'amount_rounds_free_any_deposit_cat4_max', 'rounds_free_any_deposit_cat4',
            ];

            foreach ($freespinFields as $field) {
                if (isset($this->data[$field]) && $this->data[$field] === '') {
                    $this->data[$field] = null;
                }
            }

            $setting = Setting::find($this->record->id);

            if ($setting->update($this->data)) {
                Cache::put('setting', $setting);

                Notification::make()
                    ->title('Dados alterados')
                    ->body('Dados alterados com sucesso!')
                    ->success()
                    ->send();

                redirect(route('filament.admin.resources.settings.payment', ['record' => $this->record->id]));

            }
        } catch (Halt $exception) {
            return;
        }
    }

    public function form(Form $form): Form
    {
        $games = Game::pluck('game_code', 'game_code');

        return $form
            ->schema([
                Section::make('Configuração de Giros Grátis')
                    ->description('Configure o nome do jogo que será exibido nas mensagens de giros grátis')
                    ->schema([
                        TextInput::make('freespin_game_name')
                            ->label('Nome do Jogo para Exibição')
                            ->placeholder('Ex: Sweet Bonanza')
                            ->helperText('Nome amigável do jogo que será exibido para o usuário na mensagem de giros grátis. Este nome será usado para todos os tipos de giros grátis.')
                            ->maxLength(191),
                        Toggle::make('show_freespin_badges')
                            ->label('Exibir Balões de Giros Grátis')
                            ->helperText('Quando ativado, exibe pequenos balões indicando o número de giros grátis nos botões de seleção de valores de depósito.')
                            ->default(true),
                    ])
                    ->collapsible(),

                Section::make('Ajuste de Taxas')
                    ->description('Formulário ajustar as taxas da plataforma')
                    ->schema([
                        TextInput::make('min_deposit')
                            ->label('Min Deposito')
                            ->numeric()
                            ->maxLength(191),
                        TextInput::make('max_deposit')
                            ->label('Max Deposito')
                            ->numeric()
                            ->maxLength(191),
                        TextInput::make('min_withdrawal')
                            ->label('Min Saque')
                            ->numeric()
                            ->maxLength(191),
                        TextInput::make('max_withdrawal')
                            ->label('Max Saque')
                            ->numeric()
                            ->maxLength(191),
                        TextInput::make('initial_bonus')
                            ->label('Bônus Primeiro Depósito')
                            ->numeric()
                            ->suffix('%')
                            ->maxLength(191),
                        TextInput::make('second_deposit_bonus')
                            ->label('Bônus Segundo Depósito')
                            ->numeric()
                            ->suffix('%')
                            ->maxLength(191)
                            ->helperText('Porcentagem do bônus para o segundo depósito'),
                        Toggle::make('second_deposit_bonus_active')
                            ->label('Ativar Bônus Segundo Depósito')
                            ->helperText('Ative ou desative o bônus para o segundo depósito')
                            ->inline(false),
                        TextInput::make('currency_code')
                            ->label('Moeda')
                            ->maxLength(191),
                    ])->columns(2),
                Section::make('Rodadas Grátis por Primeiro Depósito')
                    ->description('Configure as rodadas grátis que o usuário ganha ao realizar o primeiro depósito, com categorias baseadas no valor depositado em euros.')
                    ->schema([
                        Checkbox::make('game_free_rounds_active_deposit')
                            ->label('Ativar Rodadas Grátis por Primeiro Depósito'),

                        Select::make('game_code_rounds_free_deposit')
                            ->label('Jogo para Rodadas de Primeiro Depósito')
                            ->options($games)
                            ->searchable()
                            ->required(),

                        Section::make('Categoria 1 - Primeiro Depósito')
                            ->schema([
                                TextInput::make('amount_rounds_free_deposit_cat1_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 1 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_deposit_cat1_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 1 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_deposit_cat1')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 1.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 2 - Primeiro Depósito')
                            ->schema([
                                TextInput::make('amount_rounds_free_deposit_cat2_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 2 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_deposit_cat2_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 2 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_deposit_cat2')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 2.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 3 - Primeiro Depósito')
                            ->schema([
                                TextInput::make('amount_rounds_free_deposit_cat3_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 3 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_deposit_cat3_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 3 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_deposit_cat3')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 3.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 4 - Primeiro Depósito')
                            ->schema([
                                TextInput::make('amount_rounds_free_deposit_cat4_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 4 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_deposit_cat4_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 4 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_deposit_cat4')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 4.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),
                    ])
                    ->columns(2),

                Section::make('Rodadas Grátis para Segundo Depósito em Diante')
                    ->description('Configure as rodadas grátis que o usuário ganha ao realizar depósitos a partir do segundo depósito, com categorias baseadas no valor depositado em euros.')
                    ->schema([
                        Checkbox::make('game_free_rounds_active_any_deposit')
                            ->label('Ativar Rodadas Grátis a partir do Segundo Depósito'),

                        Select::make('game_code_rounds_free_any_deposit')
                            ->label('Jogo para Rodadas a partir do Segundo Depósito')
                            ->options($games)
                            ->searchable()
                            ->required(),

                        Section::make('Categoria 1 - Depósitos Subsequentes')
                            ->schema([
                                TextInput::make('amount_rounds_free_any_deposit_cat1_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 1 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_any_deposit_cat1_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 1 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_any_deposit_cat1')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 1.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 2 - Depósitos Subsequentes')
                            ->schema([
                                TextInput::make('amount_rounds_free_any_deposit_cat2_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 2 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_any_deposit_cat2_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 2 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_any_deposit_cat2')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 2.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 3 - Depósitos Subsequentes')
                            ->schema([
                                TextInput::make('amount_rounds_free_any_deposit_cat3_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 3 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_any_deposit_cat3_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 3 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_any_deposit_cat3')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 3.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),

                        Section::make('Categoria 4 - Depósitos Subsequentes')
                            ->schema([
                                TextInput::make('amount_rounds_free_any_deposit_cat4_min')
                                    ->label('Valor Mínimo (€)')
                                    ->helperText('Valor mínimo da margem para a categoria 4 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('amount_rounds_free_any_deposit_cat4_max')
                                    ->label('Valor Máximo (€)')
                                    ->helperText('Valor máximo da margem para a categoria 4 de freespins.')
                                    ->numeric()
                                    ->nullable()
                                    ->suffix('€')
                                    ->maxLength(191),

                                TextInput::make('rounds_free_any_deposit_cat4')
                                    ->label('Quantidade de Rodadas')
                                    ->helperText('Número de rodadas grátis para a categoria 4.')
                                    ->numeric()
                                    ->nullable()
                                    ->maxLength(191),
                            ])
                            ->columns(3)
                            ->collapsed(),
                    ])
                    ->columns(2),

                Section::make('Rodadas Grátis por Registro')
                    ->description('Configure as rodadas grátis que o usuário ganha ao se registrar.')
                    ->schema([
                        TextInput::make('rounds_free_register')
                            ->label('Quantidade de Rodadas')
                            ->helperText('Número de rodadas grátis concedidas ao novo usuário no registro.')
                            ->numeric()
                            ->nullable()
                            ->maxLength(191),

                        Select::make('game_code_rounds_free_register')
                            ->label('Jogo para Rodadas de Registro')
                            ->options($games)
                            ->searchable()
                            ->required(),

                        Checkbox::make('game_free_rounds_active_register')
                            ->label('Ativar Rodadas Grátis por Registro'),

                        Checkbox::make('initial_credit_active')
                            ->label('Adicionar Crédito Inicial')
                            ->helperText('Quando ativado, adiciona um crédito inicial à carteira do usuário ao se registrar.'),

                        TextInput::make('initial_credit_amount')
                            ->label('Valor do Crédito Inicial (€)')
                            ->helperText('Valor em euros a ser adicionado à carteira do usuário ao se registrar.')
                            ->numeric()
                            ->nullable()
                            ->suffix('€')
                            ->default(0.01)
                            ->maxLength(191),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }
}
