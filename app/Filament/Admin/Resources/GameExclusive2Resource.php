<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GameExclusive2Resource\Pages;
use App\Models\GameExclusive2;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameExclusive2Resource extends Resource
{
    protected static ?string $model = GameExclusive2::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Jogos';

    protected static ?string $navigationLabel = 'Jogos Exclusivos 2';

    protected static ?string $modelLabel = 'Jogo Exclusivo 2';

    protected static ?string $pluralModelLabel = 'Jogos Exclusivos 2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome do Jogo')
                                    ->placeholder('Digite o nome do jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('uuid')
                                    ->placeholder('Digite o código do jogo')
                                    ->label('Código do Jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\Select::make('game_type')
                                    ->label('Tipo do Jogo')
                                    ->options([
                                        'pacman' => 'PacMan',
                                        'jetpack' => 'Jetpack Joyride',
                                        'angry' => 'Angry Birds',
                                    ])
                                    ->required(),
                            ])->columns(3),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição do Jogo')
                            ->placeholder('Digite a descrição do jogo')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\FileUpload::make('cover')
                                    ->label('Imagem da Capa')
                                    ->image()
                                    ->directory('games/covers')
                                    ->disk('public')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo: 5MB'),
                                Forms\Components\FileUpload::make('icon')
                                    ->label('Ícone do Jogo')
                                    ->image()
                                    ->directory('games/icons')
                                    ->disk('public')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                    ])
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo: 2MB. Recomendado: 1:1 (quadrado)'),
                            ])->columns(2),
                    ]),

                Forms\Components\Section::make('Configurações Gerais')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('meta_multiplier')
                                    ->label('Multiplicador de Meta')
                                    ->numeric()
                                    ->step(0.1)
                                    ->default(1.0)
                                    ->required(),
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Valor Mínimo de Aposta')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(1.0)
                                    ->required(),
                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Valor Máximo de Aposta')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Deixe vazio para sem limite')
                                    ->helperText('Valor máximo permitido para apostas (opcional)'),
                                Forms\Components\TextInput::make('views')
                                    ->label('Visualizações')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ])->columns(3),
                    ]),

                // Configurações específicas do Pacman
                Forms\Components\Section::make('Configurações do Pacman')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('lives')
                                    ->label('Vidas')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('coin_rate')
                                    ->label('Taxa de Moeda')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0.01)
                                    ->required(),
                                Forms\Components\TextInput::make('ghost_points')
                                    ->label('Pontos de Fantasma')
                                    ->numeric()
                                    ->step(0.1)
                                    ->default(0.1)
                                    ->required(),
                                Forms\Components\Select::make('difficulty')
                                    ->label('Dificuldade')
                                    ->options([
                                        1 => 'Fácil (1)',
                                        2 => 'Médio (2)',
                                        3 => 'Difícil (3)',
                                    ])
                                    ->default(1)
                                    ->required(),
                            ])->columns(4),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'pacman'),

                // Configurações de Influencer para Pacman
                Forms\Components\Section::make('Configurações de Influencer - Pacman')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('influencer_lives')
                                    ->label('Vidas (Influencer)')
                                    ->numeric()
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_coin_rate')
                                    ->label('Taxa de Moeda (Influencer)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_meta_multiplier')
                                    ->label('Multiplicador de Meta (Influencer)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_ghost_points')
                                    ->label('Pontos de Fantasma (Influencer)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\Select::make('influencer_difficulty')
                                    ->label('Dificuldade (Influencer)')
                                    ->options([
                                        1 => 'Fácil (1)',
                                        2 => 'Médio (2)',
                                        3 => 'Difícil (3)',
                                    ])
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                            ])->columns(5),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'pacman'),

                // Configurações específicas do Jetpack
                Forms\Components\Section::make('Configurações do Jetpack')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('coin_rate')
                                    ->label('Taxa de Moeda')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0.01)
                                    ->required(),
                                Forms\Components\Select::make('jetpack_difficulty')
                                    ->label('Dificuldade do Jetpack')
                                    ->options([
                                        'easy' => 'Fácil',
                                        'medium' => 'Médio',
                                        'hard' => 'Difícil',
                                    ])
                                    ->default('medium')
                                    ->required()
                                    ->helperText('Fácil: Velocidade baixa, poucos obstáculos | Médio: Velocidade normal | Difícil: Velocidade alta, muitos obstáculos'),
                            ])->columns(3),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'jetpack'),

                // Configurações de Influencer para Jetpack
                Forms\Components\Section::make('Configurações de Influencer - Jetpack')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('influencer_coin_rate')
                                    ->label('Taxa de Moeda (Influencer)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_meta_multiplier')
                                    ->label('Multiplicador de Meta (Influencer)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\Select::make('influencer_jetpack_difficulty')
                                    ->label('Dificuldade do Jetpack (Influencer)')
                                    ->options([
                                        'easy' => 'Fácil',
                                        'medium' => 'Médio',
                                        'hard' => 'Difícil',
                                    ])
                                    ->placeholder('Deixe vazio para usar valor padrão')
                                    ->helperText('Dificuldade específica para influencers'),
                            ])->columns(4),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'jetpack'),

                // Configurações específicas do Angry Birds
                Forms\Components\Section::make('Configurações do Angry Birds')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('coin_multiplier')
                                    ->label('Multiplicador de Moeda')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->default(1.0)
                                    ->required(),
                                Forms\Components\TextInput::make('game_difficulty')
                                    ->label('Dificuldade do Jogo')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                            ])->columns(3),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'angry'),

                // Configurações de Influencer para Angry Birds
                Forms\Components\Section::make('Configurações de Influencer - Angry Birds')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('influencer_coin_multiplier')
                                    ->label('Multiplicador de Moeda (Influencer)')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_game_difficulty')
                                    ->label('Dificuldade do Jogo (Influencer)')
                                    ->numeric()
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                                Forms\Components\TextInput::make('influencer_meta_multiplier')
                                    ->label('Multiplicador de Meta (Influencer)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->placeholder('Deixe vazio para usar valor padrão'),
                            ])->columns(3),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('game_type') === 'angry'),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Toggle::make('active')
                                    ->label('Ativo')
                                    ->helperText('Ative ou desative o jogo')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('visible_in_home')
                                    ->label('Visível na Home')
                                    ->helperText('Controla se o jogo aparece na página inicial')
                                    ->default(true)
                                    ->required(),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('Ícone')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Código')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('game_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pacman' => 'warning',
                        'jetpack' => 'info',
                        'angry' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('meta_multiplier')
                    ->label('Meta Multiplier')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('coin_rate')
                    ->label('Taxa Moeda')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min. Aposta')
                    ->numeric(decimalPlaces: 2)
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max. Aposta')
                    ->numeric(decimalPlaces: 2)
                    ->money('EUR')
                    ->placeholder('Sem limite')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Ativo'),
                Tables\Columns\ToggleColumn::make('visible_in_home')
                    ->label('Visível Home'),
                Tables\Columns\TextColumn::make('views')
                    ->label('Views')
                    ->icon('heroicon-o-eye')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('cover')
                    ->label('Capa')
                    ->disk('public')
                    ->height(60)
                    ->width(80)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game_type')
                    ->label('Tipo de Jogo')
                    ->options([
                        'pacman' => 'PacMan',
                        'jetpack' => 'Jetpack Joyride',
                        'angry' => 'Angry Birds',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Ativo'),
                Tables\Filters\TernaryFilter::make('visible_in_home')
                    ->label('Visível na Home'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListGameExclusive2s::route('/'),
            'create' => Pages\CreateGameExclusive2::route('/create'),
            'view' => Pages\ViewGameExclusive2::route('/{record}'),
            'edit' => Pages\EditGameExclusive2::route('/{record}/edit'),
        ];
    }
}
