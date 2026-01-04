<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all settings to use EUR currency
        Setting::query()->update([
            'currency_code' => 'EUR',
            'prefix' => '€',
            'currency_position' => 'left',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to BRL if needed
        Setting::query()->update([
            'currency_code' => 'BRL',
            'prefix' => 'R$',
            'currency_position' => 'left',
        ]);
    }
};
