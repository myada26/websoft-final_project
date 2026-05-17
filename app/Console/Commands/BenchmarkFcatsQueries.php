<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * [Lab 7] BenchmarkFcatsQueries — measures wall-clock latency of the 6 core
 * FCATS read queries against the live database.
 *
 * Usage:
 *   php artisan benchmark:fcats
 *   php artisan benchmark:fcats --iterations=200
 *
 * Exits with Command::FAILURE if any benchmark's average exceeds its target,
 * making it usable as a CI/CD performance gate.
 */
class BenchmarkFcatsQueries extends Command // [Lab 7]
{
    protected $signature   = 'benchmark:fcats {--iterations=100 : Number of timed iterations per benchmark}';
    protected $description = '[Lab 7] Benchmark core FCATS DB queries and report avg/min/max latency';

    public function handle(): int
    {
        $iterations = max(1, (int) $this->option('iterations'));

        $this->info('');
        $this->info("[Lab 7] FCATS Query Benchmark — {$iterations} iteration(s) per query");
        $this->info(str_repeat('─', 72));

        // ── Load stable fixture IDs so benchmarks hit real rows ───────────────

        $activeYear = AcademicYear::where('is_active', true)->first();
        $semId      = $activeYear?->id ?? DB::table('academic_years')->min('id');
        $orgId      = DB::table('organizations')->where('is_active', true)->min('id');
        $studentId  = DB::table('student_enrollments')
            ->where('academic_year_id', $semId)
            ->min('student_id') ?? 1;

        // Use a real student_number so the UNIQUE index is exercised
        $studentNumber = DB::table('students')
            ->where('id', $studentId)
            ->value('student_number') ?? '2024000001';

        // ── Define benchmarks ─────────────────────────────────────────────────

        $benchmarks = [
            [
                'name'      => 'Student search (exact number)',
                'target_ms' => 50,
                'run'       => function () use ($studentNumber): void {
                    Student::where('student_number', $studentNumber)->first();
                },
            ],
            [
                'name'      => 'POS enrolled listing',
                'target_ms' => 100,
                'run'       => function () use ($semId): void {
                    DB::table('students')
                        ->join('student_enrollments as se', 'se.student_id', '=', 'students.id')
                        ->where('se.academic_year_id', $semId)
                        ->orderBy('students.last_name', 'asc')
                        ->orderBy('students.id', 'asc')
                        ->limit(50)
                        ->select([
                            'students.id', 'students.student_number',
                            'students.first_name', 'students.last_name',
                            'se.year_level', 'se.student_type', 'se.program_id',
                        ])
                        ->get();
                },
            ],
            [
                'name'      => 'Transaction history',
                'target_ms' => 100,
                'run'       => function () use ($orgId, $semId, $studentId): void {
                    Transaction::forOrg($orgId)
                        ->forSemester($semId)
                        ->where('student_id', $studentId)
                        ->get();
                },
            ],
            [
                'name'      => 'Collection summary',
                'target_ms' => 200,
                'run'       => function () use ($orgId, $semId): void {
                    DB::table('transactions')
                        ->where('organization_id', $orgId)
                        ->where('academic_year_id', $semId)
                        ->where('is_void', false)
                        ->groupBy(['transaction_type', 'payment_method'])
                        ->select([
                            'transaction_type',
                            'payment_method',
                            DB::raw('COUNT(*) as c'),
                            DB::raw('SUM(amount_paid) as s'),
                        ])
                        ->get();
                },
            ],
            [
                'name'      => 'Audit log entity lookup',
                'target_ms' => 150,
                'run'       => function () use ($studentId): void {
                    AuditLog::where('entity_type', 'TRANSACTION')
                        ->where('entity_id', $studentId)
                        ->first();
                },
            ],
            [
                'name'      => 'ILIKE name search (pg_trgm)',
                'target_ms' => 300,
                'run'       => function (): void {
                    DB::table('students')
                        ->whereRaw(
                            "(first_name || ' ' || last_name) ILIKE ?",
                            ['%Santos%']
                        )
                        ->limit(20)
                        ->get();
                },
            ],
        ];

        // ── Warmup pass — exercises PostgreSQL's query planner once ───────────

        $this->line('  Warming up query plans…');
        foreach ($benchmarks as $b) {
            ($b['run'])();
        }
        $this->line('');

        // ── Timed iterations ──────────────────────────────────────────────────

        $passed = 0;
        $total  = count($benchmarks);

        foreach ($benchmarks as $b) {
            $samples = [];

            for ($i = 0; $i < $iterations; $i++) {
                $start     = hrtime(true);
                ($b['run'])();
                $samples[] = (hrtime(true) - $start) / 1_000_000; // ns → ms
            }

            $avg = array_sum($samples) / count($samples);
            $min = min($samples);
            $max = max($samples);

            $pass = $avg <= $b['target_ms'];
            if ($pass) {
                $passed++;
            }

            $tag    = $pass ? '<fg=green>[PASS]</>' : '<fg=red>[FAIL]</>';
            $label  = str_pad($b['name'] . ':', 36);
            $avgFmt = str_pad('avg ' . number_format($avg, 1) . 'ms', 16);
            $minFmt = str_pad('min ' . number_format($min, 1) . 'ms', 16);
            $maxFmt = 'max ' . number_format($max, 1) . 'ms';
            $target = '(target < ' . $b['target_ms'] . 'ms)';

            $this->line("  {$tag} {$label} {$avgFmt} {$minFmt} {$maxFmt}  {$target}");
        }

        // ── Summary ───────────────────────────────────────────────────────────

        $this->info('');
        $this->info(str_repeat('─', 72));

        if ($passed === $total) {
            $this->info("<fg=green>{$passed}/{$total} benchmarks passed.</>");
        } else {
            $failed = $total - $passed;
            $this->info("<fg=red>{$failed} benchmark(s) failed — {$passed}/{$total} passed.</>");
        }

        $this->info('');

        return $passed === $total ? self::SUCCESS : self::FAILURE;
    }
}
