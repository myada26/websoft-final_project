<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Note: SQLite doesn't support CHECK constraints well
        // GCash reference_number validation is handled at application level
        // in TransactionController validation rules
        
        // For future MySQL/PostgreSQL, uncomment below:
        // DB::statement('ALTER TABLE transactions ADD CONSTRAINT 
        //    gcash_reference_check CHECK 
        //    (payment_method != \'GCASH\' OR reference_number IS NOT NULL)');
    }

    public function down(): void
    {
        // No-op for SQLite
    }
};