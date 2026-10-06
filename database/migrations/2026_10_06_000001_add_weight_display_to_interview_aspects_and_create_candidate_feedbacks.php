<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // interview_aspects already has 'weight' column (default 1).
        // We just make sure existing records have weight = 1 (already the default).
        // We update old records that were saved as weight=100 to weight=1.
        DB::table('interview_aspects')->where('weight', 100)->update(['weight' => 1]);

        // Create candidate feedback table
        Schema::create('interview_candidate_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('candidate_name')->nullable();
            $table->text('feedback')->nullable();
            $table->json('scores')->nullable(); // { aspect_id: { score, notes } }
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('average_score', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_candidate_feedbacks');
    }
};
