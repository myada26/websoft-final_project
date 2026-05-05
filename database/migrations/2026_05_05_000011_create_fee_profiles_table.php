<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('name'); // e.g. "Membership Fee", "Irregular Rate A"
            $table->decimal('amount', 10, 2); // DECIMAL(10,2) — never FLOAT (NFR-009)
            $table->enum('category', ['REGULAR', 'IRREGULAR', 'EXTENDEE', 'EXEMPTED']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profiles');
    }
};
