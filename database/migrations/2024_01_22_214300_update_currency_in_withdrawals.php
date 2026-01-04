<?php

use App\Models\Withdrawal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualiza todos os registros existentes para EUR
        Withdrawal::query()->update([
            'currency' => 'EUR',
            'symbol' => '€',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte para BRL se necessário
        Withdrawal::query()->update([
            'currency' => 'BRL',
            'symbol' => 'R$',
        ]);
    }
};
