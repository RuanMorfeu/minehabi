<?php

use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking if is_slot exists in games table...\n";
if (Schema::hasColumn('games', 'is_slot')) {
    echo "Column 'is_slot' EXISTS.\n";
} else {
    echo "Column 'is_slot' DOES NOT EXIST.\n";
}
