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
            $table->integer('influencer_lives')->nullable()->after('lives');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_exclusive2s', function (Blueprint $table) {
            $table->dropColumn('influencer_lives');
        });
    }
};
