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
        Schema::table('auth_popups', function (Blueprint $table) {
            // Adiciona campos de freespin para popups
            $table->boolean('game_free_rounds_active_popup')->default(false);
            $table->string('game_code_rounds_free_popup')->nullable();
            $table->integer('rounds_free_popup')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            $table->dropColumn([
                'game_free_rounds_active_popup',
                'game_code_rounds_free_popup',
                'rounds_free_popup',
            ]);
        });
    }
};
