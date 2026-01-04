<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PreDeployCheckCommand extends Command
{
    protected $signature = 'deploy:pre-check';

    protected $description = 'Verifica se o sistema está pronto para deploy em produção';

    public function handle()
    {
        $this->info("🔍 Verificação pré-deploy para produção...\n");

        $errors = [];
        $warnings = [];
        $passed = 0;
        $total = 0;

        // Verificações críticas
        $this->checkEnvironmentConfig($errors, $warnings, $passed, $total);
        $this->checkSecurityConfig($errors, $warnings, $passed, $total);
        $this->checkDatabaseConfig($errors, $warnings, $passed, $total);
        $this->checkEmailConfig($errors, $warnings, $passed, $total);
        $this->checkFileStructure($errors, $warnings, $passed, $total);
        $this->checkDependencies($errors, $warnings, $passed, $total);
        $this->checkAssets($errors, $warnings, $passed, $total);

        // Mostrar resultados
        $this->displayResults($errors, $warnings, $passed, $total);

        return empty($errors) ? 0 : 1;
    }

    private function checkEnvironmentConfig(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('🌍 Verificando configurações de ambiente...');

        $total++;
        if (app()->environment('production')) {
            $this->line("✅ APP_ENV está configurado como 'production'");
            $passed++;
        } else {
            $warnings[] = "APP_ENV não está como 'production' (atual: ".app()->environment().')';
        }

        $total++;
        if (! config('app.debug')) {
            $this->line('✅ APP_DEBUG está desabilitado');
            $passed++;
        } else {
            $errors[] = "APP_DEBUG deve estar como 'false' em produção";
        }

        $total++;
        if (str_starts_with(config('app.url'), 'https://')) {
            $this->line('✅ APP_URL está configurado com HTTPS');
            $passed++;
        } else {
            $warnings[] = 'APP_URL deveria usar HTTPS em produção';
        }
    }

    private function checkSecurityConfig(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('🔒 Verificando configurações de segurança...');

        $total++;
        if (! empty(config('app.key'))) {
            $this->line('✅ APP_KEY está configurada');
            $passed++;
        } else {
            $errors[] = 'APP_KEY não está configurada';
        }

        $total++;
        if (! empty(config('jwt.secret'))) {
            $this->line('✅ JWT_SECRET está configurada');
            $passed++;
        } else {
            $errors[] = 'JWT_SECRET não está configurada';
        }

        $total++;
        if (config('logging.level') !== 'debug') {
            $this->line("✅ LOG_LEVEL não está como 'debug'");
            $passed++;
        } else {
            $warnings[] = "LOG_LEVEL está como 'debug' - considere 'error' para produção";
        }
    }

    private function checkDatabaseConfig(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('🗄️ Verificando configurações de banco de dados...');

        $total++;
        if (! empty(config('database.connections.mysql.host'))) {
            $this->line('✅ DB_HOST está configurado');
            $passed++;
        } else {
            $errors[] = 'DB_HOST não está configurado';
        }

        $total++;
        if (! empty(config('database.connections.mysql.database'))) {
            $this->line('✅ DB_DATABASE está configurado');
            $passed++;
        } else {
            $errors[] = 'DB_DATABASE não está configurado';
        }

        $total++;
        try {
            \DB::connection()->getPdo();
            $this->line('✅ Conexão com banco de dados funcional');
            $passed++;
        } catch (Exception $e) {
            $errors[] = 'Falha na conexão com banco: '.$e->getMessage();
        }
    }

    private function checkEmailConfig(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('📧 Verificando configurações de email...');

        $total++;
        $mailDriver = config('mail.default');
        if (in_array($mailDriver, ['postmark', 'smtp', 'failover'])) {
            $this->line("✅ MAIL_MAILER está configurado ({$mailDriver})");
            $passed++;
        } else {
            $warnings[] = "MAIL_MAILER pode não ser adequado para produção: {$mailDriver}";
        }

        $total++;
        if ($mailDriver === 'postmark' && ! empty(config('mail.mailers.postmark.token'))) {
            $this->line('✅ POSTMARK_TOKEN está configurado');
            $passed++;
        } elseif ($mailDriver === 'smtp' && ! empty(config('mail.mailers.smtp.host'))) {
            $this->line('✅ MAIL_HOST está configurado para SMTP');
            $passed++;
        } elseif ($mailDriver === 'failover') {
            $this->line('✅ Sistema de fallback de email configurado');
            $passed++;
        } else {
            $warnings[] = 'Configurações de email podem estar incompletas';
        }

        $total++;
        if (! empty(config('mail.from.address'))) {
            $this->line('✅ MAIL_FROM_ADDRESS está configurado');
            $passed++;
        } else {
            $warnings[] = 'MAIL_FROM_ADDRESS não está configurado';
        }
    }

    private function checkFileStructure(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('📁 Verificando estrutura de arquivos...');

        $criticalFiles = [
            '.env',
            'composer.json',
            'artisan',
            'public/index.php',
        ];

        foreach ($criticalFiles as $file) {
            $total++;
            if (File::exists(base_path($file))) {
                $this->line("✅ {$file} existe");
                $passed++;
            } else {
                $errors[] = "Arquivo crítico não encontrado: {$file}";
            }
        }

        $directories = [
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'bootstrap/cache',
        ];

        foreach ($directories as $dir) {
            $total++;
            $fullPath = base_path($dir);
            if (is_dir($fullPath) && is_writable($fullPath)) {
                $this->line("✅ {$dir} tem permissões corretas");
                $passed++;
            } else {
                $errors[] = "Diretório sem permissão de escrita: {$dir}";
            }
        }
    }

    private function checkDependencies(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('📦 Verificando dependências...');

        $total++;
        if (File::exists(base_path('vendor/autoload.php'))) {
            $this->line('✅ Dependências do Composer instaladas');
            $passed++;
        } else {
            $errors[] = "Execute 'composer install' antes do deploy";
        }

        $requiredExtensions = ['curl', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];
        foreach ($requiredExtensions as $ext) {
            $total++;
            if (extension_loaded($ext)) {
                $this->line("✅ Extensão PHP {$ext} carregada");
                $passed++;
            } else {
                $errors[] = "Extensão PHP necessária não encontrada: {$ext}";
            }
        }
    }

    private function checkAssets(&$errors, &$warnings, &$passed, &$total)
    {
        $this->info('🎨 Verificando assets...');

        $total++;
        if (File::exists(public_path('build/manifest.json'))) {
            $this->line('✅ Assets compilados encontrados');
            $passed++;
        } else {
            $warnings[] = "Assets não compilados - execute 'npm run build'";
        }

        $total++;
        if (File::exists(public_path('storage'))) {
            $this->line('✅ Link simbólico do storage existe');
            $passed++;
        } else {
            $warnings[] = "Execute 'php artisan storage:link' após o deploy";
        }
    }

    private function displayResults($errors, $warnings, $passed, $total)
    {
        $this->info("\n📊 Resultados da verificação:");
        $this->line("Verificações aprovadas: {$passed}/{$total}");

        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        if ($percentage >= 90) {
            $this->info("🎉 Sistema pronto para produção! ({$percentage}%)");
        } elseif ($percentage >= 70) {
            $this->warn("⚠️  Sistema quase pronto ({$percentage}%) - corrija os avisos");
        } else {
            $this->error("❌ Sistema NÃO está pronto para produção ({$percentage}%)");
        }

        if (! empty($errors)) {
            $this->error("\n❌ ERROS CRÍTICOS (devem ser corrigidos):");
            foreach ($errors as $error) {
                $this->error("  • {$error}");
            }
        }

        if (! empty($warnings)) {
            $this->warn("\n⚠️  AVISOS (recomendado corrigir):");
            foreach ($warnings as $warning) {
                $this->warn("  • {$warning}");
            }
        }

        if (empty($errors) && empty($warnings)) {
            $this->info("\n✅ Tudo perfeito! Sistema pronto para deploy em produção.");
            $this->info("Execute './deploy-production.sh' no servidor para fazer o deploy.");
        } elseif (empty($errors)) {
            $this->info("\n✅ Sistema aprovado para produção!");
            $this->warn('Considere corrigir os avisos para melhor performance.');
        } else {
            $this->error("\n❌ Corrija os erros críticos antes do deploy.");
        }

        $this->info("\n📋 Próximos passos:");
        $this->line('1. Corrigir erros críticos (se houver)');
        $this->line('2. Fazer upload dos arquivos para o servidor');
        $this->line('3. Configurar .env no servidor');
        $this->line('4. Executar ./deploy-production.sh');
        $this->line('5. Testar funcionalidades críticas');
    }
}
