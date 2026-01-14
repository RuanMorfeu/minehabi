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
        Schema::create('chicken_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 10, 2);
            $table->string('difficulty'); // easy, medium, hard, hardcore
            $table->integer('trap_position'); // Posição onde a galinha vai "morrer"
            $table->integer('current_step')->default(0); // Passo atual
            $table->string('status'); // playing, won, lost, cashed_out
            $table->decimal('multiplier', 10, 2)->default(1.00);
            $table->decimal('potential_win', 10, 2)->nullable();
            $table->decimal('win_amount', 10, 2)->nullable();
            $table->string('wallet_type')->default('balance');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chicken_games');
    }
};
