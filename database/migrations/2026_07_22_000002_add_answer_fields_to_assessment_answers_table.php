<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('selected_option');
            $table->string('file_path')->nullable()->after('answer_text');
            $table->decimal('score', 5, 2)->nullable()->after('file_path');
            $table->text('review_notes')->nullable()->after('score');
            $table->foreignId('reviewed_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text', 'file_path', 'score', 'review_notes', 'reviewed_by', 'reviewed_at']);
        });
    }
};
