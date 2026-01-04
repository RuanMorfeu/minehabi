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
            $table->string('influencer_code')->nullable()->after('target_user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            $table->dropColumn('influencer_code');
        });
    }
};
