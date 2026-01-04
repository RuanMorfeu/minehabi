<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GameSpinsResource\Pages;
use App\Models\GameSpins;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameSpinsResource extends Resource
{
    protected static ?string $model = GameSpins::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('category')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('subcategory')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Textarea::make('details')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_new')
                    ->required(),
                Forms\Components\Toggle::make('mobile')
                    ->required(),
                Forms\Components\TextInput::make('id_hash')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('ts')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_hash_parent')
                    ->required()
                    ->maxLength(191),
                Forms\Components\Toggle::make('freerounds_supported')
                    ->required(),
                Forms\Components\Toggle::make('featurebuy_supported')
                    ->required(),
                Forms\Components\Toggle::make('has_jackpot')
                    ->required(),
                Forms\Components\Toggle::make('play_for_fun_supported')
                    ->required(),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->getUploadedFileUsing(fn ($state) => $state)
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\FileUpload::make('image_square')
                    ->image()
                    ->getUploadedFileUsing(fn ($state) => $state)
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\FileUpload::make('image_portrait')
                    ->image()
                    ->getUploadedFileUsing(fn ($state) => $state)
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\FileUpload::make('image_long')
                    ->image()
                    ->getUploadedFileUsing(fn ($state) => $state)
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3),
                Forms\Components\TextInput::make('source')
                    ->maxLength(191),
                Forms\Components\Toggle::make('use_at_own_risk')
                    ->required(),
                Forms\Components\TextInput::make('game_id')
                    ->numeric(),
                Forms\Components\Toggle::make('active')
                    ->required(),
                Forms\Components\Toggle::make('show_home')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('type')
                    ->searchable(),*/
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('subcategory')
                    ->searchable(),*/
                /*Tables\Columns\IconColumn::make('is_new')
                    ->boolean(),
                Tables\Columns\IconColumn::make('mobile')
                    ->boolean(),*/
                Tables\Columns\TextColumn::make('id_hash')
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('ts')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_hash_parent')
                    ->searchable(),
                Tables\Columns\IconColumn::make('freerounds_supported')
                    ->boolean(),
                Tables\Columns\IconColumn::make('featurebuy_supported')
                    ->boolean(),
                Tables\Columns\IconColumn::make('has_jackpot')
                    ->boolean(),
                Tables\Columns\IconColumn::make('play_for_fun_supported')
                    ->boolean(),*/
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\ImageColumn::make('image_square'),
                Tables\Columns\ImageColumn::make('image_portrait'),
                Tables\Columns\ImageColumn::make('image_long'),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\IconColumn::make('use_at_own_risk')
                    ->boolean(),*/
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('game_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('active')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('show_home')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGameSpins::route('/'),
            'create' => Pages\CreateGameSpins::route('/create'),
            'edit' => Pages\EditGameSpins::route('/{record}/edit'),
        ];
    }
}
