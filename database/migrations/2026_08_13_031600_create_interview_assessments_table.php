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
        Schema::create('interview_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_template_id')->constrained();
            $table->string('candidate_name');
            $table->string('job_title')->nullable();
            $table->string('gender')->nullable();
            $table->string('department')->nullable();
            $table->integer('age')->nullable();
            $table->string('location')->nullable();
            $table->string('domicile')->nullable();
            $table->date('join_date')->nullable();
            $table->string('expected_salary')->nullable();
            $table->date('interview_date')->nullable();
            
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('average_score', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('recommendation')->nullable();
            
            $table->text('hr_conclusion')->nullable();
            $table->string('hr_interviewer_name')->nullable();
            $table->string('user_interviewer_name')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_assessments');
    }
};
