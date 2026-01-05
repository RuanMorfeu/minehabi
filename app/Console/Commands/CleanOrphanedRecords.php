<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOrphanedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean-orphans {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean orphaned records from all tables after user cleanup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  ATENÇÃO: Esta ação irá remover todos os registros órfãos. Deseja continuar?')) {
                $this->info('Operação cancelada.');

                return 0;
            }
        }

        // Iniciar transação
        DB::beginTransaction();

        try {
            $this->info('Procurando e removendo registros órfãos...');

            // Tabelas para limpar
            $tables = [
                'deposits' => 'Depósitos',
                'withdrawals' => 'Saques',
                'affiliate_withdraws' => 'Saques de Afiliados',
                'orders' => 'Pedidos',
                'user_accounts' => 'Contas de Usuário',
                'user_deposits' => 'Depósitos de Usuário',
                'user_documents' => 'Documentos de Usuário',
                'popup_freespin_redemptions' => 'Resgates de Freespins',
            ];

            $totalRemoved = 0;

            foreach ($tables as $table => $description) {
                // Verificar se tabela existe
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    $this->line("Tabela {$table} não existe, pulando...");

                    continue;
                }

                // Contar órfãos antes
                $orphanCount = DB::table($table)
                    ->whereNotIn('user_id', function ($query) {
                        $query->select('id')->from('users');
                    })
                    ->count();

                if ($orphanCount > 0) {
                    $this->info("Removendo {$orphanCount} registros órfãos de {$description} ({$table})...");

                    // Remover órfãos
                    $deleted = DB::table($table)
                        ->whereNotIn('user_id', function ($query) {
                            $query->select('id')->from('users');
                        })
                        ->delete();

                    $totalRemoved += $deleted;
                    $this->line("✓ {$deleted} registros removidos");
                } else {
                    $this->line("✓ Nenhum registro órfão em {$description}");
                }
            }

            // Verificar transactions (que devem estar ok)
            $transactionOrphans = DB::table('transactions')
                ->whereNotIn('user_id', function ($query) {
                    $query->select('id')->from('users');
                })
                ->count();

            if ($transactionOrphans > 0) {
                $this->warn("ATENÇÃO: Encontrados {$transactionOrphans} transactions órfãs!");
            }

            // Confirmar transação
            DB::commit();

            $this->info('✅ Limpeza concluída com sucesso!');
            $this->info("Total de registros órfãos removidos: {$totalRemoved}");

            // Log da ação
            Log::info('Orphaned records cleaned', [
                'total_removed' => $totalRemoved,
                'timestamp' => now(),
            ]);

            return 0;

        } catch (\Exception $e) {
            // Reverter transação em caso de erro
            DB::rollBack();

            $this->error('❌ Erro ao limpar registros órfãos: '.$e->getMessage());
            Log::error('Error cleaning orphaned records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
