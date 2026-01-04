<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class R2Helper
{
    /**
     * Gera uma URL temporária para um arquivo no R2
     *
     * @param  string  $path  Caminho do arquivo no R2
     * @param  int  $minutes  Tempo de expiração em minutos
     * @return string URL temporária
     */
    public static function getTemporaryUrl($path, $minutes = 60)
    {
        if (empty($path)) {
            return null;
        }

        try {
            // Gera URL temporária para o bucket ganhoubetdoc
            return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar URL temporária para R2: '.$e->getMessage());

            // Fallback: tentar construir URL pública se disponível
            try {
                $r2Url = config('filesystems.disks.r2.url');
                if ($r2Url) {
                    return $r2Url.'/'.$path;
                }
            } catch (\Exception $ex) {
                // Ignorar erro do fallback
            }

            return null;
        }
    }

    /**
     * Converte um caminho de arquivo do storage público para R2
     *
     * @param  string  $publicPath  Caminho no storage público
     * @return string Caminho no formato R2
     */
    public static function convertPublicPathToR2($publicPath)
    {
        if (empty($publicPath)) {
            return null;
        }

        // Remove o prefixo 'uploads/' e adiciona 'user/documents/'
        if (strpos($publicPath, 'uploads/') === 0) {
            return 'user/documents/'.substr($publicPath, 8);
        }

        return $publicPath;
    }

    /**
     * Lista arquivos no bucket R2
     *
     * @param  string  $directory  Diretório a ser listado (opcional)
     * @param  bool  $recursive  Listar arquivos recursivamente
     * @return array Lista de arquivos
     */
    public static function listFiles($directory = '', $recursive = false)
    {
        try {
            $files = Storage::disk('r2')->listContents($directory, $recursive);
            $filesList = [];

            foreach ($files as $file) {
                $filesList[] = [
                    'path' => $file['path'],
                    'type' => $file['type'],
                    'size' => $file['size'] ?? 0,
                    'timestamp' => $file['timestamp'] ?? null,
                ];
            }

            return $filesList;
        } catch (\Exception $e) {
            Log::error('Erro ao listar arquivos do R2: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Faz upload de um arquivo para o R2
     *
     * @param  UploadedFile  $file  Arquivo a ser enviado
     * @param  string  $path  Caminho onde o arquivo será armazenado
     * @param  string|null  $filename  Nome personalizado para o arquivo (opcional)
     * @return string|null Caminho do arquivo no R2 ou null em caso de erro
     */
    public static function uploadFile(UploadedFile $file, string $path, ?string $filename = null)
    {
        try {
            // Gera um nome único para o arquivo se não for especificado
            if (empty($filename)) {
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid().'.'.$extension;
            }

            // Caminho completo no R2
            $fullPath = trim($path, '/').'/'.$filename;

            // Faz o upload do arquivo para o R2
            $uploaded = Storage::disk('r2')->put($fullPath, file_get_contents($file));

            if ($uploaded) {
                return $fullPath;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload para R2: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Exclui um arquivo do R2
     *
     * @param  string  $path  Caminho do arquivo no R2
     * @return bool Verdadeiro se o arquivo foi excluído com sucesso
     */
    public static function deleteFile(string $path)
    {
        try {
            if (empty($path)) {
                return false;
            }

            return Storage::disk('r2')->delete($path);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir arquivo do R2: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Verifica se um arquivo existe no R2
     *
     * @param  string  $path  Caminho do arquivo no R2
     * @return bool Verdadeiro se o arquivo existe
     */
    public static function fileExists(string $path)
    {
        try {
            if (empty($path)) {
                return false;
            }

            return Storage::disk('r2')->exists($path);
        } catch (\Exception $e) {
            Log::error('Erro ao verificar existência de arquivo no R2: '.$e->getMessage());

            return false;
        }
    }
}
