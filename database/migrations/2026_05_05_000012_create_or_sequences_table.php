<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('or_sequences', function (Blueprint $table) {
            // organization_id IS the primary key — one row per org
            $table->foreignId('organization_id')
                  ->primary()
                  ->constrained('organizations')
                  ->restrictOnDelete();
            $table->unsignedInteger('last_or_number')->default(0);
            $table->timestamp('updated_at')->nullable();
            // No id, no created_at — this table is a counter only
            // Reads must use SELECT FOR UPDATE (row-level lock) — see OrSequenceService
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('or_sequences');
    }
};
