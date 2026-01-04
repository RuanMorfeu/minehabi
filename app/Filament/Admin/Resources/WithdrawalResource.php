<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Saques';

    protected static ?string $modelLabel = 'Saques';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?string $slug = 'todos-saques';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /*** @return string[]
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['type', 'bank_info', 'user.name', 'user.last_name', 'user.cpf', 'user.phone',  'user.email', 'name', 'nif'];
    }

    /*** @return string|null
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 0)->count();
    }

    /*** @return string|array|null
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 0)->count() > 5 ? 'success' : 'warning';
    }

    /*** @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cadastro de Saques')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuários')
                            ->placeholder('Selecione um usuário')
                            ->relationship(name: 'user', titleAttribute: 'name')
                            ->options(
                                fn ($get) => User::query()
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('nif')
                            ->label('NIF')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->required()
                            ->default(0.00),
                        Forms\Components\TextInput::make('type')
                            ->label('Tipo')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\FileUpload::make('proof')
                            ->label('Comprovante')
                            ->placeholder('Carregue a imagem do comprovante')
                            ->image()
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Toggle::make('status')
                            ->required(),
                    ]),
            ]);
    }

    /*** @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nome')
                    ->searchable(['users.name', 'users.last_name', 'users.email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Completo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (Withdrawal $record): string => '€ '.number_format($record->amount, 2, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('pix_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => \Helper::formatPixType($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('pix_key')
                    ->label('Chave Pix'),
                Tables\Columns\TextColumn::make('bank_info')
                    ->label('Informações Bancaria'),
                Tables\Columns\TextColumn::make('proof')
                    ->label('Comprovante')
                    ->html()
                    ->formatStateUsing(fn (string $state): string => '<a href="'.url('storage/'.$state).'" target="_blank">Baixar</a>'),

                Tables\Columns\IconColumn::make('status')

                    ->icon(fn (string $state): string => match ($state) {
                        '0' => 'heroicon-m-exclamation-circle',
                        '1' => 'heroicon-o-check-circle',
                        '2' => 'heroicon-m-x-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'warning',
                        '1' => 'success',
                        '2' => 'danger',

                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('user.is_demo_agent')
                    ->label('Influencer')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Withdrawal $record): string => $record->user->is_demo_agent ? 'Influencer' : 'Usuário Normal'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('deny_payment')
                    ->label('Cancelar')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->visible(fn (Withdrawal $withdrawal): bool => ! $withdrawal->status)
                    ->action(function (Withdrawal $withdrawal) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cancelar Saque')
                            ->success()
                            ->persistent()
                            ->body('Você está cancelando saque de '.\Helper::amountFormatDecimal($withdrawal->amount))
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Confirmar')
                                    ->button()
                                    ->url(route('cancelwithdrawal', ['id' => $withdrawal->id, 'tipo' => 'user']))
                                    ->close(),
                                \Filament\Notifications\Actions\Action::make('undo')
                                    ->color('gray')
                                    ->label('Cancelar')
                                    ->action(function (Withdrawal $withdrawal) {})
                                    ->close(),
                            ])
                            ->send();
                    }),
                Action::make('approve_payment')
                    ->label('Fazer pagamento')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Withdrawal $withdrawal): bool => ! $withdrawal->status)
                    ->action(function (Withdrawal $withdrawal) {
                        \Filament\Notifications\Notification::make()
                            ->title('Saque')
                            ->success()
                            ->persistent()
                            ->body('Você está solicitando um saque de '.\Helper::amountFormatDecimal($withdrawal->amount))
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Confirmar')
                                    ->button()
                                    ->url(route('withdrawal', ['id' => $withdrawal->id, 'tipo' => 'user']))
                                    ->close(),
                                \Filament\Notifications\Actions\Action::make('undo')
                                    ->color('gray')
                                    ->label('Cancelar')
                                    ->action(function (Withdrawal $withdrawal) {})
                                    ->close(),
                            ])
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve_payments')
                        ->label('Aprovar Saques')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                \DB::beginTransaction();

                                $processados = 0;
                                foreach ($records as $record) {
                                    if (! $record->status) {
                                        // Atualizar status
                                        $record->status = 1;
                                        $record->save();

                                        try {
                                            // Tentar notificar o usuário, mas não impedir o processo se falhar
                                            $record->user->notify(new \App\Notifications\WithdrawalApproved($record));
                                        } catch (\Exception $e) {
                                            \Log::error('Erro ao notificar usuário sobre saque aprovado: '.$e->getMessage());
                                        }

                                        $processados++;
                                    }
                                }

                                \DB::commit();

                                \Filament\Notifications\Notification::make()
                                    ->title('Saques Aprovados')
                                    ->success()
                                    ->body("$processados saques foram processados com sucesso!")
                                    ->send();

                            } catch (\Exception $e) {
                                \DB::rollBack();

                                \Filament\Notifications\Notification::make()
                                    ->title('Erro ao Processar Saques')
                                    ->danger()
                                    ->body('Ocorreu um erro ao processar os saques: '.$e->getMessage())
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deny_payments')
                        ->label('Cancelar Saques')
                        ->icon('heroicon-o-banknotes')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                if (! $record->status) {
                                    // Redirecionar para a rota de cancelamento
                                    return redirect()->route('cancelwithdrawal', ['id' => $record->id, 'tipo' => 'user']);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    /*** @return array|\Filament\Resources\RelationManagers\RelationGroup[]|\Filament\Resources\RelationManagers\RelationManagerConfiguration[]|string[]
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\WithdrawalResource\Pages\ListWithdrawals::route('/'),
            'create' => \App\Filament\Admin\Resources\WithdrawalResource\Pages\CreateWithdrawal::route('/create'),
            'edit' => \App\Filament\Admin\Resources\WithdrawalResource\Pages\EditWithdrawal::route('/{record}/edit'),
        ];
    }
}
