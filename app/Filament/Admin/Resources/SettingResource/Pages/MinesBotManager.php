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
                            ->label('Status do Bot')
                            ->content(function () {
                                // Verifica permissão no banco
                                $isEnabled = \App\Models\Setting::first()->mines_bot_enabled ?? false;

                                // Verifica processo físico
                                $pid = trim(shell_exec('pgrep -f "python Mines_com_api.py" | head -n 1'));
                                $isRunning = ! empty($pid) && is_numeric($pid);

                                if ($isRunning) {
                                    $cmd = trim(shell_exec("ps -p $pid -o args="));
                                    $user = trim(shell_exec("ps -p $pid -o user="));

                                    if ($isEnabled) {
                                        return "🟢 ATIVO E ENVIANDO SINAIS\nPID: $pid ($user)";
                                    } else {
                                        return "🟡 EM STANDBY (Processo rodando, mas envio pausado)\nPID: $pid ($user)\nO bot está aguardando ativação.";
                                    }
                                }

                                return '🔴 DESLIGADO (Processo não está rodando)';
                            }),
                    ]),

                Section::make('Ações Rápidas')
                    ->schema([
                        Actions::make([
                            Action::make('start_bot')
                                ->label('Ativar Envio (Start)')
                                ->icon('heroicon-o-play')
                                ->color('success')
                                ->action(function () {
                                    $this->startBot();
                                }),

                            Action::make('stop_bot')
                                ->label('Pausar Envio (Stop)')
                                ->icon('heroicon-o-pause')
                                ->color('warning')
                                ->action(function () {
                                    $this->stopBot();
                                }),

                            Action::make('refresh_status')
                                ->label('Atualizar Status')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
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
            // 1. Ativa a flag no banco de dados
            if ($this->record) {
                $this->record->update(['mines_bot_enabled' => true]);
            }

            // 2. Verifica se já está rodando
            $pid = trim(shell_exec('pgrep -f "python Mines_com_api.py" | head -n 1'));

            if (! empty($pid) && is_numeric($pid)) {
                Notification::make()
                    ->title('Bot Ativado!')
                    ->body("O processo já estava rodando (PID: $pid). O envio de sinais foi habilitado.")
                    ->success()
                    ->send();
            } else {
                // 3. Se não estiver rodando, inicia
                $botDir = base_path('bots/mines');

                // Verifica permissões
                if (! is_writable($botDir)) {
                    throw new \Exception("Sem permissão de escrita em $botDir");
                }

                $process = new Process([
                    'bash', '-c',
                    'cd '.base_path('bots/mines').' && source venv/bin/activate && nohup python Mines_com_api.py > bot_output.log 2>&1 & echo $!',
                ]);
                $process->setTimeout(0);
                $process->run();

                $newPid = trim($process->getOutput());

                Notification::make()
                    ->title('Bot Iniciado e Ativado!')
                    ->body("Processo iniciado com PID: $newPid")
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao ativar bot')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        // Refresh page
        redirect(route('filament.admin.resources.settings.mines_manager', ['record' => $this->record->id]));
    }

    protected function stopBot()
    {
        try {
            // Apenas desativa a flag no banco.
            // NÃO mata o processo, pois o Supervisor reiniciaria ele.
            if ($this->record) {
                $this->record->update(['mines_bot_enabled' => false]);
            }

            $logPath = base_path('bots/mines/bot_output.log');
            $timestamp = date('Y-m-d H:i:s');

            if (file_exists($logPath) && is_writable($logPath)) {
                file_put_contents($logPath, "[$timestamp] ADMIN: Bot colocado em PAUSA (Standby). Processo mantido vivo.\n", FILE_APPEND);
            }

            Notification::make()
                ->title('Bot Pausado (Standby)')
                ->body('O envio de sinais foi interrompido. O processo continua rodando em background para evitar reinícios automáticos.')
                ->warning()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao pausar bot')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        // Refresh page
        redirect(route('filament.admin.resources.settings.mines_manager', ['record' => $this->record->id]));
    }
}
