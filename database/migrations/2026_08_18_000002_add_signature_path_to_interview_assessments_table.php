<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_assessments', function (Blueprint $table): void {
            $table->string('signature_path')->nullable()->after('user_interviewer_name');
        });
    }

    public function down(): void
    {
        Schema::table('interview_assessments', function (Blueprint $table): void {
            $table->dropColumn('signature_path');
        });
    }
};
