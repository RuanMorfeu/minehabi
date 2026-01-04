<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email} {--mailer=}';

    protected $description = 'Testa o envio de email para diagnosticar problemas';

    public function handle()
    {
        $email = $this->argument('email');
        $mailer = $this->option('mailer');

        $this->info('🔧 Iniciando teste de email...');
        $this->info("📧 Email destino: {$email}");

        // Mostrar configurações atuais
        $this->showCurrentConfig();

        // Testar diferentes mailers se não especificado
        $mailersToTest = $mailer ? [$mailer] : ['postmark', 'smtp', 'log'];

        foreach ($mailersToTest as $currentMailer) {
            $this->testMailer($currentMailer, $email);
        }
    }

    private function showCurrentConfig()
    {
        $this->info("\n📋 Configurações atuais:");
        $this->line('MAIL_MAILER: '.config('mail.default'));
        $this->line('MAIL_HOST: '.config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: '.config('mail.mailers.smtp.port'));
        $this->line('MAIL_FROM_ADDRESS: '.config('mail.from.address'));
        $this->line('POSTMARK_TOKEN: '.(config('mail.mailers.postmark.token') ? 'Configurado' : 'Não configurado'));
        $this->line('Memory Limit: '.ini_get('memory_limit'));
        $this->line('Max Execution Time: '.ini_get('max_execution_time'));
    }

    private function testMailer($mailer, $email)
    {
        $this->info("\n🧪 Testando mailer: {$mailer}");

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            // Configurar mailer temporariamente
            config(['mail.default' => $mailer]);

            $this->line('Enviando email de teste...');

            Mail::send('emails.forget-password', [
                'token' => 'TEST123',
                'resetLink' => url('/reset-password/TEST123'),
            ], function ($message) use ($email) {
                $message->to($email);
                $message->subject('Teste de Email - dei.bet');
            });

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $duration = round(($endTime - $startTime) * 1000, 2);
            $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);

            $this->info("✅ Sucesso com {$mailer}!");
            $this->line("⏱️  Tempo: {$duration}ms");
            $this->line("💾 Memória: {$memoryUsed}MB");

        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->error("❌ Erro com {$mailer}: ".$e->getMessage());
            $this->line("⏱️  Tempo até erro: {$duration}ms");
            $this->line('🔍 Classe do erro: '.get_class($e));

            if (method_exists($e, 'getCode')) {
                $this->line('📊 Código do erro: '.$e->getCode());
            }
        }
    }
}
