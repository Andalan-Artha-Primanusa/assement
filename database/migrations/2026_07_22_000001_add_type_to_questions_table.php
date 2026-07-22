<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('questions', function (Blueprint $table) use ($driver) {
            $table->string('type')->default('multiple_choice')->after('question_package_id');

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE questions MODIFY COLUMN option_a TEXT NULL');
                DB::statement('ALTER TABLE questions MODIFY COLUMN option_b TEXT NULL');
                DB::statement('ALTER TABLE questions MODIFY COLUMN option_c TEXT NULL');
                DB::statement('ALTER TABLE questions MODIFY COLUMN option_d TEXT NULL');
                DB::statement('ALTER TABLE questions MODIFY COLUMN correct_option CHAR(1) NULL DEFAULT "a"');
            } else {
                $table->text('option_a')->nullable()->change();
                $table->text('option_b')->nullable()->change();
                $table->text('option_c')->nullable()->change();
                $table->text('option_d')->nullable()->change();
                $table->char('correct_option', 1)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
