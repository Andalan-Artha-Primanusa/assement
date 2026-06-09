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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('assessment_access_expires_at')->nullable()->after('is_admin');
            $table->unsignedInteger('assessment_duration_minutes')->default(120)->after('assessment_access_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['assessment_access_expires_at', 'assessment_duration_minutes']);
        });
    }
};
