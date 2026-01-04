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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('game_code_rounds_free_any_deposit')->nullable();
            $table->string('rounds_free_any_deposit')->nullable();
            $table->decimal('amount_rounds_free_any_deposit', 10, 2)->default(0);
            $table->boolean('game_free_rounds_active_any_deposit')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'game_code_rounds_free_any_deposit',
                'rounds_free_any_deposit',
                'amount_rounds_free_any_deposit',
                'game_free_rounds_active_any_deposit',
            ]);
        });
    }
};
