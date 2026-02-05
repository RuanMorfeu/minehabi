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
        Schema::create('aviator_bets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->decimal('amount', 20, 2);
            $table->string('type')->nullable(); // 'bet' ou 'auto'
            $table->unsignedBigInteger('gameid');
            $table->string('section_no')->nullable(); // extra info
            $table->decimal('cashout_multiplier', 8, 2)->nullable(); // Multiplicador no cashout
            $table->string('wallet_type')->nullable(); // Tipo de carteira usada (balance, bonus, etc)
            $table->boolean('status')->default(0); // 0 = pendente, 1 = ganho/finalizado?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviator_bets');
    }
};
