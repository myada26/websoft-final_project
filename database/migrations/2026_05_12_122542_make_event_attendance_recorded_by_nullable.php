<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For SQLite, we need to recreate the table
        Schema::dropIfExists('event_attendance');
        
        Schema::create('event_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->enum('slot', ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT']);
            $table->boolean('is_present')->default(false);
            $table->foreignId('recorded_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['event_id', 'student_id', 'slot']);
            $table->index(['event_id', 'student_id']);
            $table->index(['event_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance');
    }
};