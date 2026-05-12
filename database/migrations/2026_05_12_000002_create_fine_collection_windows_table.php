<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fine_collection_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                  ->constrained('organizations')
                  ->restrictOnDelete();
            $table->foreignId('academic_year_id')
                  ->constrained('academic_years')
                  ->restrictOnDelete();
            $table->foreignId('opened_by_user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->foreignId('closed_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');

            $table->unique(['organization_id', 'academic_year_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_collection_windows');
    }
};