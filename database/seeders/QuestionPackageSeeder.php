<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionPackageSeeder extends Seeder
{
    public const MECHANIC_M1 = 'Screening Mechanic M1';
    public const MECHANIC_M2 = 'Screening Mechanic M2';
    public const MECHANIC_M3 = 'Screening Mechanic M3';
    public const OPERATOR = 'Screening Operator';

    public function run(): void
    {
        $adminMekanik = User::where('email', 'admin.mekanik@andalan.co.id')->first();
        $adminOperator = User::where('email', 'admin.operator@andalan.co.id')->first();

        $mekanikPackages = [
            self::MECHANIC_M1 => [
                'description' => 'Paket screening mekanik level M1 (Dasar).',
                'level' => 'M1',
                'min_score_pertimbangan' => 60,
                'min_score_lolos' => 65,
                'created_by' => $adminMekanik?->id,
            ],
            self::MECHANIC_M2 => [
                'description' => 'Paket screening mekanik level M2 (Menengah).',
                'level' => 'M2',
                'min_score_pertimbangan' => 55,
                'min_score_lolos' => 60,
                'created_by' => $adminMekanik?->id,
            ],
            self::MECHANIC_M3 => [
                'description' => 'Paket screening mekanik level M3 (Lanjutan).',
                'level' => 'M3',
                'min_score_pertimbangan' => 50,
                'min_score_lolos' => 55,
                'created_by' => $adminMekanik?->id,
            ],
        ];

        foreach ($mekanikPackages as $name => $data) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                array_merge($data, [
                    'type' => 'mekanik',
                    'is_active' => true,
                ])
            );
        }

        $operatorPackages = [
            self::OPERATOR => [
                'description' => 'Paket screening operator.',
                'level' => null,
                'min_score_pertimbangan' => 65,
                'min_score_lolos' => 70,
                'created_by' => $adminOperator?->id,
            ],
        ];

        foreach ($operatorPackages as $name => $data) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                array_merge($data, [
                    'type' => 'operator',
                    'is_active' => true,
                ])
            );
        }
    }
}
