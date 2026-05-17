<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');                                       // TRANSACTIONS, STUDENTS, FINES
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->json('filters')->nullable();
            $table->string('format');                                     // XLSX, CSV, PDF
            $table->string('status');                                     // PENDING, PROCESSING, READY, FAILED
            $table->string('download_path')->nullable();
            $table->unsignedBigInteger('row_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
