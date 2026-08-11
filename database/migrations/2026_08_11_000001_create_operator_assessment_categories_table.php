<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_assessment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('question_packages', function (Blueprint $table) {
            $table->foreignId('operator_assessment_category_id')
                ->nullable()
                ->after('level')
                ->constrained('operator_assessment_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operator_assessment_category_id');
        });

        Schema::dropIfExists('operator_assessment_categories');
    }
};
