<?php

namespace App\Filament\Admin\Resources\GameResource\Pages;

use App\Filament\Admin\Resources\GameResource;
use App\Traits\Providers\DrakonTrait;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    use DrakonTrait;

    /*** @var string
     */
    protected static string $resource = GameResource::class;

    /*** @return array|Action[]|\Filament\Actions\ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Novo Jogo'),

            Action::make('classificarJogos')
                ->label('Classificar Jogos Ao Vivo')
                ->icon('heroicon-o-tag')
                ->color('warning')
                ->tooltip('Classifica automaticamente jogos dos provedores Spin, Evolution e PragmaticPlayLive como "Ao vivo" e "Todos", e desativa exibição na home')
                ->requiresConfirmation()
                ->modalHeading('Classificar Jogos Ao Vivo')
                ->modalDescription('Esta ação irá classificar automaticamente todos os jogos dos provedores Spin, Evolution e PragmaticPlayLive como "Ao vivo" e "Todos", além de desativar a exibição na home. Deseja continuar?')
                ->modalSubmitActionLabel('Sim, classificar jogos')
                ->action(function () {
                    try {
                        // Executar o script de classificação de jogos
                        $output = shell_exec('php '.base_path('classificar_jogos.php'));

                        // Exibir notificação de sucesso
                        Notification::make()
                            ->title('Classificação de jogos concluída com sucesso!')
                            ->success()
                            ->send();

                        // Registrar no log
                        \Log::info('Script de classificação de jogos executado com sucesso', [
                            'output' => $output,
                            'user_id' => auth()->id(),
                        ]);
                    } catch (\Exception $e) {
                        // Exibir notificação de erro
                        Notification::make()
                            ->title('Erro ao classificar jogos')
                            ->body('Ocorreu um erro ao executar o script de classificação: '.$e->getMessage())
                            ->danger()
                            ->send();

                        // Registrar erro no log
                        \Log::error('Erro ao executar script de classificação de jogos', [
                            'error' => $e->getMessage(),
                            'user_id' => auth()->id(),
                        ]);
                    }
                }),

            Action::make('importarJogosDrakon')
                ->label('Importar Jogos Drakon')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->tooltip('Importa jogos da API Drakon dos provedores específicos: 100hp, Aviatrix, Bgaming, creedz, Evolution, Evoplay, Galaxsys, Sagaming, Smartsoft e Spribe')
                ->requiresConfirmation()
                ->modalHeading('Importar Jogos Drakon')
                ->modalDescription('Esta ação irá importar jogos da API Drakon dos provedores específicos: 100hp, Aviatrix, Bgaming, creedz, Evolution, Evoplay, Galaxsys, Sagaming, Smartsoft e Spribe. Deseja continuar?')
                ->modalSubmitActionLabel('Sim, importar jogos')
                ->action(function () {
                    try {
                        // Executar a função de importação de jogos da Drakon
                        \App\Traits\Providers\DrakonTrait::getDrakonGames();

                        // Exibir notificação de sucesso
                        Notification::make()
                            ->title('Importação de jogos da Drakon concluída com sucesso!')
                            ->success()
                            ->send();

                        // Registrar no log
                        \Log::info('Importação de jogos da Drakon executada com sucesso', [
                            'user_id' => auth()->id(),
                        ]);
                    } catch (\Exception $e) {
                        // Exibir notificação de erro
                        Notification::make()
                            ->title('Erro ao importar jogos da Drakon')
                            ->body('Ocorreu um erro ao executar a importação: '.$e->getMessage())
                            ->danger()
                            ->send();

                        // Registrar erro no log
                        \Log::error('Erro ao executar importação de jogos da Drakon', [
                            'error' => $e->getMessage(),
                            'user_id' => auth()->id(),
                        ]);
                    }
                }),
        ];
    }

    /*** Carregar todos os provedores

     * @return void
     */
    protected static function LoadingProviderGames2Api()
    {
        dd('dsfsdsdf');
        self::GetAllProvidersGames2Api();
    }

    /*** Carregar todos os jogos

     * @return void
     */
    protected static function LoadingGames2Api()
    {
        self::GetAllGamesGames2Api();
    }

    protected static function LoadingGames()
    {
        self::LoadingGamesWorldSlot();
    }
}
