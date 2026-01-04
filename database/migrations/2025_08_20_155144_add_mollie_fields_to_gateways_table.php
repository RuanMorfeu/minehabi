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
            $table->string('mollie_api_key')->nullable()->after('stripe_secret_key');
            $table->string('mollie_profile_id')->nullable()->after('mollie_api_key');
            $table->boolean('mollie_active')->default(false)->after('mollie_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['mollie_api_key', 'mollie_profile_id', 'mollie_active']);
        });
    }
};
