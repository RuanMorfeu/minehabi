<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserAccountResource\Pages;
use App\Models\UserAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserAccountResource extends Resource
{
    protected static ?string $model = UserAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Verificação KYC';

    protected static ?string $modelLabel = 'Verificação';

    protected static ?string $pluralModelLabel = 'Verificações';

    protected static ?string $navigationGroup = 'Verificações KYC';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 'pending')->count() > 5 ? 'success' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Usuário')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Dados Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191)
                            ->disabled(),
                        Forms\Components\TextInput::make('document_number')
                            ->label('NIF')
                            ->required()
                            ->maxLength(191)
                            ->disabled(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Data de Nascimento')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telemóvel')
                            ->tel()
                            ->maxLength(191)
                            ->disabled(),
                        Forms\Components\TextInput::make('country')
                            ->label('País')
                            ->required()
                            ->maxLength(191)
                            ->default('PT')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status da Verificação')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'rejected' => 'Rejeitado',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo da Rejeição')
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->required(fn (callable $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('can_resubmit')
                            ->label('Permitir Reenvio')
                            ->helperText('Permite que o usuário reenvie documentos após rejeição')
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Data da Verificação')
                            ->visible(fn (callable $get) => in_array($get('status'), ['approved', 'rejected']))
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.wallet.total_balance')
                    ->label('Saldo Total')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (UserAccount $record) => $record->user->wallet?->total_balance ?? 0),
                Tables\Columns\TextColumn::make('user.wallet.balance_withdrawal')
                    ->label('Saldo Sacável')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn (UserAccount $record) => $record->user->wallet?->balance_withdrawal ?? 0),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome Completo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('NIF')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Data de Nascimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telemóvel')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('can_resubmit')
                    ->label('Pode Reenviar')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Data da Verificação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Visualizar'),
                Tables\Actions\EditAction::make()
                    ->label('Verificar'),
                Tables\Actions\Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UserAccount $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (UserAccount $record) {
                        $record->update([
                            'status' => 'approved',
                            'verified_at' => now(),
                            'rejection_reason' => null,
                            'can_resubmit' => true, // Reset para permitir reenvio futuro
                        ]);

                        Notification::make()
                            ->title('Dados pessoais aprovados com sucesso!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UserAccount $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo da Rejeição')
                            ->required()
                            ->placeholder('Descreva o motivo da rejeição...'),
                        Forms\Components\Toggle::make('can_resubmit')
                            ->label('Permitir Reenvio')
                            ->helperText('Permite que o usuário reenvie documentos após esta rejeição')
                            ->default(true),
                    ])
                    ->action(function (UserAccount $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'verified_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                            'can_resubmit' => $data['can_resubmit'] ?? true,
                        ]);

                        // ✅ ADICIONADO: Sincronizar com UserDocument
                        if ($record->user && $record->user->userDocument) {
                            $record->user->userDocument->update([
                                'verification_status' => 'rejected',
                                'verified_at' => now(),
                                'rejection_reason' => $data['rejection_reason'],
                                'can_resubmit' => $data['can_resubmit'] ?? true,
                            ]);
                        }

                        Notification::make()
                            ->title('Dados pessoais rejeitados!')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Aprovar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (UserAccount $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'verified_at' => now(),
                                        'rejection_reason' => null,
                                    ]);

                                    // ✅ ADICIONADO: Sincronizar com UserDocument
                                    if ($record->user && $record->user->userDocument) {
                                        $record->user->userDocument->update([
                                            'verification_status' => 'approved',
                                            'verified_at' => now(),
                                            'rejection_reason' => null,
                                            'can_resubmit' => true, // Reset para true quando aprovado
                                        ]);
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Dados pessoais aprovados com sucesso!')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'user.userDocument', 'user.wallet']);
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
            'index' => Pages\ListUserAccounts::route('/'),
            'create' => Pages\CreateUserAccount::route('/create'),
            'view' => Pages\ViewUserAccount::route('/{record}'),
            'edit' => Pages\EditUserAccount::route('/{record}/edit'),
        ];
    }
}
