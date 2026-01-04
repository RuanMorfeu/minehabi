<?php

namespace App\Console\Commands;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExtrairUsuariosFacebookAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extracao:facebook-ads 
                            {--formato=csv : Formato de saída (csv ou txt)}
                            {--data-inicial= : Data inicial para filtrar usuários (formato: Y-m-d)}
                            {--data-final= : Data final para filtrar usuários (formato: Y-m-d)}
                            {--tipos= : Tipos de lista a serem geradas (sem_deposito,com_deposito,multiplos_depositos,afiliados,afiliados_com_indicacao,afiliados_sem_indicacao,transacoes_nao_concluidas)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrai listas de usuários para Facebook Ads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $formato = $this->option('formato');
        $diretorio = storage_path('app/facebook-ads');
        $dataInicial = $this->option('data-inicial');
        $dataFinal = $this->option('data-final');
        $tipos = $this->option('tipos');

        // Criar diretório se não existir
        if (! File::exists($diretorio)) {
            File::makeDirectory($diretorio, 0755, true);
        }

        $this->info('Iniciando extração de usuários para Facebook Ads...');

        // Converter string de tipos em array
        $tiposArray = $tipos ? explode(',', $tipos) : [];

        // 1. Usuários que se registraram e não depositaram
        if (empty($tiposArray) || in_array('sem_deposito', $tiposArray)) {
            $this->extrairUsuariosRegistradosSemDeposito($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 2. Usuários que se registraram e depositaram
        if (empty($tiposArray) || in_array('com_deposito', $tiposArray)) {
            $this->extrairUsuariosComDeposito($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 3. Usuários que se registraram e depositaram mais de 1 vez
        if (empty($tiposArray) || in_array('multiplos_depositos', $tiposArray)) {
            $this->extrairUsuariosComMultiplosDepositos($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 4. Usuários que são afiliados (TODOS)
        if (empty($tiposArray) || in_array('afiliados', $tiposArray)) {
            $this->extrairUsuariosAfiliados($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 4a. Afiliados COM indicação (já indicaram pessoas)
        if (empty($tiposArray) || in_array('afiliados_com_indicacao', $tiposArray)) {
            $this->extrairAfiliadosComIndicacao($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 4b. Afiliados SEM indicação (nunca indicaram ninguém)
        if (empty($tiposArray) || in_array('afiliados_sem_indicacao', $tiposArray)) {
            $this->extrairAfiliadosSemIndicacao($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 5. Usuários com transações não concluídas e sem depósito
        if (empty($tiposArray) || in_array('transacoes_nao_concluidas', $tiposArray)) {
            $this->extrairUsuariosComTransacoesNaoConcluidasSemDeposito($diretorio, $formato, $dataInicial, $dataFinal);
        }

        // 6. Filtrar registros duplicados
        $this->filtrarDuplicados($diretorio, $formato);

        $this->info('Extração concluída com sucesso! Os arquivos foram salvos em: '.$diretorio);
    }

    /**
     * Extrai usuários que se registraram mas nunca depositaram
     */
    private function extrairUsuariosRegistradosSemDeposito($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo usuários registrados sem depósito...');

        // Usuários que não têm depósitos
        $usuariosComDeposito = Deposit::where('status', 1)
            ->distinct()
            ->pluck('user_id');

        $query = User::whereNotIn('id', $usuariosComDeposito)
            ->where('banned', 0);

        // Aplicar filtro de data se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'usuarios_sem_deposito', $formato);
        $this->info('Total de usuários sem depósito: '.$usuarios->count());
    }

    /**
     * Extrai usuários que se registraram e fizeram pelo menos um depósito
     */
    private function extrairUsuariosComDeposito($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo usuários com depósito...');

        // Usuários que têm pelo menos um depósito
        $depositosQuery = Deposit::where('status', 1);

        // Aplicar filtro de data aos depósitos se fornecido
        if ($dataInicial) {
            $depositosQuery->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $depositosQuery->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuariosIds = $depositosQuery->distinct()->pluck('user_id');

        $query = User::whereIn('id', $usuariosIds)
            ->where('banned', 0);

        // Aplicar filtro de data aos usuários se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'usuarios_com_deposito', $formato);
        $this->info('Total de usuários com depósito: '.$usuarios->count());
    }

    /**
     * Extrai usuários que se registraram e depositaram mais de 1 vez
     */
    private function extrairUsuariosComMultiplosDepositos($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo usuários com múltiplos depósitos...');

        $depositosQuery = DB::table('deposits')
            ->select('user_id')
            ->where('status', 1);

        // Aplicar filtro de data aos depósitos se fornecido
        if ($dataInicial) {
            $depositosQuery->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $depositosQuery->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuariosIds = $depositosQuery->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        $query = User::whereIn('id', $usuariosIds)
            ->where('banned', 0);

        // Aplicar filtro de data aos usuários se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'usuarios_multiplos_depositos', $formato);
        $this->info('Total de usuários com múltiplos depósitos: '.$usuarios->count());
    }

    /**
     * Extrai usuários que são afiliados (TODOS)
     */
    private function extrairUsuariosAfiliados($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo usuários afiliados (todos)...');

        // Usuários com código de afiliado definido
        $query = User::where('banned', 0)
            ->whereNotNull('inviter_code')
            ->whereRaw('inviter_code != ""');

        // Aplicar filtro de data se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuariosIds = $query->pluck('id');

        $usuarios = User::whereIn('id', $usuariosIds)
            ->select('id', 'name', 'email', 'phone', 'created_at', 'inviter_code', 'cpa_enabled', 'affiliate_revenue_share', 'affiliate_cpa')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'usuarios_afiliados', $formato);
        $this->info('Total de usuários afiliados: '.$usuarios->count());
    }

    /**
     * Extrai afiliados que JÁ INDICARAM pessoas (COM indicação)
     */
    private function extrairAfiliadosComIndicacao($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo afiliados COM indicação...');

        // IDs de afiliados que têm pelo menos 1 pessoa indicada
        $afiliadosComIndicacao = User::whereNotNull('inviter')
            ->where('inviter', '!=', 0)
            ->distinct()
            ->pluck('inviter');

        $query = User::where('banned', 0)
            ->whereNotNull('inviter_code')
            ->whereRaw('inviter_code != ""')
            ->whereIn('id', $afiliadosComIndicacao);

        // Aplicar filtro de data se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at', 'inviter_code', 'cpa_enabled', 'affiliate_revenue_share', 'affiliate_cpa')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'afiliados_com_indicacao', $formato);
        $this->info('Total de afiliados com indicação: '.$usuarios->count());
    }

    /**
     * Extrai afiliados que NUNCA INDICARAM ninguém (SEM indicação)
     */
    private function extrairAfiliadosSemIndicacao($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo afiliados SEM indicação...');

        // IDs de afiliados que têm pelo menos 1 pessoa indicada
        $afiliadosComIndicacao = User::whereNotNull('inviter')
            ->where('inviter', '!=', 0)
            ->distinct()
            ->pluck('inviter');

        $query = User::where('banned', 0)
            ->whereNotNull('inviter_code')
            ->whereRaw('inviter_code != ""')
            ->whereNotIn('id', $afiliadosComIndicacao);

        // Aplicar filtro de data se fornecido
        if ($dataInicial) {
            $query->where('created_at', '>=', $dataInicial.' 00:00:00');
        }

        if ($dataFinal) {
            $query->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at', 'inviter_code', 'cpa_enabled', 'affiliate_revenue_share', 'affiliate_cpa')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'afiliados_sem_indicacao', $formato);
        $this->info('Total de afiliados sem indicação: '.$usuarios->count());
    }

    /**
     * Extrai usuários com transações não concluídas e que nunca depositaram
     */
    private function extrairUsuariosComTransacoesNaoConcluidasSemDeposito($diretorio, $formato, $dataInicial = null, $dataFinal = null)
    {
        $this->info('Extraindo usuários com transações não concluídas e sem depósito...');

        // Usuários que não têm depósitos concluídos (status = 1)
        $usuariosComDepositoConcluido = Deposit::where('status', 1)
            ->distinct()
            ->pluck('user_id');

        // Usuários que têm transações não concluídas (status != 1)
        $queryTransNaoConcluidas = Deposit::where('status', '!=', 1);

        // Aplicar filtro de data se fornecido
        if ($dataInicial) {
            $queryTransNaoConcluidas->where('created_at', '>=', $dataInicial.' 00:00:00');
        }
        if ($dataFinal) {
            $queryTransNaoConcluidas->where('created_at', '<=', $dataFinal.' 23:59:59');
        }

        $usuariosComTransNaoConcluidas = $queryTransNaoConcluidas->distinct()->pluck('user_id');

        // Usuários que estão na lista de transações não concluídas e não estão na lista de depósitos concluídos
        $query = User::whereIn('id', $usuariosComTransNaoConcluidas)
            ->whereNotIn('id', $usuariosComDepositoConcluido)
            ->where('banned', 0);

        $usuarios = $query->select('id', 'name', 'email', 'phone', 'created_at')
            ->get();

        $this->salvarArquivo($usuarios, $diretorio, 'usuarios_transacoes_nao_concluidas_sem_deposito', $formato);
        $this->info('Total de usuários com transações não concluídas e sem depósito: '.$usuarios->count());
    }

    /**
     * Salva os dados em um arquivo no formato especificado
     */
    private function salvarArquivo($usuarios, $diretorio, $nomeArquivo, $formato)
    {
        $caminhoArquivo = $diretorio.'/'.$nomeArquivo.'.'.$formato;

        // Determinar cabeçalhos com base no nome do arquivo
        $cabecalhos = ['ID', 'Nome', 'Email', 'Telefone', 'Data de Registro'];

        // Adicionar cabeçalhos específicos para afiliados
        if ($nomeArquivo === 'usuarios_afiliados') {
            $cabecalhos = array_merge($cabecalhos, ['Código Afiliado', 'CPA Ativado', 'Revenue Share %', 'CPA Valor']);
        }

        if ($formato === 'csv') {
            $handle = fopen($caminhoArquivo, 'w');

            // Cabeçalho
            fputcsv($handle, $cabecalhos);

            // Dados
            foreach ($usuarios as $usuario) {
                $dataRegistro = $usuario->created_at instanceof \Carbon\Carbon
                    ? $usuario->created_at->format('Y-m-d H:i:s')
                    : $usuario->created_at;

                $dados = [
                    $usuario->id,
                    $usuario->name,
                    $usuario->email,
                    $usuario->phone,
                    $dataRegistro,
                ];

                // Adicionar dados específicos para afiliados
                if ($nomeArquivo === 'usuarios_afiliados') {
                    $dados = array_merge($dados, [
                        $usuario->inviter_code ?? '',
                        isset($usuario->cpa_enabled) ? ($usuario->cpa_enabled ? 'Sim' : 'Não') : '',
                        isset($usuario->affiliate_revenue_share) ? $usuario->affiliate_revenue_share : '',
                        isset($usuario->affiliate_cpa) ? $usuario->affiliate_cpa : '',
                    ]);
                }

                fputcsv($handle, $dados);
            }

            fclose($handle);
        } else {
            // Formato TXT para Facebook Ads (apenas números de telefone formatados)
            $conteudo = '';

            foreach ($usuarios as $usuario) {
                if (isset($usuario->phone) && ! empty($usuario->phone)) {
                    // 1. Normalizar o número de telefone (remover tudo exceto dígitos)
                    $telefoneNormalizado = preg_replace('/[^0-9]/', '', $usuario->phone);

                    // 2. Ignorar números inválidos ou curtos (menos de 9 dígitos)
                    if (strlen($telefoneNormalizado) < 9) {
                        continue;
                    }

                    // 3. Adicionar o prefixo de Portugal (351) se for um número de 9 dígitos
                    if (strlen($telefoneNormalizado) === 9) {
                        $telefoneNormalizado = '351'.$telefoneNormalizado;
                    }

                    // 4. Garantir que o número final tenha o prefixo + e adicionar ao conteúdo
                    $numeroFormatado = '+'.$telefoneNormalizado;
                    $conteudo .= $numeroFormatado."\n";
                }
            }

            // Escrever o conteúdo no arquivo
            File::put($caminhoArquivo, $conteudo);
        }
    }

    /**
     * Filtra registros duplicados. Para CSV, o critério é o email. Para TXT, é o número de telefone.
     */
    private function filtrarDuplicados($diretorio, $formato)
    {
        $this->info('Filtrando registros duplicados...');

        $arquivos = [
            'usuarios_sem_deposito',
            'usuarios_com_deposito',
            'usuarios_multiplos_depositos',
            'usuarios_afiliados',
            'afiliados_com_indicacao',
            'afiliados_sem_indicacao',
            'usuarios_transacoes_nao_concluidas_sem_deposito',
        ];

        foreach ($arquivos as $arquivo) {
            $caminhoArquivo = $diretorio.'/'.$arquivo.'.'.$formato;
            $caminhoArquivoFiltrado = $diretorio.'/'.$arquivo.'_filtrado.'.$formato;

            if (! File::exists($caminhoArquivo)) {
                $this->warn("Arquivo {$caminhoArquivo} não encontrado, pulando...");

                continue;
            }

            if ($formato === 'csv') {
                // Para CSV, filtrar por email
                $this->filtrarCSVComEmailsDuplicados($caminhoArquivo, $caminhoArquivoFiltrado, $arquivo);
            } else {
                // Para TXT, continuar filtrando por número de telefone
                $this->filtrarTXTComNumerosDuplicados($caminhoArquivo, $caminhoArquivoFiltrado, $arquivo);
            }
        }
    }

    /**
     * Filtra emails duplicados em arquivos CSV, mantendo o primeiro registro encontrado.
     */
    private function filtrarCSVComEmailsDuplicados($caminhoArquivo, $caminhoArquivoFiltrado, $nomeArquivo)
    {
        // Ler o arquivo CSV
        $handle = fopen($caminhoArquivo, 'r');
        if (! $handle) {
            $this->error("Não foi possível abrir o arquivo {$caminhoArquivo}");

            return;
        }

        // Ler o cabeçalho
        $cabecalho = fgetcsv($handle);
        if ($cabecalho === false) {
            $this->error("Não foi possível ler o cabeçalho do arquivo {$caminhoArquivo}");
            fclose($handle);

            return;
        }

        // Encontrar o índice da coluna de email
        $indiceEmail = array_search('Email', $cabecalho);

        if ($indiceEmail === false) {
            $this->warn("Coluna 'Email' não encontrada no arquivo {$caminhoArquivo}. A filtragem de emails duplicados não será aplicada.");
            fclose($handle);
            // Apenas copia o arquivo para não interromper o processo
            File::copy($caminhoArquivo, $caminhoArquivoFiltrado);

            return;
        }

        // Ler as linhas e filtrar por email
        $emailsEncontrados = [];
        $linhasFiltradas = [];
        $duplicadosRemovidos = 0;

        while (($linha = fgetcsv($handle)) !== false) {
            if (! isset($linha[$indiceEmail])) {
                $linhasFiltradas[] = $linha; // Mantém a linha se a coluna de email não existir

                continue;
            }

            $email = trim(strtolower($linha[$indiceEmail]));

            // Ignorar emails vazios ou inválidos, mantendo-os na lista
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $linhasFiltradas[] = $linha;

                continue;
            }

            // Se o email ainda não foi encontrado, adiciona à lista de emails e mantém a linha
            if (! isset($emailsEncontrados[$email])) {
                $emailsEncontrados[$email] = true;
                $linhasFiltradas[] = $linha;
            } else {
                // Se o email já foi visto, é um duplicado
                $duplicadosRemovidos++;
            }
        }
        fclose($handle);

        // Escrever o arquivo filtrado
        $handleFiltrado = fopen($caminhoArquivoFiltrado, 'w');
        if (! $handleFiltrado) {
            $this->error("Não foi possível criar o arquivo filtrado {$caminhoArquivoFiltrado}");

            return;
        }

        // Escrever o cabeçalho
        fputcsv($handleFiltrado, $cabecalho);

        // Escrever as linhas filtradas
        foreach ($linhasFiltradas as $linha) {
            fputcsv($handleFiltrado, $linha);
        }
        fclose($handleFiltrado);

        $this->info("Arquivo {$nomeArquivo} filtrado por email: ".count($linhasFiltradas).' usuários únicos (removidos '.$duplicadosRemovidos.' duplicados)');

        // Substituir o arquivo original pelo filtrado
        File::move($caminhoArquivoFiltrado, $caminhoArquivo);
    }

    /**
     * Filtra números de telefone duplicados em arquivos TXT (formato para Facebook Ads)
     */
    private function filtrarTXTComNumerosDuplicados($caminhoArquivo, $caminhoArquivoFiltrado, $nomeArquivo)
    {
        // Ler o arquivo TXT
        $conteudo = File::get($caminhoArquivo);
        $linhas = explode("\n", $conteudo);
        $linhasFiltradas = [];
        $numerosTelefone = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if (empty($linha)) {
                continue;
            }

            // Remover o prefixo '+' para normalização
            $telefoneNormalizado = ltrim($linha, '+');

            // Ignorar telefones vazios ou muito curtos
            if (empty($telefoneNormalizado) || strlen($telefoneNormalizado) < 5) {
                continue;
            }

            // Se o número de telefone ainda não foi encontrado, adicionar à lista
            if (! isset($numerosTelefone[$telefoneNormalizado])) {
                $numerosTelefone[$telefoneNormalizado] = true;
                $linhasFiltradas[] = $linha;
            }
        }

        // Escrever o arquivo filtrado
        File::put($caminhoArquivoFiltrado, implode("\n", $linhasFiltradas));

        $this->info("Arquivo {$nomeArquivo} filtrado: ".count($linhasFiltradas).' números únicos (removidos '.
            (count($linhas) - count($linhasFiltradas)).' duplicados/vazios)');

        // Substituir o arquivo original pelo filtrado
        File::move($caminhoArquivoFiltrado, $caminhoArquivo);
    }
}
