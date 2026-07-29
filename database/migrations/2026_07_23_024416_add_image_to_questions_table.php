<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('questions', 'image')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table): void {
            $table->string('image', 500)->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('image');
        });
    }
};
