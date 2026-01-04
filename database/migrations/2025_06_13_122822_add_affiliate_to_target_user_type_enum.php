<?php

use Illuminate\Database\Migrations\Migration;
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
            // Atualizar o enum para incluir a opção 'affiliate'
            DB::statement("ALTER TABLE auth_popups MODIFY COLUMN target_user_type ENUM('all', 'new', 'existing', 'with_deposit', 'without_deposit', 'affiliate') DEFAULT 'all'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para os tipos anteriores
        if (Schema::hasColumn('auth_popups', 'target_user_type')) {
            DB::statement("ALTER TABLE auth_popups MODIFY COLUMN target_user_type ENUM('all', 'new', 'existing', 'with_deposit', 'without_deposit') DEFAULT 'all'");
        }
    }
};
