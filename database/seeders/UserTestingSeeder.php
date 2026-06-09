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
            QuestionPackageSeeder::MECHANIC,
            QuestionPackageSeeder::AUTO_ELECTRICIAN,
            QuestionPackageSeeder::TYREMAN,
        ])->get()->keyBy('name');

        $users = [
            [
                'name' => 'Peserta Demo',
                'email' => 'peserta@example.com',
                'package' => QuestionPackageSeeder::MECHANIC,
            ],
            [
                'name' => 'Testing Mechanic 01',
                'email' => 'mechanic01@example.com',
                'package' => QuestionPackageSeeder::MECHANIC,
            ],
            [
                'name' => 'Testing Auto Electrician 01',
                'email' => 'autoelectrician01@example.com',
                'package' => QuestionPackageSeeder::AUTO_ELECTRICIAN,
            ],
            [
                'name' => 'Testing Tyreman 01',
                'email' => 'tyreman01@example.com',
                'package' => QuestionPackageSeeder::TYREMAN,
            ],
            [
                'name' => 'Testing Auto Electrician 02',
                'email' => 'autoelectrician02@example.com',
                'package' => QuestionPackageSeeder::AUTO_ELECTRICIAN,
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
                    'email_verified_at' => now(),
                    'question_package_id' => $package?->id,
                    'assessment_access_expires_at' => now()->addDays(30),
                    'assessment_duration_minutes' => 120,
                ]
            );
        }
    }
}
