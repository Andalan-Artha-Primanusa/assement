<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShePackageSeeder extends Seeder
{
    public const SHE_DEPARTEMENT_HEAD = 'Screening SHE Departement Head';
    public const SHE_SECTION_HEAD = 'Screening SHE Section Head';
    public const SHE_LEAD_OF = 'Screening SHE Lead Of';

    public function run(): void
    {
        $adminShe = User::where('email', 'admin.she@andalan.co.id')->first();

        $packages = [
            self::SHE_DEPARTEMENT_HEAD => [
                'description' => 'Paket screening SHE untuk Departement Head.',
                'level' => 'Departement Head',
                'min_score_pertimbangan' => 65,
                'min_score_lolos' => 70,
            ],
            self::SHE_SECTION_HEAD => [
                'description' => 'Paket screening SHE untuk Section Head.',
                'level' => 'Section Head',
                'min_score_pertimbangan' => 60,
                'min_score_lolos' => 65,
            ],
            self::SHE_LEAD_OF => [
                'description' => 'Paket screening SHE untuk Lead Of.',
                'level' => 'Lead Of',
                'min_score_pertimbangan' => 55,
                'min_score_lolos' => 60,
            ],
        ];

        foreach ($packages as $name => $data) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                array_merge($data, [
                    'type' => 'she',
                    'is_active' => true,
                    'has_segments' => true,
                    'created_by' => $adminShe?->id,
                ])
            );
        }
    }
}
