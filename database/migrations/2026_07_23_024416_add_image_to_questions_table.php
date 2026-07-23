<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE questions ADD COLUMN image VARCHAR(500) NULL AFTER text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE questions DROP COLUMN image');
    }
};
