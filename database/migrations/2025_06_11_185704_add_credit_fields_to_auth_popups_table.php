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
            $table->boolean('initial_credit_active')->default(false)->after('rounds_free_popup');
            $table->decimal('initial_credit_amount', 10, 2)->default(0)->after('initial_credit_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            $table->dropColumn(['initial_credit_active', 'initial_credit_amount']);
        });
    }
};
