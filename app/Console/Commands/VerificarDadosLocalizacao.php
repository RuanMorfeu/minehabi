<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class VerificarDadosLocalizacao extends Command
{
    protected $signature = 'verificar:localizacao';

    protected $description = 'Verifica como os dados de localização estão armazenados no banco de dados';

    public function handle()
    {
        $this->info('Verificando dados de localização no banco de dados...');

        // Buscar algumas atividades recentes com IP
        $atividades = Activity::query()
            ->whereNotNull('properties->ip')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($atividades->isEmpty()) {
            $this->error('Nenhuma atividade com IP encontrada.');

            return 1;
        }

        $this->info('Encontradas '.$atividades->count().' atividades com IP.');

        foreach ($atividades as $index => $atividade) {
            $this->info("\n--- Atividade #".($index + 1).' ---');
            $this->info('ID: '.$atividade->id);
            $this->info('Descrição: '.$atividade->description);

            // Exibir propriedades
            $this->info("\nPropriedades:");
            $properties = $atividade->properties;

            if (isset($properties['ip'])) {
                $this->info('IP: '.$properties['ip']);
            } else {
                $this->warn('IP não encontrado nas propriedades');
            }

            if (isset($properties['location'])) {
                $this->info('Location (raw): '.json_encode($properties['location']));

                // Tentar decodificar se for uma string
                if (is_string($properties['location'])) {
                    $decoded = json_decode($properties['location'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->info('Location (decoded): '.json_encode($decoded, JSON_PRETTY_PRINT));
                    }
                }

                // Verificar se é um array ou objeto
                if (is_array($properties['location']) || is_object($properties['location'])) {
                    $this->info('Location é um '.(is_array($properties['location']) ? 'array' : 'objeto'));

                    // Verificar campos específicos
                    $location = (array) $properties['location'];
                    $this->info('countryName: '.($location['countryName'] ?? 'não encontrado'));
                    $this->info('cityName: '.($location['cityName'] ?? 'não encontrado'));
                }
            } else {
                $this->warn('Location não encontrada nas propriedades');
            }

            // Exibir o SQL bruto para essa linha
            $this->info("\nSQL para esta atividade:");
            $sql = "SELECT JSON_EXTRACT(properties, '$.location') as location_json FROM activity_log WHERE id = {$atividade->id}";
            $result = DB::select($sql);
            $this->info('Resultado SQL: '.json_encode($result));
        }

        return 0;
    }
}
