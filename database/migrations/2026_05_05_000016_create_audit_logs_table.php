<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action');       // e.g. "TRANSACTION_CREATED", "VOID_APPROVED"
            $table->string('entity_type'); // e.g. "TRANSACTION", "REMITTANCE", "USER"
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('details')->nullable(); // old/new values snapshot
            $table->string('ip_address', 45);
            $table->timestamp('timestamp');     // intentionally named 'timestamp' per schema spec
            // No updated_at, no deleted_at — immutable (FR-0025)

            // Indexes from schema spec
            $table->index(['user_id', 'timestamp']);       // user activity queries
            $table->index(['entity_type', 'entity_id']);   // entity-specific trail
            $table->index('timestamp');                    // date-range queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
