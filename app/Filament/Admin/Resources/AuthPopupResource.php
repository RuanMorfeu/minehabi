<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuthPopupResource\Pages;
use App\Models\AuthPopup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuthPopupResource extends Resource
{
    protected static ?string $model = AuthPopup::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'Pop-up de Autenticação';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pop-ups de Autenticação';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label('Mensagem')
                            ->required()
                            ->rows(4),

                        Forms\Components\FileUpload::make('image')
                            ->label('Imagem')
                            ->image()
                            ->directory('popups')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('button_text')
                            ->label('Texto do Botão')
                            ->default('Entendi')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('redirect_url')
                            ->label('URL de Redirecionamento')
                            ->url()
                            ->helperText('Se preenchido, o usuário será redirecionado para esta URL ao clicar no botão. Deixe em branco para apenas fechar o pop-up.')
                            ->placeholder('https://exemplo.com/pagina-destino')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configurações de Exibição')
                    ->schema([
                        Forms\Components\Toggle::make('show_after_login')
                            ->label('Mostrar após login')
                            ->default(true)
                            ->helperText('O pop-up será exibido quando o usuário fizer login'),

                        Forms\Components\Toggle::make('show_after_register')
                            ->label('Mostrar após registro')
                            ->default(false)
                            ->helperText('O pop-up será exibido quando um novo usuário se registrar'),

                        Forms\Components\Toggle::make('show_only_once')
                            ->label('Mostrar apenas uma vez')
                            ->default(false)
                            ->helperText('O pop-up será exibido apenas uma vez para cada usuário'),

                        Forms\Components\Toggle::make('require_redemption')
                            ->label('Exigir resgate')
                            ->default(false)
                            ->helperText('O pop-up será exibido repetidamente até que o usuário clique no botão'),

                        Forms\Components\Toggle::make('browser_persistent')
                            ->label('Persistência por Navegador')
                            ->default(false)
                            ->helperText('Se ativado, o pop-up será marcado como visto/resgatado para o navegador, independentemente do usuário.'),

                        Forms\Components\Toggle::make('active')
                            ->label('Ativo')
                            ->default(true)
                            ->helperText('Ative ou desative este pop-up'),

                        Forms\Components\Select::make('target_user_type')
                            ->label('Tipo de Usuário Alvo')
                            ->options([
                                'all' => 'Todos os usuários',
                                'new' => 'Apenas novos usuários',
                                'existing' => 'Apenas usuários existentes',
                                'with_deposit' => 'Usuários com depósito realizado',
                                'without_deposit' => 'Usuários sem depósito realizado',
                                'affiliate' => 'Apenas afiliados com link',
                            ])
                            ->default('all')
                            ->required(),

                        Forms\Components\TextInput::make('influencer_code')
                            ->label('Código do Influencer')
                            ->helperText('Se preenchido, o pop-up será exibido apenas para usuários que vieram deste influencer')
                            ->placeholder('Deixe em branco para todos os usuários')
                            ->maxLength(50),

                        Forms\Components\Toggle::make('require_influencer_match')
                            ->label('Exigir correspondência exata do influencer')
                            ->helperText('Se ativado, o pop-up só será exibido para usuários que vieram exatamente do influencer especificado acima')
                            ->default(false)
                            ->visible(fn (Forms\Get $get): bool => ! empty($get('influencer_code'))),

                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Data de Início')
                            ->helperText('Deixe em branco para começar imediatamente'),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Data de Término')
                            ->helperText('Deixe em branco para não ter data de término')
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configurações de Freespin')
                    ->schema([
                        Forms\Components\Toggle::make('game_free_rounds_active_popup')
                            ->label('Ativar Freespin no Pop-up')
                            ->default(false)
                            ->helperText('Quando ativado, o usuário receberá rodadas grátis ao clicar no botão do pop-up'),

                        Forms\Components\Select::make('game_code_rounds_free_popup')
                            ->label('Jogo para Freespin')
                            ->options(\App\Models\Game::pluck('game_code', 'game_code'))
                            ->searchable()
                            ->helperText('Selecione o jogo onde serão concedidas as rodadas gratuitas')
                            ->visible(fn (Forms\Get $get): bool => $get('game_free_rounds_active_popup')),

                        Forms\Components\TextInput::make('game_name_rounds_free_popup')
                            ->label('Nome do Jogo para Exibição')
                            ->placeholder('Ex: Sweet Bonanza')
                            ->helperText('Nome do jogo que será exibido na notificação de rodadas grátis')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get): bool => $get('game_free_rounds_active_popup')),

                        Forms\Components\TextInput::make('rounds_free_popup')
                            ->label('Número de Rodadas Grátis')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Quantidade de rodadas gratuitas que serão concedidas')
                            ->visible(fn (Forms\Get $get): bool => $get('game_free_rounds_active_popup')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configurações de Crédito Inicial')
                    ->schema([
                        Forms\Components\Toggle::make('initial_credit_active')
                            ->label('Ativar Crédito Inicial no Pop-up')
                            ->default(false)
                            ->reactive()
                            ->helperText('Quando ativado, o usuário receberá um crédito inicial na carteira ao clicar no botão do pop-up'),

                        Forms\Components\TextInput::make('initial_credit_amount')
                            ->label('Valor do Crédito Inicial (€)')
                            ->numeric()
                            ->default(0.01)
                            ->minValue(0.01)
                            ->maxValue(1000)
                            ->suffix('€')
                            ->helperText('Valor em euros que será adicionado à carteira do usuário')
                            ->visible(fn (Forms\Get $get): bool => $get('initial_credit_active') === true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagem')
                    ->circular(),

                Tables\Columns\IconColumn::make('show_after_login')
                    ->label('Após Login')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_after_register')
                    ->label('Após Registro')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_only_once')
                    ->label('Apenas Uma Vez')
                    ->boolean(),

                Tables\Columns\IconColumn::make('require_redemption')
                    ->label('Exige Resgate')
                    ->boolean(),

                Tables\Columns\IconColumn::make('browser_persistent')
                    ->label('Persist. Navegador')
                    ->boolean(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('target_user_type')
                    ->label('Tipo de Usuário')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'Todos',
                        'new' => 'Novos',
                        'existing' => 'Existentes',
                        'with_deposit' => 'Com Depósito',
                        'without_deposit' => 'Sem Depósito',
                    }),

                Tables\Columns\IconColumn::make('game_free_rounds_active_popup')
                    ->label('Freespin')
                    ->boolean(),

                Tables\Columns\IconColumn::make('initial_credit_active')
                    ->label('Crédito Inicial')
                    ->boolean(),

                Tables\Columns\TextColumn::make('initial_credit_amount')
                    ->label('Valor do Crédito')
                    ->money('EUR')
                    ->visible(fn ($record) => $record && $record->initial_credit_active),

                Tables\Columns\TextColumn::make('target_user_type')
                    ->label('')
                    ->visibleFrom('md')
                    ->colors([
                        'primary' => 'all',
                        'success' => 'new',
                        'warning' => 'existing',
                        'info' => 'with_deposit',
                        'danger' => 'without_deposit',
                        'purple' => 'affiliate',
                    ]),

                Tables\Columns\TextColumn::make('influencer_code')
                    ->label('Influencer')
                    ->searchable()
                    ->placeholder('Todos')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('require_influencer_match')
                    ->label('Segmentação Exclusiva')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('game_free_rounds_active_popup')
                    ->label('Freespin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record ? $record->game_free_rounds_active_popup : false),

                Tables\Columns\TextColumn::make('rounds_free_popup')
                    ->label('Rodadas')
                    ->numeric()
                    ->visible(fn ($record): bool => $record && $record->game_free_rounds_active_popup),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Término')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Métricas de Performance
                Tables\Columns\TextColumn::make('total_views')
                    ->label('Visualizações')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('unique_views')
                    ->label('Visualiz. Únicas')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Cliques')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_redemptions')
                    ->label('Resgates')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('successful_redemptions')
                    ->label('Resgates OK')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('redemption_success_rate')
                    ->label('Taxa Sucesso')
                    ->getStateUsing(function ($record) {
                        if ($record->total_redemptions == 0) {
                            return '0%';
                        }
                        $rate = round(($record->successful_redemptions / $record->total_redemptions) * 100, 1);

                        return $rate.'%';
                    })
                    ->badge()
                    ->color(function ($state) {
                        $rate = (float) str_replace('%', '', $state);
                        if ($rate >= 80) {
                            return 'success';
                        }
                        if ($rate >= 60) {
                            return 'warning';
                        }

                        return 'danger';
                    }),

                Tables\Columns\TextColumn::make('last_shown_at')
                    ->label('Última Exibição')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('Nunca exibido'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('active')
                    ->label('Ativos')
                    ->query(fn (Builder $query): Builder => $query->where('active', true)),

                Tables\Filters\Filter::make('login')
                    ->label('Após Login')
                    ->query(fn (Builder $query): Builder => $query->where('show_after_login', true)),

                Tables\Filters\Filter::make('register')
                    ->label('Após Registro')
                    ->query(fn (Builder $query): Builder => $query->where('show_after_register', true)),

                Tables\Filters\Filter::make('show_only_once')
                    ->label('Apenas Uma Vez')
                    ->query(fn (Builder $query): Builder => $query->where('show_only_once', true)),

                Tables\Filters\Filter::make('require_redemption')
                    ->label('Exige Resgate')
                    ->query(fn (Builder $query): Builder => $query->where('require_redemption', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthPopups::route('/'),
            'create' => Pages\CreateAuthPopup::route('/create'),
            'edit' => Pages\EditAuthPopup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
