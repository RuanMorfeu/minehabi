<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearUsersFast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clear-fast {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all users except admin from database (FAST VERSION)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  ATENÇÃO: Esta ação irá remover TODOS os usuários exceto o admin. Deseja continuar?')) {
                $this->info('Operação cancelada.');

                return 0;
            }
        }

        // Iniciar transação
        DB::beginTransaction();

        try {
            // Email do admin fixo
            $adminEmail = 'ruanadm@gmail.com';
            $admin = User::where('email', $adminEmail)->first();

            if (! $admin) {
                $this->error("Nenhum usuário admin encontrado com email {$adminEmail}!");

                return 1;
            }

            $this->info("Admin encontrado: {$admin->email} (ID: {$admin->id})");

            // Contar usuários que serão removidos
            $usersCount = User::where('id', '!=', $admin->id)->count();
            $this->info("Removendo {$usersCount} usuários...");

            // Desabilitar chaves estrangeiras temporariamente para acelerar
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Remover em lotes maiores usando delete direto (muito mais rápido)
            $this->info('Usando método de exclusão direta em lotes grandes...');

            // Primeiro limpar tabelas relacionadas (se houver)
            $this->info('Limpando tabelas relacionadas...');

            // Limpar transações dos usuários
            DB::table('transactions')->where('user_id', '!=', $admin->id)->delete();

            // Limpar wallets dos usuários
            DB::table('wallets')->where('user_id', '!=', $admin->id)->delete();

            // Agora remover os usuários em lotes grandes
            $deleted = 0;
            $batchSize = 10000; // Lotes ainda maiores

            do {
                $batchDeleted = DB::table('users')
                    ->where('id', '!=', $admin->id)
                    ->limit($batchSize)
                    ->delete();

                $deleted += $batchDeleted;

                if ($batchDeleted > 0) {
                    $this->info("Removidos {$deleted}/{$usersCount} usuários...");
                }

            } while ($batchDeleted > 0);

            // Reabilitar chaves estrangeiras
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Confirmar transação
            DB::commit();

            $this->info('✅ Usuários removidos com sucesso!');
            $this->info("Apenas o admin {$admin->email} foi mantido.");
            $this->info("Total removido: {$deleted} usuários");

            // Log da ação
            Log::warning('Users cleared from database (FAST)', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'removed_count' => $deleted,
                'timestamp' => now(),
            ]);

            return 0;

        } catch (\Exception $e) {
            // Reverter transação em caso de erro
            DB::rollBack();

            $this->error('❌ Erro ao remover usuários: '.$e->getMessage());
            Log::error('Error clearing users (FAST)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
