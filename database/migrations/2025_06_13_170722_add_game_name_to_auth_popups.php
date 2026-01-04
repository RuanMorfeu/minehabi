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
            $table->string('game_name_rounds_free_popup')->nullable()->after('game_code_rounds_free_popup')->comment('Nome do jogo para exibição na notificação de rodadas grátis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            $table->dropColumn('game_name_rounds_free_popup');
        });
    }
};
