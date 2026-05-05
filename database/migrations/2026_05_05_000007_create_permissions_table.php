<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g. "pos:create", "remit:verify"
            $table->string('description');
            $table->string('module'); // e.g. "POS", "REMITTANCE", "REPORTS"
            // No timestamps — permissions are seeded once and never modified
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
