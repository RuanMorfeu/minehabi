<?php

namespace App\Filament\Admin\Resources;

use App\Models\AffiliateWithdraw;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class AffiliateWithdrawResource extends Resource
{
    protected static ?string $model = AffiliateWithdraw::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('afiliado') || auth()->user()->hasRole('admin'));
    }

    /*** @return string
     */
    public static function getNavigationLabel(): string
    {
        return auth()->user()->hasRole('afiliado') ? 'Meus Saques' : 'Saques de Afiliados';
    }

    /*** @return string
     */
    public static function getModelLabel(): string
    {
        return auth()->user()->hasRole('afiliado') ? 'Meus Saques' : 'Saques de Afiliados';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 0)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::where('status', 0)->count() > 5 ? 'success' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    /*** @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->query(auth()->user()->hasRole('afiliado') ? AffiliateWithdraw::query()->where('user_id', auth()->id()) : AffiliateWithdraw::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (AffiliateWithdraw $record): string => $record->symbol.' '.$record->amount)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pix_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => \Helper::formatPixType($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('pix_key')
                    ->label('IBAN'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome IBAN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_info')
                    ->label('Informações Bancaria')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('proof')
                    ->label('Comprovante')
                    ->html()
                    ->formatStateUsing(fn (string $state): string => '<a href="'.url('storage/'.$state).'" target="_blank">Baixar</a>')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->visible(fn (AffiliateWithdraw $withdrawal): bool => ! $withdrawal->status)
                    ->action(function (AffiliateWithdraw $withdrawal) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cancelar Saque')
                            ->success()
                            ->persistent()
                            ->body('Você está cancelando saque de '.\Helper::amountFormatDecimal($withdrawal->amount))
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Confirmar')
                                    ->button()
                                    ->url(route('cancelwithdrawal', ['id' => $withdrawal->id, 'tipo' => 'afiliado']))
                                    ->close(),
                                \Filament\Notifications\Actions\Action::make('undo')
                                    ->color('gray')
                                    ->label('Cancelar')
                                    ->action(function (AffiliateWithdraw $withdrawal) {})
                                    ->close(),
                            ])
                            ->send();
                    }),
                Action::make('approve_payment')
                    ->label('Fazer pagamento')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (AffiliateWithdraw $withdrawal): bool => ! $withdrawal->status)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pagamento')
                    ->modalDescription(fn (AffiliateWithdraw $withdrawal) => 'Você está aprovando um saque de '.\Helper::amountFormatDecimal($withdrawal->amount).' para '.$withdrawal->user->name)
                    ->modalSubmitActionLabel('Confirmar Pagamento')
                    ->action(function (AffiliateWithdraw $withdrawal) {
                        try {
                            $withdrawal->update(['status' => 1]);

                            \Filament\Notifications\Notification::make()
                                ->title('Saque Aprovado')
                                ->body('Saque de '.\Helper::amountFormatDecimal($withdrawal->amount).' aprovado com sucesso!')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao Aprovar Saque')
                                ->body('Ocorreu um erro ao processar o saque: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
                                        $record->status = 1;
                                        $record->save();
                                        $processados++;
                                    }
                                }

                                \DB::commit();

                                if ($processados > 0) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Saques Aprovados')
                                        ->success()
                                        ->body("$processados saques foram aprovados com sucesso!")
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                \DB::rollBack();
                                \Log::error('Erro ao aprovar saques de afiliados: '.$e->getMessage());

                                \Filament\Notifications\Notification::make()
                                    ->title('Erro ao Aprovar Saques')
                                    ->danger()
                                    ->body('Ocorreu um erro ao processar os saques. Por favor, tente novamente.')
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
                                    return redirect()->route('cancelwithdrawal', ['id' => $record->id, 'tipo' => 'afiliado']);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
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
            'index' => \App\Filament\Admin\Resources\AffiliateWithdrawResource\Pages\ListAffiliateWithdraws::route('/'),
        ];
    }
}
