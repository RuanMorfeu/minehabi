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
            // Métricas de visualizações
            $table->unsignedInteger('total_views')->default(0)->after('browser_persistent');
            $table->unsignedInteger('unique_views')->default(0)->after('total_views');

            // Métricas de interações
            $table->unsignedInteger('total_clicks')->default(0)->after('unique_views');
            $table->unsignedInteger('total_redemptions')->default(0)->after('total_clicks');
            $table->unsignedInteger('successful_redemptions')->default(0)->after('total_redemptions');

            // Última vez que foi exibido
            $table->timestamp('last_shown_at')->nullable()->after('successful_redemptions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            //
        });
    }
};
