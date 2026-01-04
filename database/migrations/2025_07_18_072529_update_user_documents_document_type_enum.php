<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alterar o ENUM para incluir os tipos de documentos portugueses
        DB::statement("ALTER TABLE user_documents MODIFY COLUMN document_type ENUM('cc', 'passport', 'carta_conducao', 'rg', 'cnh', 'other') NOT NULL DEFAULT 'cc'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o ENUM original
        DB::statement("ALTER TABLE user_documents MODIFY COLUMN document_type ENUM('rg', 'cnh', 'passport', 'other') NOT NULL DEFAULT 'rg'");
    }
};
