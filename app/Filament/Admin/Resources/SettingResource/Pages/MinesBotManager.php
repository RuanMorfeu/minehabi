<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use App\Models\Setting;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Process\Process;

class MinesBotManager extends Page implements HasForms
{
    use HasPageSidebar;
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.mines-bot-manager';

    public static function canView(Model $record): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Gerenciar Bot Mines';
    }

    public Setting $record;

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::first();
        $this->record = $setting;
        $this->form->fill($setting->toArray());
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Controle do Bot Mines')
                    ->description('Use os botões abaixo para controlar o bot de sinais do Mines')
                    ->schema([
                        Placeholder::make('bot_status')
                            ->label('Status do Processo')
                            ->content(function () {
                                // Verifica se existe algum processo python rodando o script do bot
                                $pid = trim(shell_exec('pgrep -f "python Mines_com_api.py" | head -n 1'));

                                if (! empty($pid) && is_numeric($pid)) {
                                    // Pega o comando exato para confirmar o que é
                                    $cmd = trim(shell_exec("ps -p $pid -o args="));

                                    return "🟢 Em execução (PID: $pid)\nCMD: $cmd";
                                }

                                return '🔴 Parado';
                            }),
                    ]),

                Section::make('Ações Rápidas')
                    ->schema([
                        Actions::make([
                            Action::make('start_bot')
                                ->label('Iniciar Bot')
                                ->icon('heroicon-o-play')
                                ->color('success')
                                ->action(function () {
                                    $this->startBot();
                                }),

                            Action::make('stop_bot')
                                ->label('Parar Bot')
                                ->icon('heroicon-o-stop')
                                ->color('danger')
                                ->action(function () {
                                    $this->stopBot();
                                }),

                            Action::make('refresh_status')
                                ->label('Atualizar Status')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
                                    // Apenas recarrega a página
                                    redirect(route('filament.admin.resources.settings.mines_manager', ['record' => $this->record->id]));
                                }),
                        ]),
                    ])
                    ->columns(3),

                Section::make('Logs de Diagnóstico')
                    ->collapsed()
                    ->description('Visualize os logs para identificar problemas')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('log_output')
                            ->label('Log de Inicialização (bot_output.log)')
                            ->rows(10)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function () {
                                $path = base_path('bots/mines/bot_output.log');

                                return file_exists($path) ? file_get_contents($path) : 'Arquivo de log não encontrado. Inicie o bot para gerar.';
                            }),

                        \Filament\Forms\Components\Textarea::make('log_debug')
                            ->label('Log Interno do Bot (bot_debug.log)')
                            ->rows(10)
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function () {
                                $path = base_path('bots/mines/bot_debug.log');

                                return file_exists($path) ? file_get_contents($path) : 'Arquivo de log não encontrado.';
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    protected function startBot()
    {
        try {
            // Verifica permissões de escrita no diretório de logs
            $botDir = base_path('bots/mines');
            if (! is_writable($botDir)) {
                Notification::make()
                    ->title('Erro de Permissão')
                    ->body("O diretório {$botDir} não tem permissão de escrita. Execute: chown -R www-data:www-data {$botDir} && chmod -R 775 {$botDir}")
                    ->danger()
                    ->send();

                return;
            }

            // Garante que não tem outro bot rodando antes de iniciar
            exec('pkill -f Mines_com_api.py');

            // Executa em background sem timeout, redirecionando saída para log
            $process = new Process([
                'bash', '-c',
                'cd '.base_path('bots/mines').' && source venv/bin/activate && nohup python Mines_com_api.py > bot_output.log 2>&1 & echo $!',
            ]);
            $process->setTimeout(0);
            $process->run();

            $pid = trim($process->getOutput());

            if (empty($pid) || ! is_numeric($pid)) {
                Notification::make()
                    ->title('Erro ao iniciar bot')
                    ->body('Não foi possível obter o PID do processo')
                    ->danger()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Bot iniciado com sucesso!')
                ->body("PID: {$pid}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao iniciar bot')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        // Refresh page to update status
        redirect(route('filament.admin.resources.settings.mines_manager', ['record' => $this->record->id]));
    }

    protected function stopBot()
    {
        try {
            $logPath = base_path('bots/mines/bot_output.log');
            $timestamp = date('Y-m-d H:i:s');

            // Log do início da ação
            if (file_exists($logPath) && is_writable($logPath)) {
                file_put_contents($logPath, "\n[$timestamp] ADMIN: Comando 'Parar Bot' recebido.\n", FILE_APPEND);
            }

            // Identifica processos antes
            $pids = trim(shell_exec('pgrep -f Mines_com_api.py'));

            if (! empty($pids)) {
                if (file_exists($logPath) && is_writable($logPath)) {
                    file_put_contents($logPath, "[$timestamp] ADMIN: Processos encontrados antes do kill: ".str_replace("\n", ' ', $pids)."\n", FILE_APPEND);
                }
            } else {
                if (file_exists($logPath) && is_writable($logPath)) {
                    file_put_contents($logPath, "[$timestamp] ADMIN: Nenhum processo encontrado para parar.\n", FILE_APPEND);
                }
            }

            // Mata todos os processos relacionados ao bot
            exec('pkill -f Mines_com_api.py');

            // Aguarda 1 segundo para o sistema operacional processar
            sleep(1);

            // Verifica se ainda tem algo rodando
            $pidsAfter = trim(shell_exec('pgrep -f Mines_com_api.py'));

            if (! empty($pidsAfter)) {
                if (file_exists($logPath) && is_writable($logPath)) {
                    file_put_contents($logPath, "[$timestamp] ADMIN: AVISO - Processos ainda ativos (PID: $pidsAfter). Tentando kill forçado (-9)...\n", FILE_APPEND);
                }

                // Força bruta se não morreu
                exec('pkill -9 -f Mines_com_api.py');
            } else {
                if (file_exists($logPath) && is_writable($logPath)) {
                    file_put_contents($logPath, "[$timestamp] ADMIN: Todos os processos do bot foram finalizados.\n", FILE_APPEND);
                }
            }

            Notification::make()
                ->title('Bot parado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao parar bot')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        // Refresh page to update status
        redirect(route('filament.admin.resources.settings.mines_manager', ['record' => $this->record->id]));
    }
}
