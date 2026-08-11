<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('operator_assessment_category_id')
                ->nullable()
                ->after('question_package_id')
                ->constrained('operator_assessment_categories')
                ->nullOnDelete();
        });

        Schema::table('question_packages', function (Blueprint $table) {
            if (Schema::hasColumn('question_packages', 'operator_assessment_category_id')) {
                $table->dropConstrainedForeignId('operator_assessment_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('question_packages', 'operator_assessment_category_id')) {
                $table->foreignId('operator_assessment_category_id')
                    ->nullable()
                    ->after('level')
                    ->constrained('operator_assessment_categories')
                    ->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_assessment_category_id');
        });
    }
};
