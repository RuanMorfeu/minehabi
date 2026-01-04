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
            $table->decimal('player_speed', 8, 2)->default(5.00)->after('ghost_points');
            $table->decimal('missile_speed', 8, 2)->default(3.00)->after('player_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            $table->dropColumn(['player_speed', 'missile_speed']);
        });
    }
};
