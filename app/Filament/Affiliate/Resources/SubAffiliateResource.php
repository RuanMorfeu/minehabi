<?php

namespace App\Filament\Affiliate\Resources;

use App\Filament\Affiliate\Resources\SubAffiliateResource\Pages;
use App\Filament\Affiliate\Resources\SubAffiliateResource\Widgets\SubAffiliateOverview;
use App\Models\SubAffiliate;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubAffiliateResource extends Resource
{
    protected static ?string $model = SubAffiliate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Sub. Afiliados';

    protected static ?string $modelLabel = 'Sub. Afiliados';

    public static function canAccess(): bool
    {
        // Desativando acesso ao recurso de sub afiliados
        return false; // auth()->user()->hasRole('afiliado');
    }

    /*** @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {

        $usersIds = User::where('inviter', auth()->id())->get()->pluck('id');

        return $table
            ->query(User::query()->whereIn('id', $usersIds))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
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

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /*** @return string[]
     */
    public static function getWidgets(): array
    {
        return [
            SubAffiliateOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubAffiliates::route('/'),
        ];
    }
}
