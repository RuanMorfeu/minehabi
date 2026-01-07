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
use Illuminate\Support\Facades\Log;
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
                                // Retorna PID e PPID (Parent PID)
                                $output = trim(shell_exec('ps -eo pid,ppid,args | grep "Mines_com_api.py" | grep -v grep | head -n 1'));

                                if (! empty($output)) {
                                    // Remove espaços múltiplos
                                    $parts = preg_split('/\s+/', trim($output));
                                    $pid = $parts[0] ?? null;
                                    $ppid = $parts[1] ?? null;

                                    $msg = "MinesBotManager: Status Check. PID: $pid, PPID: $ppid. Raw: $output";
                                    Log::info($msg);
                                    error_log($msg);

                                    if (is_numeric($pid)) {
                                        return "🟢 Em execução (PID: $pid | Pai: $ppid)";
                                    }
                                } else {
                                    $msg = 'MinesBotManager: Status Check. Nenhum processo encontrado.';
                                    Log::info($msg);
                                    error_log($msg);
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
            ])
            ->statePath('data');
    }

    protected function startBot()
    {
        $msg = 'MinesBotManager: Solicitado início do bot.';
        Log::info($msg);
        error_log($msg);

        try {
            // Garante que não tem outro bot rodando antes de iniciar
            $msg = 'MinesBotManager: Matando processos antigos...';
            Log::info($msg);
            error_log($msg);
            exec('pkill -f Mines_com_api.py');

            // Executa em background sem timeout e salva logs
            $msg = 'MinesBotManager: Iniciando novo processo...';
            Log::info($msg);
            error_log($msg);

            $logFile = storage_path('logs/mines_bot.log');
            $process = new Process([
                'bash', '-c',
                'cd '.base_path('bots/mines').' && source venv/bin/activate && nohup python Mines_com_api.py > '.$logFile.' 2>&1 & echo $!',
            ]);
            $process->setTimeout(0);
            $process->run();

            $pid = trim($process->getOutput());
            $msg = "MinesBotManager: Processo iniciado. Output PID: {$pid}";
            Log::info($msg);
            error_log($msg);

            if (empty($pid) || ! is_numeric($pid)) {
                $msg = 'MinesBotManager: Falha ao obter PID.';
                Log::error($msg);
                error_log($msg);
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
            $msg = 'MinesBotManager: Erro ao iniciar bot: '.$e->getMessage();
            Log::error($msg);
            error_log($msg);
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
        $msg = 'MinesBotManager: Solicitado parada do bot.';
        Log::info($msg);
        error_log($msg);

        try {
            // Mata todos os processos relacionados ao bot
            $msg = 'MinesBotManager: Executando pkill...';
            Log::info($msg);
            error_log($msg);
            exec('pkill -f Mines_com_api.py');

            // Aguarda 1 segundo para o processo encerrar
            sleep(1);

            // Verifica se ainda existe algum teimoso e força o encerramento
            $check = trim(shell_exec('pgrep -f Mines_com_api.py'));
            $msg = 'MinesBotManager: Verificação pós-kill. Processos restantes: '.($check ?: 'Nenhum');
            Log::info($msg);
            error_log($msg);

            if (! empty($check)) {
                $msg = 'MinesBotManager: Processo persistente detectado. Forçando kill -9.';
                Log::warning($msg);
                error_log($msg);
                exec('pkill -9 -f Mines_com_api.py');
                sleep(1);
            }

            Notification::make()
                ->title('Bot parado com sucesso!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            $msg = 'MinesBotManager: Erro ao parar bot: '.$e->getMessage();
            Log::error($msg);
            error_log($msg);
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
