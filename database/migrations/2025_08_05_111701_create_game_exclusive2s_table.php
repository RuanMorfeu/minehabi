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
        Schema::create('game_exclusive2s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('visible_in_home')->default(true);
            $table->bigInteger('views')->default(0);
            $table->integer('lives')->default(1);
            $table->decimal('coin_rate', 8, 2)->default(0.01);
            $table->decimal('meta_multiplier', 8, 2)->default(1.0);
            $table->decimal('ghost_points', 8, 2)->default(1.0);
            $table->decimal('min_amount', 8, 2)->default(1.0);
            $table->string('game_type'); // pacman, jetpack, angry
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_exclusive2s');
    }
};
