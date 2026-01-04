<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthCheckCommand extends Command
{
    protected $signature = 'system:health-check';

    protected $description = 'Verifica a saúde geral do sistema e identifica problemas potenciais';

    public function handle()
    {
        $this->info("🔍 Iniciando verificação de saúde do sistema...\n");

        $issues = [];
        $warnings = [];

        // Verificar configurações críticas
        $this->checkCriticalConfigurations($issues, $warnings);

        // Verificar conexões
        $this->checkConnections($issues, $warnings);

        // Verificar permissões de arquivos
        $this->checkFilePermissions($issues, $warnings);

        // Verificar configurações de produção
        $this->checkProductionSettings($issues, $warnings);

        // Verificar dependências críticas
        $this->checkCriticalDependencies($issues, $warnings);

        // Mostrar resultados
        $this->displayResults($issues, $warnings);

        return empty($issues) ? 0 : 1;
    }

    private function checkCriticalConfigurations(&$issues, &$warnings)
    {
        $this->info('📋 Verificando configurações críticas...');

        // APP_KEY
        if (empty(config('app.key'))) {
            $issues[] = 'APP_KEY não está configurada';
        }

        // JWT_SECRET
        if (empty(config('jwt.secret'))) {
            $issues[] = 'JWT_SECRET não está configurada';
        }

        // Mail configurations
        $mailDriver = config('mail.default');
        if ($mailDriver === 'smtp' && empty(config('mail.mailers.smtp.host'))) {
            $issues[] = 'MAIL_HOST não está configurada para SMTP';
        }

        if ($mailDriver === 'postmark' && empty(config('mail.mailers.postmark.token'))) {
            $issues[] = 'POSTMARK_TOKEN não está configurada';
        }

        // Database
        if (empty(config('database.connections.mysql.host'))) {
            $issues[] = 'DB_HOST não está configurada';
        }

        $this->line('✅ Configurações críticas verificadas');
    }

    private function checkConnections(&$issues, &$warnings)
    {
        $this->info('🔌 Verificando conexões...');

        // Database
        try {
            DB::connection()->getPdo();
            $this->line('✅ Conexão com banco de dados: OK');
        } catch (Exception $e) {
            $issues[] = 'Falha na conexão com banco de dados: '.$e->getMessage();
        }

        // Redis
        try {
            if (config('cache.default') === 'redis') {
                Cache::store('redis')->put('health_check', 'test', 10);
                Cache::store('redis')->forget('health_check');
                $this->line('✅ Conexão com Redis: OK');
            }
        } catch (Exception $e) {
            $warnings[] = 'Falha na conexão com Redis: '.$e->getMessage();
        }

        // Storage
        try {
            Storage::disk('local')->put('health_check.txt', 'test');
            Storage::disk('local')->delete('health_check.txt');
            $this->line('✅ Sistema de arquivos: OK');
        } catch (Exception $e) {
            $issues[] = 'Falha no sistema de arquivos: '.$e->getMessage();
        }
    }

    private function checkFilePermissions(&$issues, &$warnings)
    {
        $this->info('📁 Verificando permissões de arquivos...');

        $directories = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $dir) {
            if (! is_writable($dir)) {
                $issues[] = "Diretório não tem permissão de escrita: {$dir}";
            }
        }

        $this->line('✅ Permissões de arquivos verificadas');
    }

    private function checkProductionSettings(&$issues, &$warnings)
    {
        $this->info('🚀 Verificando configurações de produção...');

        if (app()->environment('production')) {
            // Debug deve estar desabilitado
            if (config('app.debug')) {
                $warnings[] = 'APP_DEBUG está habilitado em produção';
            }

            // Log level não deve ser debug
            if (config('logging.level') === 'debug') {
                $warnings[] = "LOG_LEVEL está como 'debug' em produção";
            }

            // HTTPS
            if (! request()->isSecure() && config('app.url') && ! str_starts_with(config('app.url'), 'https://')) {
                $warnings[] = 'APP_URL não está configurada com HTTPS';
            }
        }

        $this->line('✅ Configurações de produção verificadas');
    }

    private function checkCriticalDependencies(&$issues, &$warnings)
    {
        $this->info('📦 Verificando dependências críticas...');

        // Verificar extensões PHP necessárias
        $requiredExtensions = ['curl', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];

        foreach ($requiredExtensions as $extension) {
            if (! extension_loaded($extension)) {
                $issues[] = "Extensão PHP necessária não está instalada: {$extension}";
            }
        }

        // Verificar limites PHP
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1' && (int) $memoryLimit < 256) {
            $warnings[] = "Memory limit pode ser insuficiente: {$memoryLimit}";
        }

        $this->line('✅ Dependências críticas verificadas');
    }

    private function displayResults($issues, $warnings)
    {
        $this->info("\n📊 Resultados da verificação:");

        if (empty($issues) && empty($warnings)) {
            $this->info('🎉 Sistema está saudável! Nenhum problema encontrado.');

            return;
        }

        if (! empty($issues)) {
            $this->error("\n❌ PROBLEMAS CRÍTICOS ENCONTRADOS:");
            foreach ($issues as $issue) {
                $this->error("  • {$issue}");
            }
        }

        if (! empty($warnings)) {
            $this->warn("\n⚠️  AVISOS:");
            foreach ($warnings as $warning) {
                $this->warn("  • {$warning}");
            }
        }

        $this->info("\n📈 Estatísticas:");
        $this->line('Memory Limit: '.ini_get('memory_limit'));
        $this->line('Max Execution Time: '.ini_get('max_execution_time'));
        $this->line('PHP Version: '.PHP_VERSION);
        $this->line('Laravel Version: '.app()->version());
        $this->line('Environment: '.app()->environment());
    }
}
