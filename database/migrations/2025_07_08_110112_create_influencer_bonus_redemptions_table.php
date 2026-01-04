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
        // Adicionado para garantir que a tabela seja recriada se já existir
        Schema::dropIfExists('influencer_bonus_redemptions');

        Schema::create('influencer_bonus_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('influencer_bonus_id');
            $table->decimal('deposit_amount', 10, 2);
            $table->decimal('bonus_amount', 10, 2);
            $table->timestamps();

            // Garante que cada usuário só pode resgatar um bônus específico uma vez
            $table->unique(['user_id', 'influencer_bonus_id']);

            // Adiciona as chaves estrangeiras sem usar o método constrained()
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('influencer_bonus_id')->references('id')->on('influencer_bonuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('influencer_bonus_redemptions');
    }
};
