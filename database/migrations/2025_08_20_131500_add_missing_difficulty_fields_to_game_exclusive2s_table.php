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
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            // Adiciona campo difficulty para Pacman (que estava faltando)
            $table->integer('difficulty')->default(1)->after('ghost_points');

            // Adiciona campo influencer_difficulty para Pacman (que estava faltando)
            $table->integer('influencer_difficulty')->nullable()->after('influencer_ghost_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'influencer_difficulty']);
        });
    }
};
