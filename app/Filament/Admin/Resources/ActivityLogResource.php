<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    /**
     * Verifica se uma string é um JSON válido
     *
     * @param  string  $string
     * @return bool
     */
    private static function isJson($string)
    {
        if (! is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Segurança';

    protected static ?string $navigationLabel = 'Logs de Atividade';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_name')
                    ->label('Nome do Log')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Descrição')
                    ->disabled(),
                Forms\Components\TextInput::make('subject_type')
                    ->label('Tipo de Objeto')
                    ->disabled(),
                Forms\Components\TextInput::make('subject_id')
                    ->label('ID do Objeto')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_type')
                    ->label('Tipo de Causador')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_id')
                    ->label('ID do Causador')
                    ->disabled(),
                Forms\Components\KeyValue::make('properties')
                    ->label('Propriedades')
                    ->afterStateHydrated(function ($component, $state) {
                        // Processar localização para exibição
                        if (isset($state['location'])) {
                            $ip = $state['ip'] ?? null;
                            $state['location'] = self::formatLocation($state['location'], $ip);
                            $component->state($state);
                        }
                    })
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Data de Criação')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filtrar eventos genéricos "created" e "updated"
                return $query->whereNotIn('description', ['created', 'updated']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Ação')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('IP')
                    ->formatStateUsing(fn ($state) => $state['ip'] ?? 'N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Localização')
                    ->formatStateUsing(function ($state) {
                        if (empty($state['location'])) {
                            return 'N/A';
                        }

                        $location = $state['location'];

                        // Se for uma string JSON, decodificar
                        if (is_string($location)) {
                            $decoded = json_decode($location, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $location = $decoded;
                            }
                        }

                        if (is_array($location)) {
                            $country = $location['countryName'] ?? null;
                            $city = $location['cityName'] ?? null;

                            if ($country && $city) {
                                return "{$country}, {$city}";
                            }

                            return $country ?? 'Desconhecido';
                        }

                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query
                            ->where('properties->location->countryName', 'like', "%{$search}%")
                            ->orWhere('properties->location->cityName', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('login')
                    ->label('Login')
                    ->query(fn (Builder $query): Builder => $query->where('description', 'login')),
                Tables\Filters\Filter::make('logout')
                    ->label('Logout')
                    ->query(fn (Builder $query): Builder => $query->where('description', 'logout')),
                Tables\Filters\Filter::make('register')
                    ->label('Registro')
                    ->query(fn (Builder $query): Builder => $query->where('description', 'user_registered')),
                Tables\Filters\Filter::make('deposit')
                    ->label('Depósitos')
                    ->query(fn (Builder $query): Builder => $query->where('description', 'deposit_completed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
            'analyze-ip' => Pages\AnalyzeIpActivity::route('/analyze-ip'),
        ];
    }

    /**
     * Formata os dados de localização para exibição no painel
     */
    public static function formatLocation($location, $ip = null)
    {
        // Se for uma string JSON, decodificar
        if (is_string($location)) {
            $decoded = json_decode($location, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $location = $decoded;
            }
        }

        // Se ainda for uma string ou estiver vazio após tentativa de decodificação
        if (! is_array($location) || empty($location) || empty(array_filter((array) $location))) {
            // Fornecer dados de fallback baseados no IP
            if ($ip && ($ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.'))) {
                return 'Local, Rede Interna';
            } else {
                return 'Desconhecido, Não disponível';
            }
        }

        // Se tiver dados de localização válidos
        $locationArray = (array) $location;
        $country = $locationArray['countryName'] ?? '';
        $city = $locationArray['cityName'] ?? '';

        if ($country || $city) {
            return trim("$country, $city", ', ');
        }

        return 'N/A';
    }
}
