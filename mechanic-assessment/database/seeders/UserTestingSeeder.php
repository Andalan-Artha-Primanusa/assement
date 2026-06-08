<?php

namespace Database\Seeders;

use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = QuestionPackage::whereIn('name', [
            QuestionPackageSeeder::BASIC,
            QuestionPackageSeeder::POWER_TRAIN,
            QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
        ])->get()->keyBy('name');

        $users = [
            [
                'name' => 'Peserta Demo',
                'email' => 'peserta@example.com',
                'package' => QuestionPackageSeeder::BASIC,
            ],
            [
                'name' => 'Testing Mechanic 01',
                'email' => 'mechanic01@example.com',
                'package' => QuestionPackageSeeder::BASIC,
            ],
            [
                'name' => 'Testing Mechanic 02',
                'email' => 'mechanic02@example.com',
                'package' => QuestionPackageSeeder::POWER_TRAIN,
            ],
            [
                'name' => 'Testing Mechanic 03',
                'email' => 'mechanic03@example.com',
                'package' => QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
            ],
            [
                'name' => 'Testing Mechanic 04',
                'email' => 'mechanic04@example.com',
                'package' => QuestionPackageSeeder::HYDRAULIC_ELECTRICAL,
            ],
        ];

        foreach ($users as $user) {
            $package = $packages[$user['package']] ?? null;

            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'is_admin' => false,
                    'question_package_id' => $package?->id,
                    'assessment_access_expires_at' => now()->addDays(30),
                    'assessment_duration_minutes' => 120,
                ]
            );
        }
    }
}
