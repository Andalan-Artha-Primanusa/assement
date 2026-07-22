<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('multiple_choice');
            $table->string('category')->default('Mechanic');
            $table->string('difficulty')->default('basic');
            $table->text('text');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->char('correct_option', 1)->nullable()->default('a');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('INSERT INTO questions_new (id, question_package_id, category, difficulty, text, option_a, option_b, option_c, option_d, correct_option, is_active, created_at, updated_at) SELECT id, question_package_id, category, difficulty, text, option_a, option_b, option_c, option_d, correct_option, is_active, created_at, updated_at FROM questions');

        Schema::drop('questions');
        Schema::rename('questions_new', 'questions');
    }

    public function down(): void
    {
        Schema::create('questions_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('Mechanic');
            $table->string('difficulty')->default('basic');
            $table->text('text');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->char('correct_option', 1)->default('a');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('INSERT INTO questions_old (id, question_package_id, category, difficulty, text, option_a, option_b, option_c, option_d, correct_option, is_active, created_at, updated_at) SELECT id, question_package_id, category, difficulty, text, option_a, option_b, option_c, option_d, correct_option, is_active, created_at, updated_at FROM questions');

        Schema::drop('questions');
        Schema::rename('questions_old', 'questions');
    }
};
