<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AnalisarPaisesNumerosTelefone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analisar:paises-telefone {--arquivo= : Arquivo CSV específico para analisar} {--diretorio=storage/app/facebook-ads : Diretório dos arquivos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analisa os prefixos de telefone para identificar os países';

    /**
     * Lista de prefixos de países e seus nomes
     */
    private $prefixosPaises = [
        '351' => 'Portugal',
        '55' => 'Brasil',
        '1' => 'EUA/Canadá',
        '44' => 'Reino Unido',
        '34' => 'Espanha',
        '33' => 'França',
        '49' => 'Alemanha',
        '39' => 'Itália',
        '31' => 'Holanda',
        '32' => 'Bélgica',
        '41' => 'Suíça',
        '43' => 'Áustria',
        '46' => 'Suécia',
        '47' => 'Noruega',
        '48' => 'Polônia',
        '54' => 'Argentina',
        '56' => 'Chile',
        '57' => 'Colômbia',
        '58' => 'Venezuela',
        '52' => 'México',
        '81' => 'Japão',
        '82' => 'Coreia do Sul',
        '86' => 'China',
        '91' => 'Índia',
        '61' => 'Austrália',
        '64' => 'Nova Zelândia',
        '27' => 'África do Sul',
        '20' => 'Egito',
        '212' => 'Marrocos',
        '213' => 'Argélia',
        '216' => 'Tunísia',
        '218' => 'Líbia',
        '220' => 'Gâmbia',
        '221' => 'Senegal',
        '222' => 'Mauritânia',
        '223' => 'Mali',
        '224' => 'Guiné',
        '225' => 'Costa do Marfim',
        '226' => 'Burkina Faso',
        '227' => 'Níger',
        '228' => 'Togo',
        '229' => 'Benin',
        '230' => 'Maurício',
        '231' => 'Libéria',
        '232' => 'Serra Leoa',
        '233' => 'Gana',
        '234' => 'Nigéria',
        '235' => 'Chade',
        '236' => 'República Centro-Africana',
        '237' => 'Camarões',
        '238' => 'Cabo Verde',
        '239' => 'São Tomé e Príncipe',
        '240' => 'Guiné Equatorial',
        '241' => 'Gabão',
        '242' => 'Congo',
        '243' => 'República Democrática do Congo',
        '244' => 'Angola',
        '245' => 'Guiné-Bissau',
        '248' => 'Seychelles',
        '249' => 'Sudão',
        '250' => 'Ruanda',
        '251' => 'Etiópia',
        '252' => 'Somália',
        '253' => 'Djibouti',
        '254' => 'Quênia',
        '255' => 'Tanzânia',
        '256' => 'Uganda',
        '257' => 'Burundi',
        '258' => 'Moçambique',
        '260' => 'Zâmbia',
        '261' => 'Madagascar',
        '262' => 'Reunião',
        '263' => 'Zimbábue',
        '264' => 'Namíbia',
        '265' => 'Malawi',
        '266' => 'Lesoto',
        '267' => 'Botsuana',
        '268' => 'Suazilândia',
        '269' => 'Comores',
        '290' => 'Santa Helena',
        '291' => 'Eritreia',
        '297' => 'Aruba',
        '298' => 'Ilhas Faroé',
        '299' => 'Groenlândia',
        '350' => 'Gibraltar',
        '352' => 'Luxemburgo',
        '353' => 'Irlanda',
        '354' => 'Islândia',
        '355' => 'Albânia',
        '356' => 'Malta',
        '357' => 'Chipre',
        '358' => 'Finlândia',
        '359' => 'Bulgária',
        '370' => 'Lituânia',
        '371' => 'Letônia',
        '372' => 'Estônia',
        '373' => 'Moldávia',
        '374' => 'Armênia',
        '375' => 'Bielorrússia',
        '376' => 'Andorra',
        '377' => 'Mônaco',
        '378' => 'San Marino',
        '380' => 'Ucrânia',
        '381' => 'Sérvia',
        '382' => 'Montenegro',
        '385' => 'Croácia',
        '386' => 'Eslovênia',
        '387' => 'Bósnia e Herzegovina',
        '389' => 'Macedônia do Norte',
        '420' => 'República Tcheca',
        '421' => 'Eslováquia',
        '423' => 'Liechtenstein',
        '500' => 'Ilhas Malvinas',
        '501' => 'Belize',
        '502' => 'Guatemala',
        '503' => 'El Salvador',
        '504' => 'Honduras',
        '505' => 'Nicarágua',
        '506' => 'Costa Rica',
        '507' => 'Panamá',
        '509' => 'Haiti',
        '590' => 'Guadalupe',
        '591' => 'Bolívia',
        '592' => 'Guiana',
        '593' => 'Equador',
        '594' => 'Guiana Francesa',
        '595' => 'Paraguai',
        '596' => 'Martinica',
        '597' => 'Suriname',
        '598' => 'Uruguai',
        '599' => 'Antilhas Holandesas',
        '670' => 'Timor-Leste',
        '672' => 'Antártida',
        '673' => 'Brunei',
        '674' => 'Nauru',
        '675' => 'Papua-Nova Guiné',
        '676' => 'Tonga',
        '677' => 'Ilhas Salomão',
        '678' => 'Vanuatu',
        '679' => 'Fiji',
        '680' => 'Palau',
        '681' => 'Wallis e Futuna',
        '682' => 'Ilhas Cook',
        '683' => 'Niue',
        '685' => 'Samoa',
        '686' => 'Kiribati',
        '687' => 'Nova Caledônia',
        '688' => 'Tuvalu',
        '689' => 'Polinésia Francesa',
        '690' => 'Tokelau',
        '691' => 'Micronésia',
        '692' => 'Ilhas Marshall',
        '850' => 'Coreia do Norte',
        '852' => 'Hong Kong',
        '853' => 'Macau',
        '855' => 'Camboja',
        '856' => 'Laos',
        '880' => 'Bangladesh',
        '886' => 'Taiwan',
        '960' => 'Maldivas',
        '961' => 'Líbano',
        '962' => 'Jordânia',
        '963' => 'Síria',
        '964' => 'Iraque',
        '965' => 'Kuwait',
        '966' => 'Arábia Saudita',
        '967' => 'Iêmen',
        '968' => 'Omã',
        '970' => 'Palestina',
        '971' => 'Emirados Árabes Unidos',
        '972' => 'Israel',
        '973' => 'Bahrein',
        '974' => 'Catar',
        '975' => 'Butão',
        '976' => 'Mongólia',
        '977' => 'Nepal',
        '992' => 'Tajiquistão',
        '993' => 'Turcomenistão',
        '994' => 'Azerbaijão',
        '995' => 'Geórgia',
        '996' => 'Quirguistão',
        '998' => 'Uzbequistão',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diretorio = $this->option('diretorio');
        $arquivoEspecifico = $this->option('arquivo');

        if (! File::exists($diretorio)) {
            $this->error("Diretório {$diretorio} não encontrado!");

            return 1;
        }

        $arquivos = [];

        if ($arquivoEspecifico) {
            $caminhoArquivo = $diretorio.'/'.$arquivoEspecifico;
            if (File::exists($caminhoArquivo)) {
                $arquivos[] = $arquivoEspecifico;
            } else {
                $this->error("Arquivo {$caminhoArquivo} não encontrado!");

                return 1;
            }
        } else {
            $arquivos = [
                'usuarios_sem_deposito.csv',
                'usuarios_com_deposito.csv',
                'usuarios_multiplos_depositos.csv',
                'usuarios_afiliados.csv',
            ];
        }

        foreach ($arquivos as $arquivo) {
            $caminhoArquivo = $diretorio.'/'.$arquivo;

            if (! File::exists($caminhoArquivo)) {
                $this->warn("Arquivo {$caminhoArquivo} não encontrado, pulando...");

                continue;
            }

            $this->info("Analisando arquivo: {$arquivo}");

            // Ler o arquivo CSV
            $handle = fopen($caminhoArquivo, 'r');

            if ($handle) {
                // Ler o cabeçalho
                $cabecalho = fgetcsv($handle);

                // Encontrar o índice da coluna de telefone
                $indicePhone = array_search('Telefone', $cabecalho);

                if ($indicePhone === false) {
                    $this->warn("Coluna 'Telefone' não encontrada no arquivo {$caminhoArquivo}, pulando...");
                    fclose($handle);

                    continue;
                }

                // Estatísticas de países
                $estatisticasPaises = [];
                $totalNumeros = 0;
                $numerosInvalidos = 0;

                // Ler as linhas e analisar os prefixos
                while (($linha = fgetcsv($handle)) !== false) {
                    $telefone = $linha[$indicePhone];
                    $totalNumeros++;

                    // Normalizar o número de telefone (remover espaços, traços, etc.)
                    $telefoneNormalizado = preg_replace('/[^0-9]/', '', $telefone);

                    // Ignorar telefones vazios ou muito curtos
                    if (empty($telefoneNormalizado) || strlen($telefoneNormalizado) < 5) {
                        $numerosInvalidos++;

                        continue;
                    }

                    // Identificar o país pelo prefixo
                    $paisIdentificado = $this->identificarPais($telefoneNormalizado);

                    if (! isset($estatisticasPaises[$paisIdentificado])) {
                        $estatisticasPaises[$paisIdentificado] = 0;
                    }

                    $estatisticasPaises[$paisIdentificado]++;
                }

                fclose($handle);

                // Ordenar estatísticas por quantidade (decrescente)
                arsort($estatisticasPaises);

                // Exibir estatísticas
                $this->info("Total de números analisados: {$totalNumeros}");
                $this->info("Números inválidos ou muito curtos: {$numerosInvalidos}");
                $this->info('Distribuição por país:');

                $this->table(
                    ['País', 'Quantidade', 'Porcentagem'],
                    array_map(function ($pais, $quantidade) use ($totalNumeros) {
                        $porcentagem = round(($quantidade / $totalNumeros) * 100, 2);

                        return [$pais, $quantidade, "{$porcentagem}%"];
                    }, array_keys($estatisticasPaises), array_values($estatisticasPaises))
                );

                $this->newLine();
            } else {
                $this->error("Não foi possível abrir o arquivo {$caminhoArquivo}");
            }
        }

        return 0;
    }

    /**
     * Identifica o país com base no prefixo do número de telefone
     */
    private function identificarPais($telefone)
    {
        // Verificar prefixos de 3 dígitos
        foreach (['351', '212', '213', '216', '218', '220', '221', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', '244', '245', '248', '249', '250', '251', '252', '253', '254', '255', '256', '257', '258', '260', '261', '262', '263', '264', '265', '266', '267', '268', '269', '290', '291', '297', '298', '299', '350', '352', '353', '354', '355', '356', '357', '358', '359', '370', '371', '372', '373', '374', '375', '376', '377', '378', '380', '381', '382', '385', '386', '387', '389', '420', '421', '423', '500', '501', '502', '503', '504', '505', '506', '507', '509', '590', '591', '592', '593', '594', '595', '596', '597', '598', '599', '670', '672', '673', '674', '675', '676', '677', '678', '679', '680', '681', '682', '683', '685', '686', '687', '688', '689', '690', '691', '692', '850', '852', '853', '855', '856', '880', '886', '960', '961', '962', '963', '964', '965', '966', '967', '968', '970', '971', '972', '973', '974', '975', '976', '977', '992', '993', '994', '995', '996', '998'] as $prefixo) {
            if (substr($telefone, 0, 3) === $prefixo) {
                return $this->prefixosPaises[$prefixo] ?? "Desconhecido ({$prefixo})";
            }
        }

        // Verificar prefixos de 2 dígitos
        foreach (['55', '44', '34', '33', '49', '39', '31', '32', '41', '43', '46', '47', '48', '54', '56', '57', '58', '52', '81', '82', '86', '91', '61', '64', '27', '20'] as $prefixo) {
            if (substr($telefone, 0, 2) === $prefixo) {
                return $this->prefixosPaises[$prefixo] ?? "Desconhecido ({$prefixo})";
            }
        }

        // Verificar prefixo de 1 dígito (EUA/Canadá)
        if (substr($telefone, 0, 1) === '1') {
            return $this->prefixosPaises['1'];
        }

        // Se começar com zero, pode ser um número nacional sem o prefixo internacional
        if (substr($telefone, 0, 1) === '0') {
            return 'Nacional (sem prefixo internacional)';
        }

        return 'Desconhecido';
    }
}
