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
        Schema::dropIfExists('popup_freespin_redemptions'); // Adicionado para garantir recriação limpa
        Schema::create('popup_freespin_redemptions', function (Blueprint $table) {
            $table->id();
            // Como a tabela users usa MyISAM, não podemos usar chave estrangeira
            $table->unsignedBigInteger('user_id'); // Sem constraint, apenas o mesmo tipo
            // A tabela auth_popups usa InnoDB, podemos manter a chave estrangeira
            $table->foreignId('popup_id')->constrained('auth_popups')->onDelete('cascade');
            $table->string('game_code');
            $table->integer('rounds');
            $table->boolean('success')->default(true);
            $table->string('response_message')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();

            // Garantir que um usuário só possa resgatar um popup uma vez
            $table->unique(['user_id', 'popup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popup_freespin_redemptions');
    }
};
