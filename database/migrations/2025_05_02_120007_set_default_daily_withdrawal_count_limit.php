<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Define o limite padrão de 1 saque diário para todos os usuários
     */
    public function up(): void
    {
        // Atualiza todos os usuários existentes para terem um limite de 1 saque diário
        DB::table('users')->whereNull('daily_withdrawal_count_limit')->update([
            'daily_withdrawal_count_limit' => 1,
        ]);

        // Atualiza o valor padrão da coluna para novos usuários
        Schema::table('users', function (Blueprint $table) {
            $table->integer('daily_withdrawal_count_limit')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     * Reverte o limite padrão de saques diários para NULL (sem limite)
     */
    public function down(): void
    {
        // Remove o valor padrão, voltando para NULL (sem limite)
        Schema::table('users', function (Blueprint $table) {
            $table->integer('daily_withdrawal_count_limit')->nullable()->default(null)->change();
        });
    }
};
