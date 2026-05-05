<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->boolean('is_regular')->default(true); // FALSE = irregular student
            $table->timestamps();

            // One enrollment per student per semester (program shifts create new rows)
            $table->unique(['student_id', 'academic_year_id']);

            $table->index(['student_id', 'academic_year_id']); // NFR-001 search performance
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
