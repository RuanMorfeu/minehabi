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
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('sibs_terminalId')->nullable();
            $table->string('sibs_entidade')->nullable();
            $table->string('sibs_clientId')->nullable();
            $table->longText('sibs_bearerToken')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn('sibs_terminalId');
            $table->dropColumn('sibs_entidade');
            $table->dropColumn('sibs_clientId');
            $table->dropColumn('sibs_bearerToken');
        });
    }
};
