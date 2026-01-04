<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FacebookAdsResource\Pages;
use App\Models\Deposit;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FacebookAdsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Facebook Ads';

    protected static ?string $modelLabel = 'Extração Facebook Ads';

    protected static ?string $pluralModelLabel = 'Extrações Facebook Ads';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Telefone')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Data de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                IconColumn::make('has_deposit')
                    ->label('Tem Depósito')
                    ->boolean()
                    ->getStateUsing(function (User $user) {
                        return Deposit::where('user_id', $user->id)
                            ->where('status', 1)
                            ->exists();
                    }),

                IconColumn::make('has_multiple_deposits')
                    ->label('Múltiplos Depósitos')
                    ->boolean()
                    ->getStateUsing(function (User $user) {
                        return Deposit::where('user_id', $user->id)
                            ->where('status', 1)
                            ->count() > 1;
                    }),

                IconColumn::make('is_affiliate')
                    ->label('Afiliado')
                    ->boolean()
                    ->getStateUsing(function (User $user) {
                        return ! empty($user->inviter_code);
                    }),

                IconColumn::make('has_referrals')
                    ->label('Tem Indicações')
                    ->boolean()
                    ->getStateUsing(function (User $user) {
                        if (empty($user->inviter_code)) {
                            return false;
                        }

                        return User::where('inviter', $user->id)->exists();
                    }),
            ])
            ->filters([
                Filter::make('sem_deposito')
                    ->label('Sem Depósito')
                    ->query(function (Builder $query) {
                        $depositUserIds = Deposit::where('status', 1)
                            ->distinct()
                            ->pluck('user_id');

                        return $query->whereNotIn('id', $depositUserIds);
                    }),

                Filter::make('com_deposito')
                    ->label('Com Depósito')
                    ->query(function (Builder $query) {
                        $depositUserIds = Deposit::where('status', 1)
                            ->distinct()
                            ->pluck('user_id');

                        return $query->whereIn('id', $depositUserIds);
                    }),

                Filter::make('multiplos_depositos')
                    ->label('Múltiplos Depósitos')
                    ->query(function (Builder $query) {
                        $multipleDepositUserIds = DB::table('deposits')
                            ->select('user_id')
                            ->where('status', 1)
                            ->groupBy('user_id')
                            ->havingRaw('COUNT(*) > 1')
                            ->pluck('user_id');

                        return $query->whereIn('id', $multipleDepositUserIds);
                    }),

                Filter::make('afiliados')
                    ->label('Afiliados (Todos)')
                    ->query(function (Builder $query) {
                        return $query->whereNotNull('inviter_code')
                            ->whereRaw('inviter_code != ""');
                    }),

                Filter::make('afiliados_com_indicacao')
                    ->label('Afiliados COM Indicação')
                    ->query(function (Builder $query) {
                        $afiliadosComIndicacao = User::whereNotNull('inviter')
                            ->where('inviter', '!=', 0)
                            ->distinct()
                            ->pluck('inviter');

                        return $query->whereNotNull('inviter_code')
                            ->whereRaw('inviter_code != ""')
                            ->whereIn('id', $afiliadosComIndicacao);
                    }),

                Filter::make('afiliados_sem_indicacao')
                    ->label('Afiliados SEM Indicação')
                    ->query(function (Builder $query) {
                        $afiliadosComIndicacao = User::whereNotNull('inviter')
                            ->where('inviter', '!=', 0)
                            ->distinct()
                            ->pluck('inviter');

                        return $query->whereNotNull('inviter_code')
                            ->whereRaw('inviter_code != ""')
                            ->whereNotIn('id', $afiliadosComIndicacao);
                    }),

                Filter::make('created_from')
                    ->label('Registrado Após')
                    ->form([
                        DatePicker::make('created_from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    }),

                Filter::make('created_until')
                    ->label('Registrado Até')
                    ->form([
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListFacebookAds::route('/'),
        ];
    }
}
