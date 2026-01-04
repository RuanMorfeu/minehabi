<?php

use App\Models\AffiliateWithdraw;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualiza todos os registros existentes para EUR
        AffiliateWithdraw::query()->update([
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
        AffiliateWithdraw::query()->update([
            'currency' => 'BRL',
            'symbol' => 'R$',
        ]);
    }
};
