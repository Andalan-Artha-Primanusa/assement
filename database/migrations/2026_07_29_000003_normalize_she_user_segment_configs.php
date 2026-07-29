<?php

use App\Models\QuestionPackage;
use App\Models\User;
use App\Support\AssessmentSegmentConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $shePackages = QuestionPackage::query()
            ->where('type', QuestionPackage::TYPE_SHE)
            ->get()
            ->keyBy('id');

        if ($shePackages->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('question_package_id', $shePackages->keys())
            ->chunkById(100, function ($users) use ($shePackages): void {
                foreach ($users as $user) {
                    $package = $shePackages->get($user->question_package_id);

                    if (! $package) {
                        continue;
                    }

                    $normalized = AssessmentSegmentConfig::forPackage($package, $user->segment_config);

                    if ($normalized !== ($user->segment_config ?? [])) {
                        $user->forceFill(['segment_config' => $normalized])->save();
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
