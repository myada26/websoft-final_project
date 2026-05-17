<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * [Lab 7] FcatsMassSeeder — adds high-volume realistic data on top of existing seed data.
 *
 * Run order (safe to run after DatabaseSeeder has already been called):
 *   1. seedStudents()      — 10,000 students via factory + seedInChunks
 *   2. seedEnrollments()   — bulk-enroll new students into the active academic year
 *   3. seedTransactions()  — 50,000 transactions; replaces SEED-XXXXXXXX placeholders
 *                            with real sequential OR numbers then UPDATEs or_sequences
 *   4. seedRemittances()   — batches ~60% of non-void transactions into Remittance rows
 *   5. seedVoidRequests()  — creates an APPROVED VoidRequest for every is_void=TRUE txn
 *   6. seedAuditLogs()     — 100,000 audit log entries via factory + seedInChunks
 *
 * Does NOT call or replace: DatabaseSeeder, PermissionSeeder, RolePermissionSeeder,
 * EventsSeeder, DummyStudentSeeder, or AuditLogSeeder.
 */
class FcatsMassSeeder extends Seeder
{
    // ── Volume constants ──────────────────────────────────────────────

    private const STUDENTS     = 10_000;
    private const TRANSACTIONS = 50_000;
    private const AUDIT_LOGS   = 100_000;

    private float $startTime;

    // ── Entry point ───────────────────────────────────────────────────

    public function run(): void
    {
        $this->startTime = microtime(true);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║  [Lab 7] FcatsMassSeeder — ' . now()->toDateTimeString() . '  ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info("  Students:     " . number_format(self::STUDENTS));
        $this->command->info("  Transactions: " . number_format(self::TRANSACTIONS));
        $this->command->info("  Audit Logs:   " . number_format(self::AUDIT_LOGS));
        $this->command->info('');

        $this->seedStudents();
        $this->seedEnrollments();
        $this->seedTransactions();
        $this->seedRemittances();
        $this->seedVoidRequests();
        $this->seedAuditLogs();

        $elapsed  = round(microtime(true) - $this->startTime, 2);
        $peakMb   = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

        $this->command->info('');
        $this->command->info("[Lab 7] FcatsMassSeeder finished in {$elapsed}s — peak mem: {$peakMb}MB");
    }

    // ── [Lab 7] Generic chunked bulk-inserter ─────────────────────────────────

    /**
     * Creates $total rows via $modelClass::factory()->make() in 1,000-row chunks,
     * normalises array/DateTime values for raw DB insert, and bulk-inserts into $table.
     * gc_collect_cycles() is called every 10 chunks to contain peak memory.
     */
    private function seedInChunks(string $table, string $modelClass, int $total): void
    {
        $batchSize = 1_000;
        $batches   = (int) ceil($total / $batchSize);

        $this->command->line("  Seeding {$total} rows into [{$table}] ({$batches} chunk(s))…");

        for ($i = 0; $i < $batches; $i++) {
            $count = min($batchSize, $total - $i * $batchSize);

            /** @var \Illuminate\Database\Eloquent\Collection $models */
            $models = $modelClass::factory()->count($count)->make();

            // toArray() serializes Carbon → 'Y-m-d H:i:s'; normalizeRow handles
            // residual PHP arrays (e.g. AuditLog.details cast) and raw DateTime objects.
            $rows = array_map(
                [$this, 'normalizeRow'],
                $models->toArray()
            );

            DB::table($table)->insert($rows);

            // Report every 10 chunks and on the final chunk
            if (($i + 1) % 10 === 0 || ($i + 1) === $batches) {
                gc_collect_cycles();
                $pct = (int) round(($i + 1) / $batches * 100);
                $mb  = round(memory_get_usage(true) / 1024 / 1024, 1);
                $this->command->line("    chunk " . ($i + 1) . "/{$batches} ({$pct}%) — mem {$mb}MB");
            }
        }
    }

