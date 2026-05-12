<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                  ->constrained('organizations')
                  ->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->foreignId('uploaded_by_user_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};