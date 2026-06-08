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
            UserTestingSeeder::class,
            QuestionTestingSeeder::class,
        ]);
    }
}
