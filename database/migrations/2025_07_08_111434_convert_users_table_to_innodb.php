<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users ENGINE = InnoDB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Embora a reversão possa não ser perfeita se chaves estrangeiras já existirem,
        // é uma boa prática definir o estado anterior.
        DB::statement('ALTER TABLE users ENGINE = MyISAM');
    }
};
