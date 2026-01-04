<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ImageProxyController extends Controller
{
    /**
     * Serve uma imagem do R2 através de proxy
     */
    public function serve(Request $request, $path)
    {
        try {
            // Decodifica o caminho
            $decodedPath = base64_decode($path);

            // Verifica se o arquivo existe no R2
            if (! Storage::disk('r2')->exists($decodedPath)) {
                return Response::make('Arquivo não encontrado', 404);
            }

            // Obtém o conteúdo do arquivo
            $content = Storage::disk('r2')->get($decodedPath);

            // Determina o tipo MIME baseado na extensão
            $extension = pathinfo($decodedPath, PATHINFO_EXTENSION);
            $mimeType = match (strtolower($extension)) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'application/octet-stream'
            };

            return Response::make($content, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=3600', // Cache por 1 hora
                'Content-Length' => strlen($content),
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao servir imagem via proxy: '.$e->getMessage());

            return Response::make('Erro interno do servidor', 500);
        }
    }
}
