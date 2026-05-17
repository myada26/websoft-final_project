<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add student_type then backfill from is_regular before dropping it
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('student_type')->default('REGULAR')->after('year_level');
        });
        DB::statement("UPDATE student_enrollments SET student_type = CASE WHEN is_regular = TRUE THEN 'REGULAR' ELSE 'IRREGULAR' END");

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('is_regular');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->boolean('is_regular')->default(true)->after('year_level');
        });
        DB::statement("UPDATE student_enrollments SET is_regular = CASE WHEN student_type = 'REGULAR' THEN 1 ELSE 0 END");
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn('student_type');
        });
    }
};
