<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GerarListaNumerosTelefone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gerar:lista-telefones 
                            {--diretorio=storage/app/facebook-ads : Diretório dos arquivos CSV} 
                            {--saida=storage/app/facebook-ads/numeros : Diretório de saída para os arquivos TXT}
                            {--arquivos= : Lista de arquivos específicos a serem processados, separados por vírgula}
                            {--data-inicial= : Data inicial para filtrar os arquivos (formato Y-m-d)}
                            {--data-final= : Data final para filtrar os arquivos (formato Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera listas de números de telefone em formato TXT com o prefixo +';

    /**
     * Lista de prefixos de países e seus códigos
     */
    private $prefixosPaises = [
        'Portugal' => '351',
        'Brasil' => '55',
        'EUA/Canadá' => '1',
        'Reino Unido' => '44',
        'Espanha' => '34',
        'França' => '33',
        'Alemanha' => '49',
        'Itália' => '39',
        'Argentina' => '54',
        'Chile' => '56',
        'Colômbia' => '57',
        'Venezuela' => '58',
        'México' => '52',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diretorioEntrada = $this->option('diretorio');
        $diretorioSaida = $this->option('saida');
        $dataInicial = $this->option('data-inicial');
        $dataFinal = $this->option('data-final');

        // Verificar se o diretório de entrada existe
        if (! File::exists($diretorioEntrada)) {
            $this->error("Diretório de entrada {$diretorioEntrada} não encontrado!");

            return 1;
        }

        // Criar diretório de saída se não existir
        if (! File::exists($diretorioSaida)) {
            File::makeDirectory($diretorioSaida, 0755, true);
        }

        // Verificar se foram especificados arquivos específicos
        $arquivosParam = $this->option('arquivos');

        if ($arquivosParam) {
            $arquivos = explode(',', $arquivosParam);
        } else {
            $arquivos = [
                'usuarios_sem_deposito.csv',
                'usuarios_com_deposito.csv',
                'usuarios_multiplos_depositos.csv',
                'usuarios_afiliados.csv',
            ];
        }

        foreach ($arquivos as $arquivo) {
            $caminhoArquivo = $diretorioEntrada.'/'.$arquivo;

            if (! File::exists($caminhoArquivo)) {
                $this->warn("Arquivo {$caminhoArquivo} não encontrado, pulando...");

                continue;
            }

            $this->info("Processando arquivo: {$arquivo}");

            // Nome do arquivo de saída (substituindo .csv por .txt)
            $nomeArquivoSaida = str_replace('.csv', '.txt', $arquivo);
            $caminhoArquivoSaida = $diretorioSaida.'/'.$nomeArquivoSaida;

            // Ler o arquivo CSV
            $handle = fopen($caminhoArquivo, 'r');

            if ($handle) {
                // Ler o cabeçalho
                $cabecalho = fgetcsv($handle);

                // Encontrar o índice da coluna de telefone e data de criação
                $indiceColunaTelefone = array_search('phone', $cabecalho);
                $indiceColunaData = array_search('created_at', $cabecalho);

                if ($indiceColunaTelefone === false) {
                    $this->error("Coluna 'phone' não encontrada no arquivo {$arquivo}");
                    fclose($handle);

                    continue;
                }

                // Verificar se temos a coluna de data para filtrar
                $filtrarPorData = false;
                if ($indiceColunaData !== false && $dataInicial && $dataFinal) {
                    $filtrarPorData = true;
                    $this->info("Filtrando por data: de {$dataInicial} até {$dataFinal}");
                }

                // Array para armazenar os números formatados
                $numerosTelefone = [];
                $totalNumeros = 0;
                $numerosValidos = 0;

                // Ler as linhas e extrair os números de telefone
                while (($linha = fgetcsv($handle)) !== false) {
                    // Verificar se o telefone existe
                    if (! isset($linha[$indiceColunaTelefone]) || empty($linha[$indiceColunaTelefone])) {
                        continue;
                    }

                    // Verificar filtro de data se aplicável
                    if ($filtrarPorData && isset($linha[$indiceColunaData]) && ! empty($linha[$indiceColunaData])) {
                        $dataUsuario = substr($linha[$indiceColunaData], 0, 10); // Formato Y-m-d

                        // Pular se a data estiver fora do intervalo
                        if ($dataUsuario < $dataInicial || $dataUsuario > $dataFinal) {
                            continue;
                        }
                    }

                    $telefone = trim($linha[$indiceColunaTelefone]);
                    $telefoneNormalizado = preg_replace('/[^0-9]/', '', $telefone);

                    // Ignorar telefones vazios ou muito curtos
                    if (empty($telefoneNormalizado) || strlen($telefoneNormalizado) < 5) {
                        continue;
                    }

                    // Formatar o número com o prefixo +
                    $numeroFormatado = $this->formatarNumeroComPrefixo($telefoneNormalizado);

                    if ($numeroFormatado) {
                        $numerosTelefone[] = $numeroFormatado;
                        $numerosValidos++;
                    }

                    $totalNumeros++;
                }

                fclose($handle);

                // Remover duplicatas
                $numerosTelefone = array_unique($numerosTelefone);

                // Ordenar os números
                sort($numerosTelefone);

                // Escrever os números no arquivo de saída
                File::put($caminhoArquivoSaida, implode("\n", $numerosTelefone));

                $this->info("Total de números processados: {$totalNumeros}");
                $this->info("Números válidos encontrados: {$numerosValidos}");
                $this->info('Números únicos após remoção de duplicatas: '.count($numerosTelefone));
                $this->info("Arquivo gerado: {$caminhoArquivoSaida}");
                $this->newLine();
            } else {
                $this->error("Não foi possível abrir o arquivo {$caminhoArquivo}");
            }
        }

        return 0;
    }

    /**
     * Formata o número de telefone com o prefixo internacional +
     */
    private function formatarNumeroComPrefixo($telefone)
    {
        // Se o número já começa com +, retornar como está
        if (substr($telefone, 0, 1) === '+') {
            return $telefone;
        }

        // Verificar prefixos de 3 dígitos (Portugal e outros)
        if (substr($telefone, 0, 3) === '351') {
            // Número já tem o prefixo de Portugal
            return '+'.$telefone;
        }

        // Verificar prefixos de 2 dígitos (Brasil e outros)
        if (substr($telefone, 0, 2) === '55') {
            // Número já tem o prefixo do Brasil
            return '+'.$telefone;
        }

        // Verificar prefixo de 1 dígito (EUA/Canadá)
        if (substr($telefone, 0, 1) === '1') {
            // Número já tem o prefixo dos EUA/Canadá
            return '+'.$telefone;
        }

        // Se o número começa com 9, 6 ou 2 (padrão português) e tem entre 9 e 10 dígitos, adicionar prefixo de Portugal
        if (preg_match('/^[926]\d{8,9}$/', $telefone)) {
            return '+351'.$telefone;
        }

        // Se o número começa com 0, remover o 0 e adicionar o prefixo de Portugal
        if (substr($telefone, 0, 1) === '0' && strlen($telefone) >= 6) {
            return '+351'.substr($telefone, 1);
        }

        // Para outros números, assumir que são de Portugal (já que a maioria é)
        if (strlen($telefone) >= 5) {
            return '+351'.$telefone;
        }

        return null;
    }
}
