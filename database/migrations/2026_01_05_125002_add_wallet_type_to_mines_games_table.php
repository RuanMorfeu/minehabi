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
        Schema::table('mines_games', function (Blueprint $table) {
            $table->string('wallet_type')->default('balance')->after('bet_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mines_games', function (Blueprint $table) {
            $table->dropColumn('wallet_type');
        });
    }
};
