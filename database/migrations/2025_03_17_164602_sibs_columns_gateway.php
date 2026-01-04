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
            // Check if columns exist before adding
            if (! Schema::hasColumn('gateways', 'sibs_terminalId')) {
                $table->string('sibs_terminalId')->nullable();
            }
            if (! Schema::hasColumn('gateways', 'sibs_entidade')) {
                $table->string('sibs_entidade')->nullable();
            }
            if (! Schema::hasColumn('gateways', 'sibs_clientId')) {
                $table->string('sibs_clientId')->nullable();
            }
            if (! Schema::hasColumn('gateways', 'sibs_bearerToken')) {
                $table->longText('sibs_bearerToken')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The down method should also check if columns exist before dropping
        // Although less critical, it's good practice
        Schema::table('gateways', function (Blueprint $table) {
            if (Schema::hasColumn('gateways', 'sibs_terminalId')) {
                $table->dropColumn('sibs_terminalId');
            }
            if (Schema::hasColumn('gateways', 'sibs_entidade')) {
                $table->dropColumn('sibs_entidade');
            }
            if (Schema::hasColumn('gateways', 'sibs_clientId')) {
                $table->dropColumn('sibs_clientId');
            }
            if (Schema::hasColumn('gateways', 'sibs_bearerToken')) {
                $table->dropColumn('sibs_bearerToken');
            }
        });
    }
};
