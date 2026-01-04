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
        Schema::table('user_documents', function (Blueprint $table) {
            $table->integer('submission_attempts')->default(0)->after('verified_at');
            $table->timestamp('last_submission_at')->nullable()->after('submission_attempts');
            $table->boolean('can_resubmit')->default(true)->after('last_submission_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropColumn(['submission_attempts', 'last_submission_at', 'can_resubmit']);
        });
    }
};
