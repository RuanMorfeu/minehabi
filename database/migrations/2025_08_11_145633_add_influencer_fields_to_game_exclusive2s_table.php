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
            // Campos de influencer para Pacman
            $table->integer('influencer_lives')->nullable()->after('lives');
            $table->decimal('influencer_coin_rate', 8, 4)->nullable()->after('coin_rate');
            $table->decimal('influencer_meta_multiplier', 8, 2)->nullable()->after('meta_multiplier');
            $table->decimal('influencer_ghost_points', 8, 2)->nullable()->after('ghost_points');

            // Campos de influencer para Jetpack
            $table->integer('influencer_player_speed')->nullable()->after('player_speed');
            $table->integer('influencer_missile_speed')->nullable()->after('missile_speed');

            // Campos de influencer para Angry Birds
            $table->decimal('influencer_coin_multiplier', 8, 2)->nullable()->after('coin_multiplier');
            $table->integer('influencer_game_difficulty')->nullable()->after('game_difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            $table->dropColumn([
                'influencer_lives',
                'influencer_coin_rate',
                'influencer_meta_multiplier',
                'influencer_ghost_points',
                'influencer_player_speed',
                'influencer_missile_speed',
                'influencer_coin_multiplier',
                'influencer_game_difficulty',
            ]);
        });
    }
};
