<?php

namespace App\Services;

use App\Models\Event;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class AttendancePopulationService
{
    /**
     * Pre-populate event_attendance rows for every eligible enrolled student.
     * All rows default to is_present = false (absent).
     * Uses insertOrIgnore so calling this again (re-sync) safely adds missing rows only.
     *
     * @return int Number of rows inserted.
     */
    public function populate(Event $event): int
    {
        $org   = $event->organization;
        $semId = $event->academic_year_id;
        $slots = $event->slots();
        $now   = now();

        $query = StudentEnrollment::where('academic_year_id', $semId);

        // Scope students to the org's college or department (FR-0027)
        if ($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id) {
            $query->whereHas('program.department', fn ($q) =>
                $q->where('college_id', $org->linked_college_id)
            );
        } elseif ($org->type === 'CLASS_ORG' && $org->linked_department_id) {
            $query->whereHas('program', fn ($q) =>
                $q->where('department_id', $org->linked_department_id)
            );
        }
        // UNIVERSITY_WIDE: no extra filter — all enrolled students

        $inserted = 0;

        $query->select('student_id')->chunk(500, function ($enrollments) use ($event, $slots, $now, &$inserted) {
            $rows = [];
            foreach ($enrollments as $enrollment) {
                foreach ($slots as $slot) {
                    $rows[] = [
                        'event_id'            => $event->id,
                        'student_id'          => $enrollment->student_id,
                        'slot'                => $slot,
                        'is_present'          => false,
                        'recorded_by_user_id' => $event->created_by_user_id,
                        'recorded_at'         => $now,
                        'updated_at'          => $now,
                    ];
                }
            }
            DB::table('event_attendance')->insertOrIgnore($rows);
            $inserted += count($rows);
        });

        return $inserted;
    }
}
