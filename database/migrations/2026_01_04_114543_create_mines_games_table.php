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
        Schema::create('mines_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet_amount', 10, 2);
            $table->integer('mines_count');
            $table->json('mine_positions');
            $table->json('revealed_positions')->nullable();
            $table->string('status'); // playing, won, lost
            $table->decimal('multiplier', 10, 2)->default(1.00);
            $table->decimal('potential_win', 10, 2)->nullable();
            $table->decimal('win_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mines_games');
    }
};
