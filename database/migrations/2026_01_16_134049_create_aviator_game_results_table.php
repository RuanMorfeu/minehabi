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
        Schema::create('aviator_game_results', function (Blueprint $table) {
            $table->id();
            $table->string('result')->nullable(); // O resultado final registrado
            $table->decimal('crash_point', 8, 2)->nullable(); // O ponto de crash predeterminado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_game_results');
    }
};
