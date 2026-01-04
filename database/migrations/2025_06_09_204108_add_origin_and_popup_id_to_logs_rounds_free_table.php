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
        Schema::table('logs_rounds_free', function (Blueprint $table) {
            // Adicionar campo origin para identificar a origem do freespin (registro, popup, etc)
            $table->string('origin')->nullable()->after('message');

            // Adicionar campo popup_id para relacionar com o popup que concedeu o freespin
            $table->unsignedBigInteger('popup_id')->nullable()->after('origin');

            // Adicionar campo user_id para relacionar com o usuário que recebeu o freespin
            $table->unsignedBigInteger('user_id')->nullable()->after('popup_id');

            // Adicionar campo rounds para registrar quantas rodadas foram concedidas
            $table->integer('rounds')->nullable()->after('user_id');

            // Adicionar índices para melhorar a performance das consultas
            $table->index('origin');
            $table->index('popup_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs_rounds_free', function (Blueprint $table) {
            // Remover os índices
            $table->dropIndex(['origin']);
            $table->dropIndex(['popup_id']);
            $table->dropIndex(['user_id']);

            // Remover os campos
            $table->dropColumn('origin');
            $table->dropColumn('popup_id');
            $table->dropColumn('user_id');
            $table->dropColumn('rounds');
        });
    }
};
