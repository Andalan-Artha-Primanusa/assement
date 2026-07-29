<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('question_packages', 'is_certificate')) {
            return;
        }

        Schema::table('question_packages', function (Blueprint $table): void {
            $table->boolean('is_certificate')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('question_packages', function (Blueprint $table): void {
            $table->dropColumn('is_certificate');
        });
    }
};
