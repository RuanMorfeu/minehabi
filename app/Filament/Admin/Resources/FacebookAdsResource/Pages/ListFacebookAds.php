<?php

namespace App\Filament\Admin\Resources\FacebookAdsResource\Pages;

use App\Filament\Admin\Resources\FacebookAdsResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class ListFacebookAds extends ListRecords
{
    protected static string $resource = FacebookAdsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gerarCSV')
                ->label('Gerar Listas CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Section::make('Filtro de Datas')
                        ->description('Selecione o período para filtrar os usuários')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('data_inicial')
                                        ->label('Data Inicial')
                                        ->required(),
                                    DatePicker::make('data_final')
                                        ->label('Data Final')
                                        ->required()
                                        ->default(now()),
                                ]),
                            Select::make('tipos_lista')
                                ->label('Tipos de Lista')
                                ->options([
                                    'todos' => 'Todas as Listas',
                                    'sem_deposito' => 'Usuários sem Depósito',
                                    'com_deposito' => 'Usuários com Depósito',
                                    'multiplos_depositos' => 'Usuários com Múltiplos Depósitos',
                                    'afiliados' => 'Afiliados (Todos)',
                                    'afiliados_com_indicacao' => 'Afiliados COM Indicação',
                                    'afiliados_sem_indicacao' => 'Afiliados SEM Indicação',
                                    'transacoes_nao_concluidas' => 'Transações não concluídas (sem depósito)',
                                ])
                                ->multiple()
                                ->required()
                                ->default(['todos']),
                        ]),
                ])
                ->action(function (array $data): void {
                    // Executar o comando para gerar os arquivos CSV
                    $dataInicial = $data['data_inicial'] instanceof \DateTime ? $data['data_inicial']->format('Y-m-d') : $data['data_inicial'];
                    $dataFinal = $data['data_final'] instanceof \DateTime ? $data['data_final']->format('Y-m-d') : $data['data_final'];
                    $tiposLista = $data['tipos_lista'];

                    $comando = 'extracao:facebook-ads';
                    $parametros = [
                        '--formato' => 'csv',
                        '--data-inicial' => $dataInicial,
                        '--data-final' => $dataFinal,
                    ];

                    if (! in_array('todos', $tiposLista)) {
                        $parametros['--tipos'] = implode(',', $tiposLista);
                    }

                    Artisan::call($comando, $parametros);

                    // Preparar arquivos para download
                    $diretorio = 'facebook-ads';
                    $arquivos = [];

                    if (in_array('todos', $tiposLista) || in_array('sem_deposito', $tiposLista)) {
                        $arquivos[] = 'usuarios_sem_deposito.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('com_deposito', $tiposLista)) {
                        $arquivos[] = 'usuarios_com_deposito.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('multiplos_depositos', $tiposLista)) {
                        $arquivos[] = 'usuarios_multiplos_depositos.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados', $tiposLista)) {
                        $arquivos[] = 'usuarios_afiliados.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados_com_indicacao', $tiposLista)) {
                        $arquivos[] = 'afiliados_com_indicacao.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados_sem_indicacao', $tiposLista)) {
                        $arquivos[] = 'afiliados_sem_indicacao.csv';
                    }

                    if (in_array('todos', $tiposLista) || in_array('transacoes_nao_concluidas', $tiposLista)) {
                        $arquivos[] = 'usuarios_transacoes_nao_concluidas_sem_deposito.csv';
                    }

                    // Criar arquivo ZIP com as listas selecionadas
                    $zipFileName = 'listas_facebook_ads_'.date('Y-m-d_His').'.zip';
                    $zipFilePath = storage_path('app/'.$zipFileName);

                    $zip = new ZipArchive;
                    if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                        foreach ($arquivos as $arquivo) {
                            $caminhoArquivo = storage_path('app/'.$diretorio.'/'.$arquivo);
                            if (file_exists($caminhoArquivo)) {
                                $zip->addFile($caminhoArquivo, $arquivo);
                            }
                        }
                        $zip->close();
                    }

                    // Fazer download do arquivo ZIP
                    if (file_exists($zipFilePath)) {
                        // Armazenar o caminho do arquivo na sessão
                        session(['download_zip_path' => $zipFilePath]);

                        // Redirecionar para a rota de download
                        $this->redirect(route('download.zip'));
                    } else {
                        $this->notify('error', 'Erro ao gerar o arquivo ZIP');
                    }
                }),

            Action::make('gerarTXT')
                ->label('Gerar Listas TXT')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->form([
                    Section::make('Filtro de Datas')
                        ->description('Selecione o período para filtrar os usuários')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('data_inicial')
                                        ->label('Data Inicial')
                                        ->required(),
                                    DatePicker::make('data_final')
                                        ->label('Data Final')
                                        ->required()
                                        ->default(now()),
                                ]),
                            Select::make('tipos_lista')
                                ->label('Tipos de Lista')
                                ->options([
                                    'todos' => 'Todas as Listas',
                                    'sem_deposito' => 'Usuários sem Depósito',
                                    'com_deposito' => 'Usuários com Depósito',
                                    'multiplos_depositos' => 'Usuários com Múltiplos Depósitos',
                                    'afiliados' => 'Afiliados (Todos)',
                                    'afiliados_com_indicacao' => 'Afiliados COM Indicação',
                                    'afiliados_sem_indicacao' => 'Afiliados SEM Indicação',
                                    'transacoes_nao_concluidas' => 'Transações não concluídas (sem depósito)',
                                ])
                                ->multiple()
                                ->required()
                                ->default(['todos']),
                        ]),
                ])
                ->action(function (array $data): void {
                    // Extrair dados do formulário
                    $dataInicial = $data['data_inicial'] instanceof \DateTime ? $data['data_inicial']->format('Y-m-d') : $data['data_inicial'];
                    $dataFinal = $data['data_final'] instanceof \DateTime ? $data['data_final']->format('Y-m-d') : $data['data_final'];
                    $tiposLista = $data['tipos_lista'];

                    // Executar o comando para extrair usuários diretamente para TXT
                    $comando = 'extracao:facebook-ads';
                    $parametros = [
                        '--formato' => 'txt',
                        '--data-inicial' => $dataInicial,
                        '--data-final' => $dataFinal,
                    ];

                    if (! in_array('todos', $tiposLista)) {
                        $parametros['--tipos'] = implode(',', $tiposLista);
                    }

                    Artisan::call($comando, $parametros);

                    // Preparar arquivos para download
                    $diretorio = 'facebook-ads';
                    $arquivos = [];

                    if (in_array('todos', $tiposLista) || in_array('sem_deposito', $tiposLista)) {
                        $arquivos[] = 'usuarios_sem_deposito.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('com_deposito', $tiposLista)) {
                        $arquivos[] = 'usuarios_com_deposito.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('multiplos_depositos', $tiposLista)) {
                        $arquivos[] = 'usuarios_multiplos_depositos.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados', $tiposLista)) {
                        $arquivos[] = 'usuarios_afiliados.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados_com_indicacao', $tiposLista)) {
                        $arquivos[] = 'afiliados_com_indicacao.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('afiliados_sem_indicacao', $tiposLista)) {
                        $arquivos[] = 'afiliados_sem_indicacao.txt';
                    }

                    if (in_array('todos', $tiposLista) || in_array('transacoes_nao_concluidas', $tiposLista)) {
                        $arquivos[] = 'usuarios_transacoes_nao_concluidas_sem_deposito.txt';
                    }

                    // Criar arquivo ZIP com as listas selecionadas
                    $zipFileName = 'listas_facebook_ads_txt_'.date('Y-m-d_His').'.zip';
                    $zipFilePath = storage_path('app/'.$zipFileName);

                    $zip = new ZipArchive;
                    if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
                        foreach ($arquivos as $arquivo) {
                            $caminhoArquivo = storage_path('app/'.$diretorio.'/'.$arquivo);
                            if (file_exists($caminhoArquivo)) {
                                $zip->addFile($caminhoArquivo, $arquivo);
                            }
                        }
                        $zip->close();
                    }

                    // Fazer download do arquivo ZIP
                    if (file_exists($zipFilePath)) {
                        // Armazenar o caminho do arquivo na sessão
                        session(['download_zip_path' => $zipFilePath]);

                        // Redirecionar para a rota de download
                        $this->redirect(route('download.zip'));
                    } else {
                        $this->notify('error', 'Erro ao gerar o arquivo ZIP');
                    }
                }),
        ];
    }
}
