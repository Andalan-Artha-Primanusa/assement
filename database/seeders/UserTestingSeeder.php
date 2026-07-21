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
            QuestionPackageSeeder::MECHANIC_M1,
            QuestionPackageSeeder::MECHANIC_M2,
            QuestionPackageSeeder::MECHANIC_M3,
            QuestionPackageSeeder::OPERATOR,
        ])->get()->keyBy('name');

        $users = [
            [
                'name' => 'Peserta Demo M1',
                'email' => 'peserta.m1@example.com',
                'package' => QuestionPackageSeeder::MECHANIC_M1,
            ],
            [
                'name' => 'Peserta Demo M2',
                'email' => 'peserta.m2@example.com',
                'package' => QuestionPackageSeeder::MECHANIC_M2,
            ],
            [
                'name' => 'Peserta Demo M3',
                'email' => 'peserta.m3@example.com',
                'package' => QuestionPackageSeeder::MECHANIC_M3,
            ],
            [
                'name' => 'Testing Mekanik 01',
                'email' => 'mekanik01@example.com',
                'package' => QuestionPackageSeeder::MECHANIC_M1,
            ],
            [
                'name' => 'Testing Mekanik 02',
                'email' => 'mekanik02@example.com',
                'package' => QuestionPackageSeeder::MECHANIC_M2,
            ],
            [
                'name' => 'Testing Operator 01',
                'email' => 'operator01@example.com',
                'package' => QuestionPackageSeeder::OPERATOR,
            ],
            [
                'name' => 'Testing Operator 02',
                'email' => 'operator02@example.com',
                'package' => QuestionPackageSeeder::OPERATOR,
            ],
        ];

        foreach ($users as $user) {
            $package = $packages[$user['package']] ?? null;

            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => 'user',
                    'email_verified_at' => now(),
                    'question_package_id' => $package?->id,
                    'assessment_access_expires_at' => now()->addDays(30),
                    'assessment_duration_minutes' => 120,
                ]
            );
        }
    }
}
