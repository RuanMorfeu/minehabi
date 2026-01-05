<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clear {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all users except admin from database';

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
            // Encontrar o admin (pela role)
            $admin = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->first();

            if (! $admin) {
                $this->error('Nenhum usuário admin encontrado!');

                return 1;
            }

            $this->info("Admin encontrado: {$admin->email} (ID: {$admin->id})");

            // Contar usuários que serão removidos
            $usersCount = User::where('id', '!=', $admin->id)->count();
            $this->info("Removendo {$usersCount} usuários...");

            // Barra de progresso
            $progressBar = $this->output->createProgressBar($usersCount);
            $progressBar->start();

            // Remover usuários em lotes para evitar problemas de memória
            User::where('id', '!=', $admin->id)
                ->chunk(100, function ($users) use ($progressBar) {
                    foreach ($users as $user) {
                        // O método delete() do modelo User já cuida de limpar as relações
                        $user->delete();
                        $progressBar->advance();
                    }
                });

            $progressBar->finish();
            $this->newLine();

            // Confirmar transação
            DB::commit();

            $this->info('✅ Usuários removidos com sucesso!');
            $this->info("Apenas o admin {$admin->email} foi mantido.");

            // Log da ação
            Log::warning('Users cleared from database', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'removed_count' => $usersCount,
                'timestamp' => now(),
            ]);

            return 0;

        } catch (\Exception $e) {
            // Reverter transação em caso de erro
            DB::rollBack();

            $this->error('❌ Erro ao remover usuários: '.$e->getMessage());
            Log::error('Error clearing users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
