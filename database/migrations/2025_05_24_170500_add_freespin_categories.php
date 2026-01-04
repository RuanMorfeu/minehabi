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
        Schema::table('settings', function (Blueprint $table) {
            // Categorias de Freespin para Primeiro Depósito
            $table->decimal('amount_rounds_free_deposit_cat1_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_deposit_cat1_max', 10, 2)->nullable();
            $table->integer('rounds_free_deposit_cat1')->nullable();

            $table->decimal('amount_rounds_free_deposit_cat2_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_deposit_cat2_max', 10, 2)->nullable();
            $table->integer('rounds_free_deposit_cat2')->nullable();

            $table->decimal('amount_rounds_free_deposit_cat3_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_deposit_cat3_max', 10, 2)->nullable();
            $table->integer('rounds_free_deposit_cat3')->nullable();

            $table->decimal('amount_rounds_free_deposit_cat4_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_deposit_cat4_max', 10, 2)->nullable();
            $table->integer('rounds_free_deposit_cat4')->nullable();

            // Categorias de Freespin para Depósitos Subsequentes
            $table->decimal('amount_rounds_free_any_deposit_cat1_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_any_deposit_cat1_max', 10, 2)->nullable();
            $table->integer('rounds_free_any_deposit_cat1')->nullable();

            $table->decimal('amount_rounds_free_any_deposit_cat2_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_any_deposit_cat2_max', 10, 2)->nullable();
            $table->integer('rounds_free_any_deposit_cat2')->nullable();

            $table->decimal('amount_rounds_free_any_deposit_cat3_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_any_deposit_cat3_max', 10, 2)->nullable();
            $table->integer('rounds_free_any_deposit_cat3')->nullable();

            $table->decimal('amount_rounds_free_any_deposit_cat4_min', 10, 2)->nullable();
            $table->decimal('amount_rounds_free_any_deposit_cat4_max', 10, 2)->nullable();
            $table->integer('rounds_free_any_deposit_cat4')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Remover colunas de categorias de Freespin para Primeiro Depósito
            $table->dropColumn([
                'amount_rounds_free_deposit_cat1_min',
                'amount_rounds_free_deposit_cat1_max',
                'rounds_free_deposit_cat1',
                'amount_rounds_free_deposit_cat2_min',
                'amount_rounds_free_deposit_cat2_max',
                'rounds_free_deposit_cat2',
                'amount_rounds_free_deposit_cat3_min',
                'amount_rounds_free_deposit_cat3_max',
                'rounds_free_deposit_cat3',
                'amount_rounds_free_deposit_cat4_min',
                'amount_rounds_free_deposit_cat4_max',
                'rounds_free_deposit_cat4',
            ]);

            // Remover colunas de categorias de Freespin para Depósitos Subsequentes
            $table->dropColumn([
                'amount_rounds_free_any_deposit_cat1_min',
                'amount_rounds_free_any_deposit_cat1_max',
                'rounds_free_any_deposit_cat1',
                'amount_rounds_free_any_deposit_cat2_min',
                'amount_rounds_free_any_deposit_cat2_max',
                'rounds_free_any_deposit_cat2',
                'amount_rounds_free_any_deposit_cat3_min',
                'amount_rounds_free_any_deposit_cat3_max',
                'rounds_free_any_deposit_cat3',
                'amount_rounds_free_any_deposit_cat4_min',
                'amount_rounds_free_any_deposit_cat4_max',
                'rounds_free_any_deposit_cat4',
            ]);
        });
    }
};
