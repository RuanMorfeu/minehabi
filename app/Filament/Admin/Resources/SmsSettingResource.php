<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SmsSettingResource\Pages;
use App\Models\SmsSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SmsSettingResource extends Resource
{
    protected static ?string $model = SmsSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Configurações de SMS';

    protected static ?string $navigationGroup = 'Configurações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_type')
                    ->label('Tipo de Evento')
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Nenhuma ação de edição aqui, o toggle na coluna já resolve.
            ])
            ->bulkActions([
                // Ações em massa não são necessárias aqui.
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSmsSettings::route('/'),
        ];
    }
}
