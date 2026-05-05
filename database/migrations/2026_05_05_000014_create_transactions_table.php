<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('or_number');                               // e.g. "ENG-001"
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('processed_by_user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->decimal('amount_paid', 10, 2);                     // DECIMAL(10,2) — NFR-009
            $table->enum('payment_method', ['CASH', 'GCASH']);
            $table->string('reference_number')->nullable();            // required if GCASH (FR-0016)
            $table->foreignId('fee_profile_id')
                  ->nullable()
                  ->constrained('fee_profiles')
                  ->restrictOnDelete();                                 // NULL for FINE type (FR-0013)
            $table->enum('transaction_type', ['FEE', 'FINE']);
            $table->foreignId('remittance_id')
                  ->nullable()
                  ->constrained('remittances')
                  ->nullOnDelete();                                     // NULL = unremitted (FR-0020)
            $table->boolean('is_void')->default(false);
            $table->timestamps();

            // Gap-free OR: unique per org (FR-0018)
            $table->unique(['organization_id', 'or_number']);

            // Indexes from schema spec (performance — NFR-001, NFR-013)
            $table->index(['organization_id', 'is_void', 'remittance_id']); // remittance queries
            $table->index(['student_id', 'academic_year_id']);              // payment history
            $table->index(['academic_year_id', 'created_at']);              // date-range reports
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
