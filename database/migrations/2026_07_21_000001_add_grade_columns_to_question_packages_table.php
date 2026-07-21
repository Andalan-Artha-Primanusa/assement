<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_packages', function (Blueprint $table) {
            $table->decimal('min_score_pertimbangan', 5, 2)->nullable()->after('is_active');
            $table->decimal('min_score_lolos', 5, 2)->nullable()->after('min_score_pertimbangan');
        });
    }

    public function down(): void
    {
        Schema::table('question_packages', function (Blueprint $table) {
            $table->dropColumn(['min_score_pertimbangan', 'min_score_lolos']);
        });
    }
};
