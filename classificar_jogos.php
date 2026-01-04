<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// IDs das categorias
$categoriaAoVivoID = 3;
$categoriaTodosID = 1;

// Buscar provedores com nomes "spin", "evolution" e "pragmaticplaylive" (case insensitive)
$provedores = \App\Models\Provider::where('name', 'like', '%spin%')
    ->orWhere('name', 'like', '%evolution%')
    ->orWhere('name', 'like', '%pragmatic%live%')
    ->orWhere('name', 'like', '%pragmaticlive%')
    ->get();

echo "Provedores encontrados:\n";
foreach ($provedores as $provedor) {
    echo "ID: {$provedor->id}, Nome: {$provedor->name}\n";
}

echo "\nIniciando classificação dos jogos...\n";

$totalJogos = 0;
$jogosClassificados = 0;
$jogosHomeDesativados = 0;

foreach ($provedores as $provedor) {
    // Buscar jogos do provedor
    $jogos = \App\Models\Game::where('provider_id', $provedor->id)->get();

    $totalJogosProvedor = count($jogos);
    $totalJogos += $totalJogosProvedor;

    echo "\nProvedor: {$provedor->name} (ID: {$provedor->id})\n";
    echo "Total de jogos encontrados: {$totalJogosProvedor}\n";

    foreach ($jogos as $jogo) {
        $classificado = false;

        // Verificar se o jogo já tem a categoria "Ao vivo"
        if (! $jogo->categories()->where('categories.id', $categoriaAoVivoID)->exists()) {
            // Adicionar categoria "Ao vivo" ao jogo
            $jogo->categories()->attach($categoriaAoVivoID);
            $classificado = true;
            echo "Jogo '{$jogo->game_name}' (ID: {$jogo->id}) classificado como 'Ao vivo'\n";
        } else {
            echo "Jogo '{$jogo->game_name}' (ID: {$jogo->id}) já está classificado como 'Ao vivo'\n";
        }

        // Verificar se o jogo já tem a categoria "Todos"
        if (! $jogo->categories()->where('categories.id', $categoriaTodosID)->exists()) {
            // Adicionar categoria "Todos" ao jogo
            $jogo->categories()->attach($categoriaTodosID);
            $classificado = true;
            echo "Jogo '{$jogo->game_name}' (ID: {$jogo->id}) classificado como 'Todos'\n";
        } else {
            echo "Jogo '{$jogo->game_name}' (ID: {$jogo->id}) já está classificado como 'Todos'\n";
        }

        if ($classificado) {
            $jogosClassificados++;
        }

        // Desativar a exibição na home
        if ($jogo->show_home == 1) {
            $jogo->show_home = 0;
            $jogo->save();
            $jogosHomeDesativados++;
            echo "Jogo '{$jogo->game_name}' (ID: {$jogo->id}) - Exibição na home desativada\n";
        }
    }
}

echo "\nResumo:\n";
echo "Total de jogos processados: {$totalJogos}\n";
echo "Jogos classificados (com novas categorias): {$jogosClassificados}\n";
echo "Jogos com exibição na home desativada: {$jogosHomeDesativados}\n";
echo "Processo concluído!\n";
