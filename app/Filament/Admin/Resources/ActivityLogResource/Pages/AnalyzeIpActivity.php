<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AnalyzeIpActivity extends Page
{
    protected static string $resource = ActivityLogResource::class;

    protected static string $view = 'filament.admin.resources.activity-log-resource.pages.analyze-ip-activity';

    // Garantir que a página seja carregada corretamente
    protected static string $layout = 'filament-panels::components.layout.index';

    public ?array $data = [];

    public $ipResults = [];

    public $userResults = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Análise de IPs')
                    ->description('Analise atividades por IP ou por usuário')
                    ->schema([
                        TextInput::make('ip')
                            ->label('Endereço IP')
                            ->placeholder('Ex: 192.168.1.1'),
                        TextInput::make('country')
                            ->label('País')
                            ->placeholder('Ex: Brazil ou BR'),
                        TextInput::make('user_id')
                            ->label('ID do Usuário')
                            ->numeric(),
                        DatePicker::make('start_date')
                            ->label('Data Inicial'),
                        DatePicker::make('end_date')
                            ->label('Data Final'),
                    ])
                    ->columns(2),
            ]);
    }

    public function searchByIp()
    {
        $this->validate([
            'data.ip' => 'nullable|string',
            'data.country' => 'nullable|string',
        ]);

        // Pelo menos um dos campos deve estar preenchido
        if (empty($this->data['ip']) && empty($this->data['country'])) {
            $this->addError('data.ip', 'Você deve informar pelo menos um IP ou País');

            return;
        }

        $query = Activity::query();

        // Filtrar por IP se fornecido
        if (! empty($this->data['ip'])) {
            $query->whereJsonContains('properties->ip', $this->data['ip']);
        }

        // Filtrar por país se fornecido
        if (! empty($this->data['country'])) {
            $country = strtolower($this->data['country']);

            // Busca pelo nome do país ou código do país
            $query->where(function ($q) use ($country) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.location.country_name'))) LIKE ?", ["%{$country}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.location.country_code'))) LIKE ?", ["%{$country}%"]);
            });
        }

        if (! empty($this->data['start_date'])) {
            $query->whereDate('created_at', '>=', $this->data['start_date']);
        }

        if (! empty($this->data['end_date'])) {
            $query->whereDate('created_at', '<=', $this->data['end_date']);
        }

        $this->ipResults = $query->with('causer')->get();
    }

    public function searchByUser()
    {
        $this->validate([
            'data.user_id' => 'required|numeric',
        ]);

        $query = Activity::query()
            ->where('causer_id', $this->data['user_id'])
            ->where('causer_type', 'App\\Models\\User');

        if (! empty($this->data['start_date'])) {
            $query->whereDate('created_at', '>=', $this->data['start_date']);
        }

        if (! empty($this->data['end_date'])) {
            $query->whereDate('created_at', '<=', $this->data['end_date']);
        }

        $this->userResults = $query->get();
    }

    public function getTopIpsProperty()
    {
        return Activity::query()
            ->whereNotNull('properties->ip')
            ->select(DB::raw('JSON_EXTRACT(properties, "$.ip") as ip'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('JSON_EXTRACT(properties, "$.ip")'))
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }
}
