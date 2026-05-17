<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Note: SQLite doesn't support complex CHECK constraints
        // Organization type constraints are handled at application level.
        // See Organization model and OrganizationController.
    }

    public function down(): void
    {
        // No-op
    }
};