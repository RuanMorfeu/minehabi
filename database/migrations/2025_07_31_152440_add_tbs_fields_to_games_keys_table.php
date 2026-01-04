<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games_keys', function (Blueprint $table) {
            // Campos TBS API
            $table->string('tbs_hall')->nullable()->comment('TBS Hall ID');
            $table->string('tbs_key')->nullable()->comment('TBS API Key');
            $table->string('tbs_endpoint')->nullable()->comment('TBS API Endpoint');
            $table->string('tbs_domain')->nullable()->comment('TBS Game Domain');
            $table->string('tbs_exit_url')->nullable()->comment('URL de saída dos jogos');
            $table->boolean('tbs_demo_mode')->default(false)->comment('Modo demo ativado');
            $table->boolean('tbs_jackpots_enabled')->default(false)->comment('Jackpots habilitados');
            $table->string('tbs_default_language')->default('pt')->comment('Idioma padrão');
            $table->string('tbs_default_continent')->default('EU')->comment('Continente padrão');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games_keys', function (Blueprint $table) {
            // Remove campos TBS API
            $table->dropColumn([
                'tbs_hall',
                'tbs_key',
                'tbs_endpoint',
                'tbs_domain',
                'tbs_exit_url',
                'tbs_demo_mode',
                'tbs_jackpots_enabled',
                'tbs_default_language',
                'tbs_default_continent',
            ]);
        });
    }
};
