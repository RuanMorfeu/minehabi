<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GatewayResource\Pages;
use App\Models\Gateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class GatewayResource extends Resource
{
    protected static ?string $model = Gateway::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Configurações de Gateway';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SuitPay')
                    ->schema([
                        Forms\Components\TextInput::make('suitpay_uri')
                            ->label('URI')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('suitpay_cliente_id')
                            ->label('Cliente ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('suitpay_cliente_secret')
                            ->label('Cliente Secret')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('DigitoPay')
                    ->schema([
                        Forms\Components\TextInput::make('digitopay_uri')
                            ->label('URI')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('digitopay_cliente_id')
                            ->label('Cliente ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('digitopay_cliente_secret')
                            ->label('Cliente Secret')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('EzzePay')
                    ->schema([
                        Forms\Components\TextInput::make('ezze_uri')
                            ->label('URI')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ezze_client')
                            ->label('Client')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ezze_secret')
                            ->label('Secret')
                            ->password()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ezze_user')
                            ->label('User')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ezze_senha')
                            ->label('Senha')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('EuPago')
                    ->schema([
                        Forms\Components\TextInput::make('eupago_uri')
                            ->label('URI')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('eupago_id')
                            ->label('ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('eupago_secret')
                            ->label('Secret')
                            ->password()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('eupago_api_key')
                            ->label('API Key')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('SIBS')
                    ->schema([
                        Forms\Components\TextInput::make('sibs_terminalId')
                            ->label('Terminal ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sibs_entidade')
                            ->label('Entidade')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sibs_clientId')
                            ->label('Client ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sibs_bearerToken')
                            ->label('Bearer Token')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Mollie')
                    ->schema([
                        Forms\Components\TextInput::make('mollie_api_key')
                            ->label('API Key')
                            ->password()
                            ->maxLength(255)
                            ->helperText('Chave da API do Mollie (test_xxx para teste, live_xxx para produção)'),
                        Forms\Components\Toggle::make('mollie_active')
                            ->label('Ativo')
                            ->helperText('Ativar ou desativar o gateway Mollie'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Configurações de Gateway por Método')
                    ->schema([
                        Forms\Components\Select::make('mbway_gateway')
                            ->label('Gateway para MBWay')
                            ->options([
                                'eupago' => 'EuPago',
                                'sibs' => 'SIBS',
                            ])
                            ->default('eupago'),
                        Forms\Components\Select::make('multibanco_gateway')
                            ->label('Gateway para Multibanco')
                            ->options([
                                'eupago' => 'EuPago',
                                'sibs' => 'SIBS',
                            ])
                            ->default('eupago'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\IconColumn::make('mollie_active')
                    ->label('Mollie')
                    ->boolean(),
                Tables\Columns\TextColumn::make('mbway_gateway')
                    ->label('MBWay Gateway'),
                Tables\Columns\TextColumn::make('multibanco_gateway')
                    ->label('Multibanco Gateway'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListGateways::route('/'),
            'create' => Pages\CreateGateway::route('/create'),
            'edit' => Pages\EditGateway::route('/{record}/edit'),
        ];
    }
}
