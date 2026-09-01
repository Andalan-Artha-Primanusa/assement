<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed HO as default
        DB::table('sites')->insert([
            'code' => 'HO',
            'name' => 'Head Office (Pusat)',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Migrate existing unique site values from users table
        $existingSites = DB::table('users')
            ->whereNotNull('site')
            ->where('site', '<>', '')
            ->where('site', '<>', 'HO')
            ->distinct()
            ->pluck('site');

        foreach ($existingSites as $site) {
            DB::table('sites')->insertOrIgnore([
                'code' => strtoupper(trim($site)),
                'name' => trim($site),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Also migrate from assessments table
        $assessmentSites = DB::table('assessments')
            ->whereNotNull('site')
            ->where('site', '<>', '')
            ->where('site', '<>', 'HO')
            ->distinct()
            ->pluck('site');

        foreach ($assessmentSites as $site) {
            DB::table('sites')->insertOrIgnore([
                'code' => strtoupper(trim($site)),
                'name' => trim($site),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
