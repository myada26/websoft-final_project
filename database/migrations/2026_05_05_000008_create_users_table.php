<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('username')->unique(); // pattern: {student_number}-{org_code}
            $table->string('password_hash');      // bcrypt — User model overrides getAuthPasswordName()
            $table->string('role');               // e.g. "Treasurer", "Auditor", "President"
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->unsignedTinyInteger('failed_login_attempts')->default(0); // FR-0004
            $table->timestamp('locked_until')->nullable();                     // FR-0004: 15-min lockout
            $table->timestamps();

            $table->unique(['student_id', 'organization_id']); // one officer account per org
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
