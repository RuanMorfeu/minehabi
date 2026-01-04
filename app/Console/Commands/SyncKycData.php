<?php

namespace App\Console\Commands;

use App\Models\UserAccount;
use Illuminate\Console\Command;

class SyncKycData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyc:sync-data {--dry-run : Apenas mostrar o que seria sincronizado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza dados de verificação KYC entre UserAccount e UserDocument';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Iniciando sincronização de dados KYC...');

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Nenhuma alteração será feita');
        }

        // Buscar todos os UserAccounts que têm UserDocument
        $userAccounts = UserAccount::whereHas('user.userDocument')->with('user.userDocument')->get();

        $totalProcessed = 0;
        $totalSynced = 0;
        $inconsistencies = [];

        foreach ($userAccounts as $userAccount) {
            $totalProcessed++;
            $userDocument = $userAccount->user->userDocument;

            // Verificar inconsistências
            $needsSync = false;
            $changes = [];

            if ($userDocument->verification_status !== $userAccount->status) {
                $needsSync = true;
                $changes[] = "verification_status: {$userDocument->verification_status} → {$userAccount->status}";
            }

            if ($userDocument->verified_at != $userAccount->verified_at) {
                $needsSync = true;
                $changes[] = "verified_at: {$userDocument->verified_at} → {$userAccount->verified_at}";
            }

            if ($userDocument->rejection_reason !== $userAccount->rejection_reason) {
                $needsSync = true;
                $changes[] = "rejection_reason: {$userDocument->rejection_reason} → {$userAccount->rejection_reason}";
            }

            // Converter para boolean para comparação correta
            $docCanResubmit = (bool) $userDocument->can_resubmit;
            $accCanResubmit = (bool) $userAccount->can_resubmit;

            if ($docCanResubmit !== $accCanResubmit) {
                $needsSync = true;
                $changes[] = 'can_resubmit: '.($docCanResubmit ? 'true' : 'false').' → '.($accCanResubmit ? 'true' : 'false');
            }

            if ($needsSync) {
                $totalSynced++;
                $inconsistencies[] = [
                    'user_id' => $userAccount->user_id,
                    'user_name' => $userAccount->user->name,
                    'user_email' => $userAccount->user->email,
                    'changes' => $changes,
                ];

                if (! $dryRun) {
                    // Sincronizar dados
                    $userDocument->update([
                        'verification_status' => $userAccount->status,
                        'verified_at' => $userAccount->verified_at,
                        'rejection_reason' => $userAccount->rejection_reason,
                        'can_resubmit' => $userAccount->can_resubmit,
                    ]);
                }
            }
        }

        // Mostrar resultados
        $this->info("📊 Processados: {$totalProcessed} registros");
        $this->info("🔄 Sincronizados: {$totalSynced} registros");

        if (! empty($inconsistencies)) {
            $this->warn("\n📋 Inconsistências encontradas:");

            foreach ($inconsistencies as $inconsistency) {
                $this->line("\n👤 Usuário: {$inconsistency['user_name']} ({$inconsistency['user_email']})");
                foreach ($inconsistency['changes'] as $change) {
                    $this->line("   • {$change}");
                }
            }
        } else {
            $this->info('✅ Nenhuma inconsistência encontrada!');
        }

        if ($dryRun && $totalSynced > 0) {
            $this->warn("\n⚠️  Para aplicar as correções, execute: php artisan kyc:sync-data");
        } elseif (! $dryRun && $totalSynced > 0) {
            $this->info("\n✅ Sincronização concluída com sucesso!");
        }

        return 0;
    }
}
