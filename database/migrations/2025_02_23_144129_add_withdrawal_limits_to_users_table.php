<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('daily_withdrawal_limit', 10, 2)->nullable();
            $table->integer('daily_withdrawal_count_limit')->nullable();
            $table->integer('withdrawal_count_today')->default(0);
            $table->decimal('withdrawal_amount_today', 10, 2)->default(0);
            $table->timestamp('withdrawal_count_reset_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_withdrawal_limit',
                'daily_withdrawal_count_limit',
                'withdrawal_count_today',
                'withdrawal_amount_today',
                'withdrawal_count_reset_at',
            ]);
        });
    }
};
