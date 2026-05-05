<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->string('control_number')->unique(); // e.g. "REM-2024-001"
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->decimal('total_amount', 10, 2); // DECIMAL(10,2) — NFR-009

            // Stage 1 — Create
            $table->foreignId('created_by_user_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Stage 2 — Verify
            $table->foreignId('verified_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Stage 3 — Accept
            $table->foreignId('accepted_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->timestamp('accepted_at')->nullable();

            $table->enum('status', ['PENDING', 'VERIFIED', 'ACCEPTED'])->default('PENDING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
