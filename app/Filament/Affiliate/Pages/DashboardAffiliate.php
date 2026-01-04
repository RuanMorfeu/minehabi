<?php

namespace App\Filament\Affiliate\Pages;

use App\Exceptions\AccessDeniedException;
use App\Filament\Affiliate\Widgets\AffiliateChart;
use App\Filament\Affiliate\Widgets\AffiliateWidgets;
use App\Filament\Affiliate\Widgets\LatestAdminComissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardAffiliate extends \Filament\Pages\Dashboard
{
    use HasFiltersAction;
    use HasFiltersForm;

    // Propriedade para armazenar os filtros
    public $filterData = [];

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.affiliate.pages.dashboard-affiliate';

    /*** @return string|\Illuminate\Contracts\Support\Htmlable|null
     */
    public function getSubheading(): string|null|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Olá, Afiliado! Seja muito bem-vindo ao seu painel.';
    }

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->hasRole('afiliado')) {
            return true;
        }

        throw new AccessDeniedException;
    }

    /*** @param Form $form
     * @return Form
     */
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    /**
     * Método executado quando o filtro é aplicado
     */
    public function filterFormUpdated(array $data): void
    {
        parent::filterFormUpdated($data);

        // Armazena os dados do filtro na propriedade da classe
        $this->filterData = $data;

        // Armazena os dados do filtro na sessão para fácil acesso pelos widgets
        session(['filament.dashboard-affiliate.filters.state' => $data]);

        // Força a atualização dos widgets
        $this->dispatch('filament.dashboard-affiliate.filters-updated', data: $data);
    }

    /*** @return array|\Filament\Actions\Action[]|\Filament\Actions\ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Filtro')
                ->form([
                    DatePicker::make('startDate')->label('Data Incial'),
                    DatePicker::make('endDate')->label('Data Final'),
                ]),
        ];
    }

    /*** @return string[]
     */
    public function getWidgets(): array
    {
        return [
            AffiliateWidgets::class,
            AffiliateChart::class,
            LatestAdminComissions::class,
        ];
    }
}
