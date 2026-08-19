<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('site')->nullable()->after('operator_assessment_category_id');
        });

        $now = now();
        foreach (['New Hire', 'Pre Test', 'Post Test', 'Remidial'] as $name) {
            DB::table('operator_assessment_categories')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => 'Kategori invite '.$name,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropColumn('site');
        });
    }
};
