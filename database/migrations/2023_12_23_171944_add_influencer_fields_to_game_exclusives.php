<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('game_exclusives', function (Blueprint $table) {
            $table->decimal('influencer_velocidade', 8, 2)->nullable()->after('velocidade');
            $table->decimal('influencer_xmeta', 8, 2)->nullable()->after('xmeta');
            $table->decimal('influencer_coin_value', 8, 2)->nullable()->after('coin_value');
        });
    }

    public function down()
    {
        Schema::table('game_exclusives', function (Blueprint $table) {
            $table->dropColumn('influencer_velocidade');
            $table->dropColumn('influencer_xmeta');
            $table->dropColumn('influencer_coin_value');
        });
    }
};
