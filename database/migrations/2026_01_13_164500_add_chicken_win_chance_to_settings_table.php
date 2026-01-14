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
            $table->integer('chicken_win_chance')->nullable()->default(null)->after('mines_win_chance')->comment('Porcentagem Global de chance de vitória no Chicken (0-100). Null = Aleatório padrão.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('chicken_win_chance');
        });
    }
};
