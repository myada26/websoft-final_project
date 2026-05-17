<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old CHECK constraint so new type values are accepted.
        DB::statement('ALTER TABLE organizations DROP CONSTRAINT IF EXISTS organizations_type_check');

        // Rename legacy data values first (while no constraint blocks them).
        DB::statement("
            UPDATE organizations
            SET type = CASE type
                WHEN 'SSC'          THEN 'UNIVERSITY_WIDE'
                WHEN 'DEPT_SOCIETY' THEN 'CLASS_ORG'
                ELSE type
            END
            WHERE type IN ('SSC', 'DEPT_SOCIETY')
        ");

        // Add the new CHECK constraint with the updated allowed values.
        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_type_check
            CHECK (type IN ('UNIVERSITY_WIDE','COLLEGE_COUNCIL','CLASS_ORG','RESERVED'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE organizations DROP CONSTRAINT IF EXISTS organizations_type_check');

        DB::statement("
            UPDATE organizations
            SET type = CASE type
                WHEN 'UNIVERSITY_WIDE' THEN 'SSC'
                WHEN 'CLASS_ORG'       THEN 'DEPT_SOCIETY'
                ELSE type
            END
            WHERE type IN ('UNIVERSITY_WIDE', 'CLASS_ORG')
        ");

        DB::statement("ALTER TABLE organizations ADD CONSTRAINT organizations_type_check
            CHECK (type IN ('SSC','COLLEGE_COUNCIL','DEPT_SOCIETY'))");
    }
};
