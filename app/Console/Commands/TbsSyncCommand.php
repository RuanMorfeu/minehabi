<?php

namespace App\Console\Commands;

use App\Services\Providers\TbsService;
use Illuminate\Console\Command;

class TbsSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbs:sync {--dry-run : Executa sem salvar dados} {--force : Força a sincronização sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza jogos da TBS API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $allowedProviders = ['evolution', 'spribe', 'galaxsys'];

        $this->info('Iniciando sincronização de jogos da TBS API...');

        if ($dryRun) {
            $this->warn('Modo de teste ativado. Nenhum dado será salvo.');
        }

        if (! $force && ! $dryRun) {
            if (! $this->confirm('Tem certeza que deseja sincronizar os jogos da TBS API? Esta ação pode demorar alguns minutos.')) {
                $this->info('Sincronização cancelada.');

                return 0;
            }
        }

        $tbsService = new TbsService;
        $result = $tbsService->sync($dryRun);

        $this->info('Sincronização concluída!');
        $this->info('');
        $this->info('Resumo:');
        $this->info('Total de jogos na API: '.$result['total_games']);
        $this->info('Jogos filtrados (apenas '.implode(', ', $allowedProviders).'): '.$result['filtered_games']);

        if (! $dryRun) {
            $this->info('Jogos criados: '.$result['created_games']);
            $this->info('Jogos atualizados: '.$result['updated_games']);
            $this->info('Provedores criados: '.$result['created_providers']);
            $this->info('Provedores atualizados: '.$result['updated_providers']);
        }

        $this->info('');
        $this->info('Provedores:');

        $bar = $this->output->createProgressBar(count($result['providers']));
        $bar->start();

        foreach ($result['providers'] as $code => $provider) {
            if (in_array($code, $allowedProviders)) {
                $this->info('');
                $this->info('- '.$provider['name'].': '.$provider['games'].' jogos');
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info('');

        return 0;
    }
}