    /**
     * [Lab 7] Normalize a factory-generated row for raw DB::table()->insert().
     * Eloquent casts (e.g. 'array') are unwound by toArray() back to PHP types;
     * raw DB insert does not re-apply them, so we encode manually here.
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_array($value)) {
                // 'array' cast fields (e.g. AuditLog.details) → JSON string
                $row[$key] = json_encode($value);
            } elseif ($value instanceof \DateTimeInterface) {
                // Residual DateTime / Carbon that toArray() didn't convert
                $row[$key] = $value->format('Y-m-d H:i:s');
            }
        }
        return $row;
    }

    // ── [Lab 7] Step 1: Students ──────────────────────────────────────────────

    private function seedStudents(): void
    {
        $this->command->info('[1/6] Seeding students…');
        $this->seedInChunks('students', Student::class, self::STUDENTS);
    }

    // ── [Lab 7] Step 2: Enrollments ───────────────────────────────────────────

    /**
     * Bulk-enroll all students that have no enrollment for the active academic year.
     * Assigns a random program from the programs table.
     */
    private function seedEnrollments(): void
    {
        $this->command->info('[2/6] Seeding enrollments…');

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            $this->command->warn('  No active academic year — skipping enrollments.');
            return;
        }

        $programIds = DB::table('programs')->pluck('id')->all();
        if (empty($programIds)) {
            $this->command->warn('  No programs found — skipping enrollments.');
            return;
        }

        // Find students without an enrollment for the active year
        $enrolledStudentIds = DB::table('student_enrollments')
            ->where('academic_year_id', $activeYear->id)
            ->pluck('student_id')
            ->all();

        $unenrolled = DB::table('students')
            ->when(
                ! empty($enrolledStudentIds),
                fn($q) => $q->whereNotIn('id', $enrolledStudentIds)
            )
            ->pluck('id')
            ->all();

        if (empty($unenrolled)) {
            $this->command->line('  All students already enrolled — skipping.');
            return;
        }

        $now  = now()->format('Y-m-d H:i:s');
        $rows = [];

        foreach ($unenrolled as $studentId) {
            $rows[] = [
                'student_id'       => $studentId,
                'academic_year_id' => $activeYear->id,
                'program_id'       => $programIds[array_rand($programIds)],
                'year_level'       => rand(1, 5),
                'student_type'     => 'REGULAR',
                'created_at'       => $now,
                'updated_at'       => $now,
            ];

            if (count($rows) === 500) {
                DB::table('student_enrollments')->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('student_enrollments')->insert($rows);
        }

        $this->command->line('  Enrolled ' . number_format(count($unenrolled)) . ' student(s).');
    }

    // ── [Lab 7] Step 3: Transactions ──────────────────────────────────────────

