<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function downloadZip(Request $request)
    {
        // Obter o caminho do arquivo da sessão
        $zipFilePath = session('download_zip_path');

        // Verificar se o caminho existe na sessão
        if (! $zipFilePath || ! file_exists($zipFilePath)) {
            return response()->json(['error' => 'Arquivo não encontrado'], 404);
        }

        // Limpar a sessão
        session()->forget('download_zip_path');

        // Retornar o arquivo para download
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
