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
        Schema::table('assessments', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('submitted_at');
            $table->string('block_reason')->nullable()->after('blocked_at');
            $table->timestamp('unlocked_at')->nullable()->after('block_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['blocked_at', 'block_reason', 'unlocked_at']);
        });
    }
};
