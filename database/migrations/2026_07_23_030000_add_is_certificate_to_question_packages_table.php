<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE question_packages ADD COLUMN is_certificate TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE question_packages DROP COLUMN is_certificate');
    }
};
