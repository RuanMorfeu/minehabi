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
            $table->string('game_code_rounds_free_deposit')->nullable();
            $table->string('rounds_free_deposit')->nullable();
            $table->decimal('amount_rounds_free_deposit')->default(0);
            $table->boolean('game_free_rounds_active_deposit')->default(false);

            $table->string('game_code_rounds_free_register')->nullable();
            $table->string('rounds_free_register')->nullable();
            $table->boolean('game_free_rounds_active_register')->default(false);
        });
        Schema::create('logs_rounds_free', function (Blueprint $table) {
            $table->id();
            $table->string('game_code');
            $table->string('username');
            $table->boolean('status')->default(false);
            $table->string('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['game_code_rounds_free_deposit', 'rounds_free_deposit', 'amount_rounds_free_deposit', 'game_free_rounds_active_deposit', 'game_code_rounds_free_register', 'rounds_free_register', 'amount_rounds_free_register', 'game_free_rounds_active_register']);
        });
        Schema::dropIfExists('logs_rounds_free');
    }
};
