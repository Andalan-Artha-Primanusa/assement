<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShePackageSeeder extends Seeder
{
    public const SHE_BASIC = 'Screening SHE Basic';
    public const SHE_ADVANCED = 'Screening SHE Advanced';

    public function run(): void
    {
        $adminShe = User::where('email', 'admin.she@andalan.co.id')->first();

        $packages = [
            self::SHE_BASIC => [
                'description' => 'Paket screening SHE level Basic (Safety, Health, Environment).',
                'level' => 'Basic',
                'min_score_pertimbangan' => 60,
                'min_score_lolos' => 65,
            ],
            self::SHE_ADVANCED => [
                'description' => 'Paket screening SHE level Advanced (Safety, Health, Environment).',
                'level' => 'Advanced',
                'min_score_pertimbangan' => 65,
                'min_score_lolos' => 70,
            ],
        ];

        foreach ($packages as $name => $data) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                array_merge($data, [
                    'type' => 'she',
                    'is_active' => true,
                    'created_by' => $adminShe?->id,
                ])
            );
        }
    }
}
