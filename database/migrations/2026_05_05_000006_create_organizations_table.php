<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['SSC', 'COLLEGE_COUNCIL', 'DEPT_SOCIETY']);
            $table->foreignId('linked_college_id')
                  ->nullable()
                  ->constrained('colleges')
                  ->restrictOnDelete();
            $table->foreignId('linked_department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // App-level rule: COLLEGE_COUNCIL → linked_college_id NOT NULL, linked_department_id NULL
            //                 DEPT_SOCIETY  → linked_department_id NOT NULL
            //                 SSC           → both NULL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
