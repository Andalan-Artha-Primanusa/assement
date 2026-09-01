<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', '<>', User::ROLE_USER)
            ->where(function ($query): void {
                $query->whereNull('site')
                    ->orWhere('site', '');
            })
            ->update([
                'site' => 'HO',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', '<>', User::ROLE_USER)
            ->where('site', 'HO')
            ->update([
                'site' => null,
                'updated_at' => now(),
            ]);
    }
};
