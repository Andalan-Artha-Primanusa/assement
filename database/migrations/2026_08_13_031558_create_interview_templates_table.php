<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('interview_templates');
        Schema::create('interview_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Form Mekanik, Form Operator
            $table->string('type')->index(); // 'mekanik', 'operator', 'hr', dll
            $table->integer('min_recommended_percentage')->default(70);
            $table->integer('min_considered_percentage')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_templates');
    }
};
