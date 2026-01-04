w<?php

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
            $table->string('title');
            $table->text('message');
            $table->string('image')->nullable();
            $table->string('button_text')->default('Entendi'); // Default from resource
            $table->boolean('show_after_login')->default(true);
            $table->boolean('show_after_register')->default(false);
            $table->boolean('show_only_once')->default(false);
            $table->boolean('active')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_popups', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'message',
                'image',
                'button_text',
                'show_after_login',
                'show_after_register',
                'show_only_once',
                'active',
                'start_date',
                'end_date',
            ]);
        });
    }
};
