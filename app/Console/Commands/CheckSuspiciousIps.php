<?php

namespace App\Console\Commands;

use App\Services\IpTrackingService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class CheckSuspiciousIps extends Command
{
    /**
     * O nome e a assinatura do comando do console.
     *
     * @var string
     */
    protected $signature = 'ip:check-suspicious {--threshold=50 : Pontuação mínima de risco para considerar um IP como suspeito}';

    /**
     * A descrição do comando do console.
     *
     * @var string
     */
    protected $description = 'Verifica IPs suspeitos no sistema e alerta sobre possíveis riscos';

    /**
     * Execute o comando do console.
     */
    public function handle()
    {
        $threshold = $this->option('threshold');
        $this->info('Verificando IPs suspeitos (pontuação de risco > '.$threshold.')...');

        // Obter os IPs mais ativos
        $activeIps = IpTrackingService::getMostActiveIps(20);

        $this->info('Analisando '.count($activeIps).' IPs mais ativos...');
        $this->newLine();

        $suspiciousCount = 0;
        $table = [];

        foreach ($activeIps as $ipData) {
            $ip = $ipData->ip;
            $result = IpTrackingService::checkSuspiciousIp($ip);

            if ($result['risk_score'] >= $threshold) {
                $suspiciousCount++;

                // Obter usuários associados a este IP
                $users = Activity::query()
                    ->whereJsonContains('properties->ip', $ip)
                    ->with('causer')
                    ->get()
                    ->pluck('causer.name', 'causer.id')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $table[] = [
                    'IP' => $ip,
                    'Pontuação de Risco' => $result['risk_score'],
                    'Nível de Risco' => $result['risk_level'],
                    'Motivos' => implode('; ', $result['reasons']),
                    'Usuários' => $users,
                ];
            }
        }

        if ($suspiciousCount > 0) {
            $this->table(
                ['IP', 'Pontuação de Risco', 'Nível de Risco', 'Motivos', 'Usuários'],
                $table
            );

            $this->warn("Encontrados {$suspiciousCount} IPs suspeitos!");
        } else {
            $this->info('Nenhum IP suspeito encontrado com a pontuação de risco acima de '.$threshold);
        }

        return 0;
    }
}
