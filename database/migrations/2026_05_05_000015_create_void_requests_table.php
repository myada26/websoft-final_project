<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                  ->unique()  // one void request per transaction (FR-0019)
                  ->constrained('transactions')
                  ->restrictOnDelete();
            $table->foreignId('requested_by_user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->foreignId('approved_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->text('reason');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('resolved_at')->nullable(); // set on APPROVED or REJECTED
            // No updated_at — status changes are captured via resolved_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_requests');
    }
};
