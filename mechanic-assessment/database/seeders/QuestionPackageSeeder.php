<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionPackageSeeder extends Seeder
{
    public const BASIC = 'Paket Mechanic Basic';

    public const POWER_TRAIN = 'Paket Power Train & Undercarriage';

    public const HYDRAULIC_ELECTRICAL = 'Paket Hydraulic & Electrical';

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        $packages = [
            self::BASIC => 'Safety, maintenance, tools, dan basic engine untuk screening awal mechanic.',
            self::POWER_TRAIN => 'Power train, steering, undercarriage, dan troubleshooting komponen mekanikal.',
            self::HYDRAULIC_ELECTRICAL => 'Hydraulic, electrical, dan soal advanced untuk kandidat yang lebih senior.',
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
