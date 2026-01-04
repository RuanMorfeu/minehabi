<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GameExclusiveResource\Pages;
use App\Models\GameExclusive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameExclusiveResource extends Resource
{
    protected static ?string $model = GameExclusive::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome do Jogo')
                                    ->placeholder('Digite o nome do jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('uuid')
                                            ->placeholder('Digite o código do jogo')
                                            ->label('Código do Jogo')
                                            ->required()
                                            ->maxLength(191),
                                        Forms\Components\TextInput::make('description')
                                            ->placeholder('Digite a descrição do jogo')
                                            ->label('Descrição do Jogo')
                                            ->required()
                                            ->maxLength(191),
                                        Forms\Components\TextInput::make('min_amount')
                                            ->placeholder('10')
                                            ->label('Valor minimo de aposta')
                                            ->required()
                                            ->maxLength(191),
                                        Forms\Components\TextInput::make('max_amount')
                                            ->placeholder('100')
                                            ->label('Valor máximo de aposta')
                                            ->maxLength(191),
                                    ])->columns(4),

                                Forms\Components\FileUpload::make('cover')
                                    ->label('Capa')
                                    ->placeholder('Carregue a capa do jogo')
                                    ->image()
                                    ->columnSpanFull()
                                    ->helperText('Tamanho recomendado para a capa é de 300x350')
                                    ->required(),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('winLength')
                                    ->placeholder('20')
                                    ->label('Ganho do Jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('loseLength')
                                    ->placeholder('10')
                                    ->label('Perda do Jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('velocidade')
                                    ->placeholder('easy')
                                    ->label('Velocidade do jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('xmeta')
                                    ->placeholder('10')
                                    ->label('Meta do jogo')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('coin_value')
                                    ->placeholder('10')
                                    ->label('Valor da moeda')
                                    ->required()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('influencer_winLength')
                                    ->placeholder('20')
                                    ->label('Influencer quantidade de ganho')
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('influencer_loseLength')
                                    ->placeholder('10')
                                    ->label('Influencer quantidade de perda')
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('influencer_velocidade')
                                    ->placeholder('easy')
                                    ->label('Velocidade do jogo (Influencer)')
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('influencer_xmeta')
                                    ->placeholder('10')
                                    ->label('Meta do jogo (Influencer)')
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('influencer_coin_value')
                                    ->placeholder('10')
                                    ->label('Valor da moeda (Influencer)')
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('views')
                                    ->label('Visualizações')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('active')
                                    ->label('Status')
                                    ->helperText('Ative ou desative o jogo')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('visible_in_home')
                                    ->label('Visível na Home')
                                    ->helperText('Controla se o jogo aparece na página inicial')
                                    ->default(true)
                                    ->required(),
                            ]),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('Capa')
                // ->disk('media')
                ,
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Código')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('velocidade')
                    ->label('Velocidade')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Status'),
                Tables\Columns\ToggleColumn::make('visible_in_home')
                    ->label('Visível na Home'),
                Tables\Columns\TextColumn::make('views')
                    ->icon('heroicon-o-eye')
                    ->numeric()
                    ->sortable(),
            ])

            ->filters([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListGameExclusives::route('/'),
            'create' => Pages\CreateGameExclusive::route('/create'),
            'edit' => Pages\EditGameExclusive::route('/{record}/edit'),
        ];
    }
}
