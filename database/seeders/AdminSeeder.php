<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@andalan.co.id'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.mekanik@andalan.co.id'],
            [
                'name' => 'Admin Mekanik',
                'password' => 'password',
                'role' => 'admin_mekanik',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.operator@andalan.co.id'],
            [
                'name' => 'Admin Operator',
                'password' => 'password',
                'role' => 'admin_operation',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.she@andalan.co.id'],
            [
                'name' => 'Admin SHE',
                'password' => 'password',
                'role' => 'admin_she',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.hr@andalan.co.id'],
            [
                'name' => 'Admin HR',
                'password' => 'password',
                'role' => 'admin_hr',
                'email_verified_at' => now(),
            ]
        );
    }
}
