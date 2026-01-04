<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Não precisamos modificar a estrutura da tabela, apenas inserir os novos tipos
        // na documentação do sistema para que possam ser usados no painel administrativo

        // Os tipos 'login' e 'register' serão usados no campo 'type' existente
        // na tabela banners, assim como já existe o tipo 'deposit_promo'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não há alterações estruturais para reverter
    }
};