    /**
     * Seeds 50,000 transactions with factory-generated placeholder OR numbers
     * (SEED-XXXXXXXX), then overwrites them with real sequential OR numbers per org
     * (OR-YYYY-NNNNN) and UPDATEs or_sequences. Never INSERTs into or_sequences
     * unless the row is genuinely missing (upsert covers that edge case).
     */
    private function seedTransactions(): void
    {
        $this->command->info('[3/6] Seeding transactions…');
        $this->seedInChunks('transactions', Transaction::class, self::TRANSACTIONS);

        // ── Overwrite placeholder OR numbers with real sequential values ───────

        $this->command->line('  Assigning real OR numbers per org…');

        // Load current counters from or_sequences (one row per org, PK = organization_id)
        $sequences = DB::table('or_sequences')->get()->keyBy('organization_id');

        // Load only the placeholder transactions we just seeded
        $placeholders = DB::table('transactions')
            ->where('or_number', 'like', 'SEED-%')
            ->orderBy('organization_id')
            ->orderBy('created_at')
            ->select('id', 'organization_id')
            ->get();

        if ($placeholders->isEmpty()) {
            $this->command->line('  No placeholder OR numbers found — skipping.');
            return;
        }

        // Build new counters per org, starting from the current last_or_number
        $counters = [];
        foreach ($sequences as $orgId => $seq) {
            $counters[$orgId] = (int) $seq->last_or_number;
        }

        $year    = date('Y');
        $updates = [];

        foreach ($placeholders as $txn) {
            $orgId = $txn->organization_id;

            if (! isset($counters[$orgId])) {
                $counters[$orgId] = 0;
            }

            $counters[$orgId]++;
            $updates[] = [
                'id'        => $txn->id,
                'or_number' => sprintf('OR-%s-%05d', $year, $counters[$orgId]),
            ];
        }

        // Batch UPDATE using CASE WHEN — 500 rows per statement
        foreach (array_chunk($updates, 500) as $chunk) {
            $ids    = array_column($chunk, 'id');
            $cases  = implode(' ', array_map(
                fn($u) => "WHEN {$u['id']} THEN '{$u['or_number']}'",
                $chunk
            ));
            $idList = implode(',', $ids);

            DB::statement(
                "UPDATE transactions SET or_number = CASE id {$cases} END WHERE id IN ({$idList})"
            );
        }

        // UPDATE or_sequences — use updateOrInsert to handle missing rows gracefully
        $now = now()->format('Y-m-d H:i:s');

        foreach ($counters as $orgId => $lastNum) {
            DB::table('or_sequences')->updateOrInsert(
                ['organization_id' => $orgId],
                ['last_or_number'  => $lastNum, 'updated_at' => $now]
            );
        }

        $this->command->line(
            '  Assigned OR numbers for ' . number_format($placeholders->count()) . ' transaction(s) '
                . 'across ' . count($counters) . ' org(s).'
        );
    }

    // ── [Lab 7] Step 4: Remittances ───────────────────────────────────────────

