<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id(); // internal PK — never exposed in UI (FR-0007)
            $table->string('student_number')->unique(); // school ID shown in UI, e.g. "2023-001"
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->enum('created_source', ['SSC_BULK', 'MANUAL']);
            $table->timestamps();

            $table->index('student_number'); // NFR-001: sub-second search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
