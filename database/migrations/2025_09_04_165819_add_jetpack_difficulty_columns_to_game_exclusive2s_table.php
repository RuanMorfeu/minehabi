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
            $table->string('jetpack_difficulty')->default('medium')->after('missile_speed');
            $table->string('influencer_jetpack_difficulty')->nullable()->after('jetpack_difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            $table->dropColumn(['jetpack_difficulty', 'influencer_jetpack_difficulty']);
        });
    }
};
