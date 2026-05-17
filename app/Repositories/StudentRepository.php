<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * [Lab 7] StudentRepository — cached read queries for student data.
 *
 * Cache store is 'file' (CACHE_STORE=file), so Cache::tags() would throw
 * BadMethodCallException. All caching uses Cache::remember() / Cache::forget()
 * with explicit string keys.
 */
class StudentRepository // [Lab 7]
{
    private const TTL = 300; // seconds

    // ── [Lab 7] Method 1 ─────────────────────────────────────────────────────

    /**
     * Look up a single student by exact student_number within a semester.
     * Uses the UNIQUE(student_number) index — plain = operator, not ILIKE.
     *
     * Cache key: "fcats.student.search.{$orgId}.{$semesterId}.{$number}"
     */
    public function searchByStudentNumber(
        string $number,
        int    $orgId,
        int    $semesterId,
    ): ?Student {
        $key = "fcats.student.search.{$orgId}.{$semesterId}.{$number}";

        return Cache::remember($key, self::TTL, function () use ($number, $semesterId) {
            $row = DB::table('students')
                ->join('student_enrollments as se', 'se.student_id', '=', 'students.id')
                ->where('students.student_number', '=', $number)
                ->where('se.academic_year_id', '=', $semesterId)
                ->select([
                    'students.id',
                    'students.student_number',
                    'students.first_name',
                    'students.last_name',
                    'students.email',
                    'se.year_level',
                    'se.student_type',
                    'se.program_id',
                ])
                ->first();

            if (! $row) {
                return null;
            }

            // Hydrate into a Student model instance so callers get a typed object
            return (new Student())->forceFill((array) $row);
        });
    }

    // ── [Lab 7] Method 2 ─────────────────────────────────────────────────────

    /**
     * Return a flat Collection of enrolled students for the POS screen.
     * Plain Collection (not CursorPaginator) because paginator results are not
     * serializable for file-based caching. Speed comes from the composite index
     * idx_enroll_semester_program (academic_year_id, program_id).
     *
     * Cache key: "fcats.students.enrolled.{$orgId}.{$semesterId}"
     */
    public function getEnrolledForPos(
        int $orgId,
        int $semesterId,
        int $perPage = 50,
    ): Collection {
        $key = "fcats.students.enrolled.{$orgId}.{$semesterId}";

        return Cache::remember($key, self::TTL, function () use ($semesterId, $perPage) {
            return DB::table('students')
                ->join('student_enrollments as se', 'se.student_id', '=', 'students.id')
                ->where('se.academic_year_id', '=', $semesterId)
                ->orderBy('students.last_name', 'asc')
                ->orderBy('students.id', 'asc')
                ->limit($perPage)
                ->select([
                    'students.id',
                    'students.student_number',
                    'students.first_name',
                    'students.last_name',
                    'students.email',
                    'se.year_level',
                    'se.student_type',
                    'se.program_id',
                ])
                ->get();
        });
    }

    // ── [Lab 7] Method 3 ─────────────────────────────────────────────────────

    /**
     * Sum outstanding (unpaid) fine amounts for a student within a semester.
     * Not cached — fine balances change on every POS transaction.
     * Returns a DECIMAL(10,2)-safe string (never a raw float).
     */
    public function getOutstandingFineBalance(
        int $studentId,
        int $orgId,
        int $semesterId,
    ): string {
        $result = DB::table('student_fines')
            ->where('student_id', $studentId)
            ->where('organization_id', $orgId)
            ->where('academic_year_id', $semesterId)
            ->where('status', 'UNPAID')
            ->sum('fine_amount');

        return number_format((float) $result, 2, '.', '');
    }

    // ── [Lab 7] Method 4 ─────────────────────────────────────────────────────

    /**
     * Evict cached entries for an org+semester.
     * Always clears the enrolled-list cache.
     * Also clears the per-student search cache when $studentNumber is provided.
     */
    public function invalidateStudentCache(
        int     $orgId,
        int     $semesterId,
        ?string $studentNumber = null,
    ): void {
        Cache::forget("fcats.students.enrolled.{$orgId}.{$semesterId}");

        if ($studentNumber !== null) {
            Cache::forget("fcats.student.search.{$orgId}.{$semesterId}.{$studentNumber}");
        }
    }
}
