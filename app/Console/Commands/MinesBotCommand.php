<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MinesBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mines:bot {action=start : start|stop|status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerenciar o Bot Mines do Telegram';

    protected $pidFile;

    protected $botPath;

    public function __construct()
    {
        parent::__construct();
        $this->pidFile = storage_path('app/mines_bot.pid');
        $this->botPath = base_path('bots/mines');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'start':
                return $this->startBot();
            case 'stop':
                return $this->stopBot();
            case 'status':
                return $this->getBotStatus();
            default:
                $this->error('Ação inválida. Use: start, stop ou status');

                return 1;
        }
    }

    protected function startBot()
    {
        if ($this->isRunning()) {
            $this->info('O bot já está em execução!');

            return 0;
        }

        $this->info('Iniciando o Bot Mines...');

        // Cria o arquivo .env se não existir
        $envFile = $this->botPath.'/.env';
        if (! file_exists($envFile)) {
            copy($this->botPath.'/.env.example', $envFile);
            $this->warn('Arquivo .env criado. Por favor, configure suas credenciais!');
        }

        // Atualiza a URL da API no arquivo .env do bot
        $this->updateBotEnv();

        // Inicia o processo em background usando shell_exec
        $command = sprintf(
            'cd %s && source venv/bin/activate && python Mines_com_api.py > /dev/null 2>&1 & echo $!',
            $this->botPath
        );

        $pid = trim(shell_exec($command));

        if (empty($pid) || ! is_numeric($pid)) {
            $this->error('Falha ao iniciar o bot.');

            return 1;
        }

        // Salva o PID
        file_put_contents($this->pidFile, $pid);

        $this->info('Bot iniciado com PID: '.$pid);

        return 0;
    }

    protected function stopBot()
    {
        if (! $this->isRunning()) {
            $this->info('O bot não está em execução.');

            return 0;
        }

        $pid = file_get_contents($this->pidFile);
        posix_kill($pid, SIGTERM);

        // Remove o arquivo PID
        if (file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }

        $this->info('Bot parado com sucesso!');

        return 0;
    }

    protected function getBotStatus()
    {
        if ($this->isRunning()) {
            $pid = file_get_contents($this->pidFile);
            $this->info("Bot está em execução com PID: {$pid}");
        } else {
            $this->info('Bot não está em execução.');
        }

        return 0;
    }

    protected function isRunning()
    {
        if (! file_exists($this->pidFile)) {
            return false;
        }

        $pid = file_get_contents($this->pidFile);

        return posix_kill($pid, 0);
    }

    protected function updateBotEnv()
    {
        $envFile = $this->botPath.'/.env';
        $apiUrl = config('app.url').'/api/bot/mines/status';

        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            $content = preg_replace(
                '/LARAVEL_API_URL=.*/',
                'LARAVEL_API_URL='.$apiUrl,
                $content
            );
            file_put_contents($envFile, $content);
        }
    }
}
