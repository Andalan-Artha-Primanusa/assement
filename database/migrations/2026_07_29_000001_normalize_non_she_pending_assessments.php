<?php

use App\Models\Assessment;
use App\Models\QuestionPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $shePackageIds = DB::table('question_packages')
            ->where('type', QuestionPackage::TYPE_SHE)
            ->pluck('id')
            ->all();

        DB::table('assessments')
            ->where('status', Assessment::STATUS_PENDING_REVIEW)
            ->where(function ($query) use ($shePackageIds): void {
                $query->whereNull('question_package_id');

                if ($shePackageIds === []) {
                    $query->orWhereNotNull('question_package_id');
                } else {
                    $query->orWhereNotIn('question_package_id', $shePackageIds);
                }
            })
            ->update([
                'status' => Assessment::STATUS_GRADED,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
