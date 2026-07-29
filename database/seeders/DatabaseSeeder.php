<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            QuestionPackageSeeder::class,
            ShePackageSeeder::class,
            HrPackageSeeder::class,
            UserTestingSeeder::class,
            QuestionTestingSeeder::class,
            SheQuestionSeeder::class,
            HrQuestionSeeder::class,
        ]);
    }
}