    /**
     * Batches ~60% of non-void, unremitted transactions into Remittance records.
     * Transactions are grouped by (organization_id, academic_year_id) and split into
     * batches of 30 per remittance.
     */
    private function seedRemittances(): void
    {
        $this->command->info('[4/6] Seeding remittances…');

        $userIds = DB::table('users')->pluck('id')->all();
        if (empty($userIds)) {
            $this->command->warn('  No users found — skipping remittances.');
            return;
        }

        // Load non-void, unremitted transactions (all in memory — ~4 fields × 30k rows ≈ 3MB)
        $eligible = DB::table('transactions')
            ->whereNull('remittance_id')
            ->where('is_void', false)
            ->select('id', 'organization_id', 'academic_year_id', 'amount_paid')
            ->get();

        if ($eligible->isEmpty()) {
            $this->command->line('  No eligible transactions — skipping.');
            return;
        }

        // Randomly sample 60%
        $eligible = $eligible->shuffle()->take((int) ($eligible->count() * 0.60));

        // Group by org + academic year
        $groups = $eligible->groupBy(fn($t) => $t->organization_id . '_' . $t->academic_year_id);

        // Starting counter offset so control numbers never collide with existing ones
        $counter       = DB::table('remittances')->count();
        $year          = date('Y');
        $now           = now()->format('Y-m-d H:i:s');
        $remittanceRows    = [];
        $transactionBatches = []; // controlNumber => [transaction_ids]

        foreach ($groups as $groupKey => $groupTxns) {
            [$orgId, $yearId] = explode('_', $groupKey, 2);

            // Split each group into batches of ~30 transactions per remittance
            foreach ($groupTxns->chunk(30) as $batch) {
                $counter++;
                $controlNumber = sprintf('REM-%s-%05d', $year, $counter);
                $totalAmount   = number_format(
                    $batch->sum(fn($t) => (float) $t->amount_paid),
                    2,
                    '.',
                    ''
                );

                $remittanceRows[]                      = [
                    'control_number'      => $controlNumber,
                    'organization_id'     => (int) $orgId,
                    'academic_year_id'    => (int) $yearId,
                    'total_amount'        => $totalAmount,
                    'created_by_user_id'  => $userIds[array_rand($userIds)],
                    'verified_by_user_id' => null,
                    'accepted_by_user_id' => null,
                    'verified_at'         => null,
                    'accepted_at'         => null,
                    'status'              => 'PENDING',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
                $transactionBatches[$controlNumber] = $batch->pluck('id')->all();
            }
        }

        if (empty($remittanceRows)) {
            $this->command->line('  Nothing to remit — skipping.');
            return;
        }

        // Insert remittances in 200-row chunks
        foreach (array_chunk($remittanceRows, 200) as $chunk) {
            DB::table('remittances')->insert($chunk);
        }

        // Reload the auto-generated IDs by control_number
        $controlToId = DB::table('remittances')
            ->whereIn('control_number', array_keys($transactionBatches))
            ->pluck('id', 'control_number');

        // Link transactions back to their remittance_id
        foreach ($transactionBatches as $controlNumber => $txnIds) {
            $remId = $controlToId[$controlNumber] ?? null;
            if (! $remId) {
                continue;
            }
            foreach (array_chunk($txnIds, 500) as $idChunk) {
                DB::table('transactions')
                    ->whereIn('id', $idChunk)
                    ->update(['remittance_id' => $remId]);
            }
        }

        $remCount = count($remittanceRows);
        $txnCount = $eligible->count();
        $this->command->line("  Created {$remCount} remittance(s) covering {$txnCount} transaction(s).");
    }

    // ── [Lab 7] Step 5: Void Requests ─────────────────────────────────────────

    /**
     * Creates an APPROVED VoidRequest for every is_void=TRUE transaction that does
     * not already have one. resolved_at is set to created_at + 1–24 hours.
     */
    private function seedVoidRequests(): void
    {
        $this->command->info('[5/6] Seeding void requests…');

        $userIds = DB::table('users')->pluck('id')->all();
        if (empty($userIds)) {
            $this->command->warn('  No users found — skipping void requests.');
            return;
        }

        // Exclude transactions that already have a void request
        $existingTxnIds = DB::table('void_requests')->pluck('transaction_id')->all();

        $voidedTxns = DB::table('transactions')
            ->where('is_void', true)
            ->when(
                ! empty($existingTxnIds),
                fn($q) => $q->whereNotIn('id', $existingTxnIds)
            )
            ->select('id', 'created_at')
            ->get();

        if ($voidedTxns->isEmpty()) {
            $this->command->line('  No new voided transactions — skipping.');
            return;
        }

        $reasons = [
            'Duplicate entry',
            'Wrong amount recorded',
            'Student requested cancellation',
            'Payment not received',
            'Administrative error',
            'Wrong student record',
        ];

        $rows = [];

        foreach ($voidedTxns as $txn) {
            $createdTs  = strtotime($txn->created_at);
            $resolvedTs = $createdTs + rand(3_600, 86_400); // 1–24 hours later

            $rows[] = [
                'transaction_id'       => $txn->id,
                'requested_by_user_id' => $userIds[array_rand($userIds)],
                'approved_by_user_id'  => $userIds[array_rand($userIds)],
                'reason'               => $reasons[array_rand($reasons)],
                'status'               => 'APPROVED',
                'resolved_at'          => date('Y-m-d H:i:s', $resolvedTs),
                'created_at'           => $txn->created_at,
                // no updated_at — VoidRequest::UPDATED_AT = null
            ];

            if (count($rows) === 500) {
                DB::table('void_requests')->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('void_requests')->insert($rows);
        }

        $this->command->line('  Created ' . number_format($voidedTxns->count()) . ' void request(s).');
    }

    // ── [Lab 7] Step 6: Audit Logs ────────────────────────────────────────────

    private function seedAuditLogs(): void
    {
        $this->command->info('[6/6] Seeding audit logs…');
        $this->seedInChunks('audit_logs', AuditLog::class, self::AUDIT_LOGS);
    }
}
