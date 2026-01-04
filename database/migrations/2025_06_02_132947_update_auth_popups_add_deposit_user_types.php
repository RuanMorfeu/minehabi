<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar se a coluna target_user_type existe
        if (Schema::hasColumn('auth_popups', 'target_user_type')) {
            // Atualizar o enum para incluir os novos tipos
            DB::statement("ALTER TABLE auth_popups MODIFY COLUMN target_user_type ENUM('all', 'new', 'existing', 'with_deposit', 'without_deposit') DEFAULT 'all'");
        } else {
            // Se a coluna não existir, criá-la
            Schema::table('auth_popups', function (Blueprint $table) {
                $table->enum('target_user_type', ['all', 'new', 'existing', 'with_deposit', 'without_deposit'])->default('all');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para os tipos originais
        if (Schema::hasColumn('auth_popups', 'target_user_type')) {
            DB::statement("ALTER TABLE auth_popups MODIFY COLUMN target_user_type ENUM('all', 'new', 'existing') DEFAULT 'all'");
        }
    }
};
