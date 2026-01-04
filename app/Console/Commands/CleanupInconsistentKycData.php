<?php

namespace App\Console\Commands;

use App\Helpers\R2Helper;
use App\Models\UserAccount;
use App\Models\UserDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupInconsistentKycData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyc:cleanup-inconsistent
                            {--dry-run : Apenas mostra o que seria corrigido sem fazer alterações}
                            {--fix-orphaned-accounts : Corrige UserAccounts sem UserDocuments}
                            {--fix-missing-files : Corrige UserDocuments com arquivos inexistentes}
                            {--cleanup-orphaned-files : Remove arquivos órfãos do R2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifica e corrige estados inconsistentes no sistema de verificação KYC';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $fixOrphanedAccounts = $this->option('fix-orphaned-accounts');
        $fixMissingFiles = $this->option('fix-missing-files');
        $cleanupOrphanedFiles = $this->option('cleanup-orphaned-files');

        $this->info('🔍 Iniciando análise de inconsistências no sistema KYC...');

        if ($isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Nenhuma alteração será feita');
        }

        $issues = [
            'orphaned_accounts' => 0,
            'missing_files' => 0,
            'orphaned_files' => 0,
            'fixed' => 0,
        ];

        // 1. Verifica UserAccounts com status 'pending' mas sem UserDocuments
        $this->checkOrphanedAccounts($issues, $isDryRun, $fixOrphanedAccounts);

        // 2. Verifica UserDocuments com arquivos que não existem no R2
        $this->checkMissingFiles($issues, $isDryRun, $fixMissingFiles);

        // 3. Verifica arquivos órfãos no R2 (opcional - mais pesado)
        if ($cleanupOrphanedFiles) {
            $this->checkOrphanedFiles($issues, $isDryRun);
        }

        // Relatório final
        $this->displaySummary($issues, $isDryRun);

        return 0;
    }

    /**
     * Verifica UserAccounts com status 'pending' ou 'approved' mas sem UserDocuments correspondentes
     */
    private function checkOrphanedAccounts(array &$issues, bool $isDryRun, bool $shouldFix)
    {
        $this->info('📋 Verificando UserAccounts órfãos...');

        $orphanedAccounts = UserAccount::whereIn('status', ['pending', 'approved'])
            ->whereDoesntHave('user.userDocument')
            ->get();

        $issues['orphaned_accounts'] = $orphanedAccounts->count();

        if ($orphanedAccounts->count() > 0) {
            $this->warn("❌ Encontrados {$orphanedAccounts->count()} UserAccounts com status 'pending/approved' mas sem UserDocuments");

            foreach ($orphanedAccounts as $account) {
                $userEmail = $account->user ? $account->user->email : 'N/A';
                $this->line("   - UserAccount ID: {$account->id}, User: {$userEmail}");

                if ($shouldFix && ! $isDryRun) {
                    // Reset status para permitir novo envio
                    $account->update([
                        'status' => null,
                        'rejection_reason' => 'Reset automático - documentos não encontrados',
                    ]);
                    $this->info("   ✅ UserAccount {$account->id} resetado");
                    $issues['fixed']++;
                }
            }
        } else {
            $this->info('✅ Nenhum UserAccount órfão encontrado');
        }
    }

    /**
     * Verifica UserDocuments com arquivos que não existem no R2
     */
    private function checkMissingFiles(array &$issues, bool $isDryRun, bool $shouldFix)
    {
        $this->info('📁 Verificando arquivos ausentes no R2...');

        $documentsWithMissingFiles = [];
        $userDocuments = UserDocument::whereNotNull('document_front')
            ->orWhereNotNull('document_back')
            ->orWhereNotNull('selfie')
            ->get();

        foreach ($userDocuments as $document) {
            $missingFiles = [];

            // Verifica cada arquivo
            $files = [
                'document_front' => $document->document_front,
                'document_back' => $document->document_back,
                'selfie' => $document->selfie,
            ];

            foreach ($files as $field => $path) {
                if (! empty($path) && ! R2Helper::fileExists($path)) {
                    $missingFiles[] = $field;
                }
            }

            if (! empty($missingFiles)) {
                $documentsWithMissingFiles[] = [
                    'document' => $document,
                    'missing_files' => $missingFiles,
                ];
            }
        }

        $issues['missing_files'] = count($documentsWithMissingFiles);

        if (count($documentsWithMissingFiles) > 0) {
            $this->warn('❌ Encontrados '.count($documentsWithMissingFiles).' UserDocuments com arquivos ausentes no R2');

            foreach ($documentsWithMissingFiles as $item) {
                $document = $item['document'];
                $missingFiles = $item['missing_files'];

                $userEmail = $document->user ? $document->user->email : 'N/A';
                $this->line("   - UserDocument ID: {$document->id}, User: {$userEmail}");
                $this->line('     Arquivos ausentes: '.implode(', ', $missingFiles));

                if ($shouldFix && ! $isDryRun) {
                    // Reset status para rejected para permitir reenvio
                    $document->update([
                        'verification_status' => 'rejected',
                        'rejection_reason' => 'Arquivos não encontrados no sistema - favor reenviar',
                        'can_resubmit' => true,
                    ]);

                    // Sincroniza com UserAccount se existir
                    if ($document->user->userAccount) {
                        $document->user->userAccount->update([
                            'status' => 'rejected',
                            'rejection_reason' => 'Arquivos não encontrados no sistema - favor reenviar',
                            'can_resubmit' => true,
                        ]);
                    }

                    $this->info("   ✅ UserDocument {$document->id} marcado para reenvio");
                    $issues['fixed']++;
                }
            }
        } else {
            $this->info('✅ Todos os arquivos estão presentes no R2');
        }
    }

    /**
     * Verifica arquivos órfãos no R2 (arquivos que existem no R2 mas não no banco)
     */
    private function checkOrphanedFiles(array &$issues, bool $isDryRun)
    {
        $this->info('🗂️  Verificando arquivos órfãos no R2...');
        $this->warn('⚠️  Esta operação pode demorar alguns minutos...');

        try {
            // Lista todos os arquivos no diretório kyc/
            $r2Files = R2Helper::listFiles('kyc', true);
            $dbFiles = [];

            // Coleta todos os caminhos de arquivos do banco
            $userDocuments = UserDocument::select(['document_front', 'document_back', 'selfie'])->get();
            foreach ($userDocuments as $document) {
                if ($document->document_front) {
                    $dbFiles[] = $document->document_front;
                }
                if ($document->document_back) {
                    $dbFiles[] = $document->document_back;
                }
                if ($document->selfie) {
                    $dbFiles[] = $document->selfie;
                }
            }

            $orphanedFiles = [];
            foreach ($r2Files as $file) {
                if ($file['type'] === 'file' && ! in_array($file['path'], $dbFiles)) {
                    $orphanedFiles[] = $file['path'];
                }
            }

            $issues['orphaned_files'] = count($orphanedFiles);

            if (count($orphanedFiles) > 0) {
                $this->warn('❌ Encontrados '.count($orphanedFiles).' arquivos órfãos no R2');

                foreach ($orphanedFiles as $orphanedFile) {
                    $this->line("   - Arquivo órfão: {$orphanedFile}");

                    if (! $isDryRun) {
                        // Remove arquivo órfão
                        if (R2Helper::deleteFile($orphanedFile)) {
                            $this->info("   ✅ Arquivo órfão removido: {$orphanedFile}");
                            $issues['fixed']++;
                        } else {
                            $this->error("   ❌ Erro ao remover arquivo: {$orphanedFile}");
                        }
                    }
                }
            } else {
                $this->info('✅ Nenhum arquivo órfão encontrado no R2');
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro ao verificar arquivos órfãos: {$e->getMessage()}");
        }
    }

    /**
     * Exibe o relatório final
     */
    private function displaySummary(array $issues, bool $isDryRun)
    {
        $this->info('');
        $this->info('📊 RELATÓRIO FINAL:');
        $this->info('==================');

        if ($issues['orphaned_accounts'] > 0) {
            $this->line("UserAccounts órfãos: {$issues['orphaned_accounts']}");
        }

        if ($issues['missing_files'] > 0) {
            $this->line("UserDocuments com arquivos ausentes: {$issues['missing_files']}");
        }

        if ($issues['orphaned_files'] > 0) {
            $this->line("Arquivos órfãos no R2: {$issues['orphaned_files']}");
        }

        $totalIssues = $issues['orphaned_accounts'] + $issues['missing_files'] + $issues['orphaned_files'];

        if ($totalIssues === 0) {
            $this->info('🎉 Sistema KYC está íntegro - nenhuma inconsistência encontrada!');
        } else {
            if ($isDryRun) {
                $this->warn("⚠️  Total de inconsistências encontradas: {$totalIssues}");
                $this->info('💡 Execute sem --dry-run e com as opções de correção para resolver os problemas');
            } else {
                $this->info("✅ Total de problemas corrigidos: {$issues['fixed']}");
            }
        }

        // Log do resultado
        Log::info('KYC Cleanup executado', [
            'dry_run' => $isDryRun,
            'issues_found' => $totalIssues,
            'issues_fixed' => $issues['fixed'],
            'details' => $issues,
        ]);
    }
}
