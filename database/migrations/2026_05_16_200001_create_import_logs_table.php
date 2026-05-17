<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');                                       // STUDENT_ENROLLMENT
            $table->string('filename');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->integer('rows_processed')->default(0);
            $table->integer('failures_count')->default(0);
            $table->json('failure_details')->nullable();
            $table->string('status');                                     // PENDING, SUCCESS, PARTIAL, FAILED
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);                // FR-0008 dashboard query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
