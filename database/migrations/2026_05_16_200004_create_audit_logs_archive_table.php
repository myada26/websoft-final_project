<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Long-term archive: audit_logs rows older than 1 year are moved here (5-year minimum, NFR-014)
        Schema::create('audit_logs_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('content_hash', 64)->nullable();              // SHA-256 tamper detection
            $table->timestamp('timestamp');
            $table->timestamp('archived_at')->useCurrent();

            $table->index('original_id');
            $table->index(['action', 'timestamp']);
            $table->index(['entity_type', 'entity_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs_archive');
    }
};
