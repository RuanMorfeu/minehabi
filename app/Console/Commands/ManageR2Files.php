<?php

namespace App\Console\Commands;

use App\Helpers\R2Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ManageR2Files extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'r2:manage 
                            {action : Ação a ser executada (list, upload, delete, clean)}
                            {--directory= : Diretório para listar/limpar arquivos}
                            {--file= : Caminho do arquivo local para upload}
                            {--path= : Caminho do arquivo no R2 para excluir}
                            {--days= : Número de dias para limpar arquivos antigos}
                            {--recursive : Listar arquivos recursivamente}
                            {--force : Forçar exclusão sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerencia arquivos no Cloudflare R2 (listar, upload, excluir, limpar)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listFiles();
                break;
            case 'upload':
                $this->uploadFile();
                break;
            case 'delete':
                $this->deleteFile();
                break;
            case 'clean':
                $this->cleanOldFiles();
                break;
            default:
                $this->error("Ação inválida: {$action}");
                $this->info('Ações disponíveis: list, upload, delete, clean');

                return 1;
        }

        return 0;
    }

    /**
     * Lista arquivos no bucket R2
     */
    protected function listFiles()
    {
        $directory = $this->option('directory') ?? '';
        $recursive = $this->option('recursive') ?? false;

        $this->info('Listando arquivos no diretório: '.($directory ?: 'raiz').($recursive ? ' (recursivamente)' : ''));

        $files = R2Helper::listFiles($directory, $recursive);

        if (empty($files)) {
            $this->warn('Nenhum arquivo encontrado');

            return;
        }

        $headers = ['Caminho', 'Tipo', 'Tamanho', 'Data'];
        $rows = [];

        foreach ($files as $file) {
            $rows[] = [
                $file['path'],
                $file['type'],
                $this->formatBytes($file['size'] ?? 0),
                $file['timestamp'] ? date('Y-m-d H:i:s', $file['timestamp']) : 'N/A',
            ];
        }

        $this->table($headers, $rows);
        $this->info('Total de arquivos: '.count($files));
    }

    /**
     * Faz upload de um arquivo para o R2
     */
    protected function uploadFile()
    {
        $localFile = $this->option('file');

        if (empty($localFile)) {
            $this->error('Você deve especificar o caminho do arquivo local com --file');

            return;
        }

        if (! file_exists($localFile)) {
            $this->error("Arquivo não encontrado: {$localFile}");

            return;
        }

        $directory = $this->option('directory') ?? 'uploads';
        $filename = basename($localFile);
        $path = trim($directory, '/').'/'.$filename;

        $this->info("Fazendo upload do arquivo {$localFile} para {$path}...");

        try {
            $contents = file_get_contents($localFile);
            $uploaded = Storage::disk('r2')->put($path, $contents);

            if ($uploaded) {
                $this->info('Upload concluído com sucesso!');

                // Gerar URL temporária
                $url = R2Helper::getTemporaryUrl($path);
                if ($url) {
                    $this->info("URL temporária (válida por 60 minutos): {$url}");
                }
            } else {
                $this->error('Falha ao fazer upload do arquivo');
            }
        } catch (\Exception $e) {
            $this->error('Erro ao fazer upload: '.$e->getMessage());
        }
    }

    /**
     * Exclui um arquivo do R2
     */
    protected function deleteFile()
    {
        $path = $this->option('path');

        if (empty($path)) {
            $this->error('Você deve especificar o caminho do arquivo no R2 com --path');

            return;
        }

        if (! $this->option('force') && ! $this->confirm("Tem certeza que deseja excluir o arquivo {$path}?")) {
            $this->info('Operação cancelada');

            return;
        }

        $this->info("Excluindo arquivo {$path}...");

        try {
            $deleted = R2Helper::deleteFile($path);

            if ($deleted) {
                $this->info('Arquivo excluído com sucesso!');
            } else {
                $this->error('Falha ao excluir o arquivo. Verifique se o arquivo existe.');
            }
        } catch (\Exception $e) {
            $this->error('Erro ao excluir arquivo: '.$e->getMessage());
        }
    }

    /**
     * Limpa arquivos antigos do R2
     */
    protected function cleanOldFiles()
    {
        $directory = $this->option('directory');

        if (empty($directory)) {
            $this->error('Você deve especificar o diretório para limpar com --directory');

            return;
        }

        $days = $this->option('days') ?? 30;

        if (! $this->option('force') && ! $this->confirm("Tem certeza que deseja excluir arquivos com mais de {$days} dias no diretório {$directory}?")) {
            $this->info('Operação cancelada');

            return;
        }

        $this->info("Listando arquivos no diretório {$directory}...");

        try {
            $files = Storage::disk('r2')->listContents($directory, true);
            $cutoffTime = time() - ($days * 24 * 60 * 60);
            $deletedCount = 0;
            $totalFiles = 0;

            foreach ($files as $file) {
                if ($file['type'] === 'file') {
                    $totalFiles++;

                    if (isset($file['timestamp']) && $file['timestamp'] < $cutoffTime) {
                        if (Storage::disk('r2')->delete($file['path'])) {
                            $deletedCount++;
                            $this->line('Excluído: '.$file['path']);
                        }
                    }
                }
            }

            $this->info("Limpeza concluída: {$deletedCount} de {$totalFiles} arquivos excluídos");
        } catch (\Exception $e) {
            $this->error('Erro ao limpar arquivos: '.$e->getMessage());
        }
    }

    /**
     * Formata o tamanho em bytes para uma representação mais legível
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), $precision).' '.$units[$i];
    }
}
