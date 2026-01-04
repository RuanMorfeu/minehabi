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
        Schema::table('influencer_bonuses', function (Blueprint $table) {
            $table->boolean('browser_persistent')->default(false)->after('one_time_use')
                ->comment('Se verdadeiro, o status de resgate será armazenado no navegador, independente do usuário');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('influencer_bonuses', function (Blueprint $table) {
            $table->dropColumn('browser_persistent');
        });
    }
};
