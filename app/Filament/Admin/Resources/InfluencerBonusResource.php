<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InfluencerBonusResource\Pages;
use App\Models\InfluencerBonus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InfluencerBonusResource extends Resource
{
    protected static ?string $model = InfluencerBonus::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Bônus de Influencer';

    protected static ?string $pluralModelLabel = 'Bônus de Influencer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Bônus')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('O código que os usuários devem digitar para resgatar este bônus.'),

                        Forms\Components\TextInput::make('bonus_percentage')
                            ->label('Percentual de Bônus (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(300)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Percentual de bônus que será aplicado sobre o valor do depósito.'),

                        Forms\Components\TextInput::make('max_bonus')
                            ->label('Valor Máximo do Bônus (EUR)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('EUR')
                            ->helperText('Deixe em branco para não ter limite máximo.'),

                        Forms\Components\TextInput::make('min_deposit')
                            ->label('Depósito Mínimo (EUR)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('EUR')
                            ->default(0)
                            ->helperText('Valor mínimo de depósito necessário para resgatar este bônus.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->helperText('Desative para impedir que novos usuários resgatem este bônus.'),

                        Forms\Components\Toggle::make('one_time_use')
                            ->label('Uso Único')
                            ->default(false)
                            ->helperText('Se ativado, cada usuário poderá resgatar este bônus apenas uma vez.'),

                        Forms\Components\Toggle::make('browser_persistent')
                            ->label('Persistente no Navegador')
                            ->default(false)
                            ->helperText('Se ativado, o status de resgate será armazenado no navegador, independente do usuário. Útil para bônus que devem ser exibidos apenas uma vez por dispositivo.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bonus_percentage')
                    ->label('Bônus (%)')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_bonus')
                    ->label('Máx. Bônus')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.').' €' : 'Ilimitado')
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_deposit')
                    ->label('Mín. Depósito')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.').' €')
                    ->sortable(),

                Tables\Columns\IconColumn::make('one_time_use')
                    ->label('Uso Único')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                Tables\Columns\IconColumn::make('browser_persistent')
                    ->label('Persistente no Navegador')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Ativo',
                        '0' => 'Inativo',
                    ])
                    ->attribute('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // Inclua aqui os gerenciadores de relacionamento, se necessário
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInfluencerBonuses::route('/'),
            'create' => Pages\CreateInfluencerBonus::route('/create'),
            'edit' => Pages\EditInfluencerBonus::route('/{record}/edit'),
        ];
    }
}
