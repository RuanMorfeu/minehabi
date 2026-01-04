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
        // Check if the table already exists
        if (! Schema::hasTable('user_deposits')) {
            Schema::create('user_deposits', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique()->comment('ID da transação no gateway');
                $table->string('deposit_method'); // e.g., MULTIBANCO, MBWAY
                $table->decimal('amount', 15, 2);
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('meta')->nullable()->comment('Dados brutos da resposta da API SIBS');
                $table->string('status')->default('pending')->comment('Status da transação: pending, completed, failed'); // Adicionando status
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the table exists before dropping
        if (Schema::hasTable('user_deposits')) {
            Schema::dropIfExists('user_deposits');
        }
    }
};
