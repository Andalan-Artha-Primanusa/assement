<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionPackageSeeder extends Seeder
{
    public const MECHANIC = 'Screening Mechanic';

    public const AUTO_ELECTRICIAN = 'Screening Auto Electrician';

    public const TYREMAN = 'Screening Tyreman';

    public const BASIC = 'Paket Mechanic Basic';

    public const POWER_TRAIN = 'Paket Power Train & Undercarriage';

    public const HYDRAULIC_ELECTRICAL = 'Paket Hydraulic & Electrical';

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $packages = [
            self::MECHANIC => 'Paket screening mechanic dari SOAL SCREENING MECHANIC.pdf.',
            self::AUTO_ELECTRICIAN => 'Paket screening auto electrician dari SOAL SCREENING AUTO ELECTRICIAN.pdf.',
            self::TYREMAN => 'Paket screening tyreman dari SOAL SCREENING TYREMAN.pdf.',
        ];

        foreach ($packages as $name => $description) {
            QuestionPackage::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'is_active' => true,
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
