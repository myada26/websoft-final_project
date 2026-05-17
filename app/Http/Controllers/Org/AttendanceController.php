<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AttendancePopulationService;
use App\Services\FineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function sheet(Event $event, Request $request)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);

        // Route model binding does not eager-load relations — load explicitly (NFR-002)
        $event->loadMissing('organization');

        $org    = $event->organization;
        $slots  = $event->slots();
        $search = $request->input('search', '');
        $user   = auth()->user();

        // Ensure attendance rows exist for all eligible students (safe to call repeatedly)
        app(AttendancePopulationService::class)->populate($event);

        // Base enrollment query for this event's semester — scoped per FR-0006 / FR-0027
        $allStudentIds = $this->scopedEnrollmentQuery($event->academic_year_id, $org)
            ->pluck('student_id')
            ->toArray();

        // Attendance map keyed by [student_id][slot]
        $attendanceMap = [];
        foreach (
            EventAttendance::where('event_id', $event->id)
                ->whereIn('student_id', $allStudentIds)
                ->get(['student_id', 'slot', 'is_present'])
            as $row
        ) {
            $attendanceMap[$row->student_id][$row->slot] = (bool) $row->is_present;
        }

        // Paginated student list (for search view)
        $studentQuery = Student::whereIn('id', $allStudentIds)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search) {
            $studentQuery->where(fn($q) =>
                $q->where('last_name',       'like', "%{$search}%")
                  ->orWhere('first_name',    'like', "%{$search}%")
                  ->orWhere('student_number','like', "%{$search}%")
            );
        }

        $students = $studentQuery->paginate(50)->withQueryString();

        // Year-level grouping for the non-search view (single eager-loaded query)
        $studentsByYear = $this->scopedEnrollmentQuery($event->academic_year_id, $org)
            ->with('student')
            ->get()
            ->groupBy('year_level')
            ->sortKeys()
            ->map(fn($enrollments) => $enrollments->pluck('student')->filter()->sortBy('last_name')->values());

        $canEdit        = $event->status === 'DRAFT' && $user->hasRole('SECRETARY');
        $auditorCanEdit = $event->status === 'PENDING_APPROVAL' && $user->hasRole('AUDITOR');

        $attendanceData = [
            'attendance'    => $attendanceMap,
            'canEdit'       => $canEdit || $auditorCanEdit,
            'toggleBaseUrl' => route('org.attendance.toggle-slot', [
                'event'   => $event->id,
                'student' => '__STUDENT__',
                'slot'    => '__SLOT__',
            ]),
            'totalStudents' => count($allStudentIds),
            'programName'   => $org->name,
        ];

        return view('org.attendance.sheet', compact(
            'event', 'slots', 'students', 'attendanceData', 'studentsByYear', 'search'
        ));
    }

    /**
     * Returns a StudentEnrollment query filtered to the org's visibility scope (FR-0006).
     */
    private function scopedEnrollmentQuery(int $academicYearId, \App\Models\Organization $org): \Illuminate\Database\Eloquent\Builder
    {
        $query = StudentEnrollment::where('academic_year_id', $academicYearId);

        if ($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id) {
            $query->whereHas('program.department', fn($q) =>
                $q->where('college_id', $org->linked_college_id)
            );
        } elseif ($org->type === 'CLASS_ORG' && $org->linked_department_id) {
            $query->whereHas('program', fn($q) =>
                $q->where('department_id', $org->linked_department_id)
            );
        }
        // UNIVERSITY_WIDE: no extra filter — all enrolled students are eligible

        return $query;
    }

    public function toggleSlot(Request $request, Event $event, Student $student, string $slot): JsonResponse
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless(in_array($slot, ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT'], true), 422);

        $user = auth()->user();

        if ($user->hasRole('SECRETARY')) {
            abort_unless($event->status === 'DRAFT', 403, 'Attendance can only be edited for DRAFT events.');
        } elseif ($user->hasRole('AUDITOR')) {
            abort_unless($event->status === 'PENDING_APPROVAL', 403, 'Auditor can only edit PENDING_APPROVAL events.');
        } else {
            abort(403);
        }

        $record = EventAttendance::where([
            'event_id'   => $event->id,
            'student_id' => $student->id,
            'slot'       => $slot,
        ])->first();

        $wasPresent = $record ? $record->is_present : false;
        $nowPresent = !$wasPresent;

        if ($record) {
            $record->update([
                'is_present'          => $nowPresent,
                'recorded_by_user_id' => $user->id,
                'recorded_at'         => now(),
                'updated_at'          => now(),
            ]);
        } else {
            $record = EventAttendance::create([
                'event_id'            => $event->id,
                'student_id'          => $student->id,
                'slot'                => $slot,
                'is_present'          => $nowPresent,
                'recorded_by_user_id' => $user->id,
                'recorded_at'         => now(),
                'updated_at'          => now(),
            ]);
        }

        if ($user->hasRole('AUDITOR')) {
            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'ATTENDANCE_EDITED_BY_AUDITOR',
                'entity_type' => 'EVENT',
                'entity_id'   => $event->id,
                'details'     => [
                    'student_id' => $student->id,
                    'slot'       => $slot,
                    'old_value'  => $wasPresent,
                    'new_value'  => $nowPresent,
                ],
                'ip_address' => $request->ip(),
                'timestamp'  => now(),
            ]);
        }

        return response()->json(['is_present' => $record->is_present]);
    }

    public function saveDraft(Request $request, Event $event): \Illuminate\Http\JsonResponse
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'DRAFT', 403, 'Only DRAFT events can be saved.');
        abort_unless(auth()->user()->hasRole('SECRETARY'), 403);

        return response()->json(['saved' => true, 'at' => now()->format('H:i:s')]);
    }

    public function submit(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'DRAFT', 403, 'Only DRAFT events can be submitted.');
        abort_unless(auth()->user()->hasRole('SECRETARY'), 403);

        $snapshot = EventAttendance::where('event_id', $event->id)
            ->get(['student_id', 'slot', 'is_present'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('is_present', 'slot')->toArray())
            ->toArray();

        $event->update([
            'status'               => 'PENDING_APPROVAL',
            'submitted_by_user_id' => auth()->user()->id,
            'submitted_at'         => now(),
            'secretary_snapshot'   => $snapshot,
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'ATTENDANCE_SUBMITTED',
            'entity_type' => 'EVENT',
            'entity_id'   => $event->id,
            'details'     => ['event_name' => $event->name],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return redirect()->route('org.events.show', $event)
            ->with('success', 'Attendance submitted for auditor review.');
    }

    public function auditorApprove(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_APPROVAL', 403, 'Event is not pending approval.');
        abort_unless(auth()->user()->hasRole('AUDITOR'), 403);

        $finesCount = 0;

        DB::transaction(function () use ($event, $request, &$finesCount) {
            if (!$event->fines()->exists()) {
                $finesCount = app(FineService::class)->computeFines($event);
            }

            $event->update([
                'status'                      => 'APPROVED',
                'auditor_reviewed_by_user_id' => auth()->user()->id,
                'auditor_reviewed_at'         => now(),
                'approved_by_user_id'         => auth()->user()->id,
                'approved_at'                 => now(),
            ]);

            AuditLog::create([
                'user_id'     => auth()->user()->id,
                'action'      => 'ATTENDANCE_APPROVED_BY_AUDITOR',
                'entity_type' => 'EVENT',
                'entity_id'   => $event->id,
                'details'     => ['event_name' => $event->name],
                'ip_address'  => $request->ip(),
                'timestamp'   => now(),
            ]);

            AuditLog::create([
                'user_id'     => auth()->user()->id,
                'action'      => 'FINES_COMPUTED',
                'entity_type' => 'EVENT',
                'entity_id'   => $event->id,
                'details'     => ['fines_created' => $finesCount],
                'ip_address'  => $request->ip(),
                'timestamp'   => now(),
            ]);
        });

        return redirect()->route('org.events.show', $event)
            ->with('success', "Attendance approved. {$finesCount} fine record(s) computed.");
    }

    public function auditorForward(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_APPROVAL', 403, 'Event is not pending approval.');
        abort_unless(auth()->user()->hasRole('AUDITOR'), 403);

        $event->update([
            'status'                      => 'PENDING_CHAIRPERSON',
            'auditor_reviewed_by_user_id' => auth()->user()->id,
            'auditor_reviewed_at'         => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'ATTENDANCE_SENT_TO_CHAIRPERSON',
            'entity_type' => 'EVENT',
            'entity_id'   => $event->id,
            'details'     => ['event_name' => $event->name],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return redirect()->route('org.events.show', $event)
            ->with('success', 'Attendance forwarded to Chairperson for final review.');
    }

    public function auditorReject(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_APPROVAL', 403, 'Event is not pending approval.');
        abort_unless(auth()->user()->hasRole('AUDITOR'), 403);

        $reason = $request->validate(['rejection_reason' => 'required|string|max:2000'])['rejection_reason'];

        $event->update([
            'status'                      => 'DRAFT',
            'auditor_reviewed_by_user_id' => auth()->user()->id,
            'auditor_reviewed_at'         => now(),
            'rejection_reason'            => $reason,
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'ATTENDANCE_REJECTED_BY_AUDITOR',
            'entity_type' => 'EVENT',
            'entity_id'   => $event->id,
            'details'     => ['reason' => $reason],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return redirect()->route('org.events.show', $event)
            ->with('success', 'Attendance rejected and returned to Secretary for revision.');
    }

    public function diff(Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_CHAIRPERSON', 403, 'Diff view is only available for events pending Chairperson review.');
        abort_unless(auth()->user()->hasRole('CHAIRPERSON'), 403);

        $slots            = $event->slots();
        $secretarySnapshot = $event->secretary_snapshot ?? [];

        $currentAttendance = EventAttendance::where('event_id', $event->id)
            ->whereIn('slot', $slots)
            ->get(['student_id', 'slot', 'is_present'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('is_present', 'slot')->toArray())
            ->toArray();

        $studentIds = array_unique(array_merge(
            array_keys($secretarySnapshot),
            array_keys($currentAttendance)
        ));

        $students = Student::whereIn('id', $studentIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('org.attendance.diff', compact(
            'event', 'slots', 'students', 'secretarySnapshot', 'currentAttendance'
        ));
    }

    public function chairpersonConfirm(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_CHAIRPERSON', 403, 'Event is not awaiting Chairperson confirmation.');
        abort_unless(auth()->user()->hasRole('CHAIRPERSON'), 403);

        $finesCount = 0;

        DB::transaction(function () use ($event, $request, &$finesCount) {
            if (!$event->fines()->exists()) {
                $finesCount = app(FineService::class)->computeFines($event);
            }

            $event->update([
                'status'              => 'APPROVED',
                'approved_by_user_id' => auth()->user()->id,
                'approved_at'         => now(),
            ]);

            AuditLog::create([
                'user_id'     => auth()->user()->id,
                'action'      => 'ATTENDANCE_APPROVED_BY_CHAIRPERSON',
                'entity_type' => 'EVENT',
                'entity_id'   => $event->id,
                'details'     => ['event_name' => $event->name],
                'ip_address'  => $request->ip(),
                'timestamp'   => now(),
            ]);

            AuditLog::create([
                'user_id'     => auth()->user()->id,
                'action'      => 'FINES_COMPUTED',
                'entity_type' => 'EVENT',
                'entity_id'   => $event->id,
                'details'     => ['fines_created' => $finesCount],
                'ip_address'  => $request->ip(),
                'timestamp'   => now(),
            ]);
        });

        return redirect()->route('org.events.show', $event)
            ->with('success', "Attendance confirmed. {$finesCount} fine record(s) computed.");
    }

    public function chairpersonReject(Request $request, Event $event)
    {
        abort_unless($event->organization_id === auth()->user()->organization_id, 403);
        abort_unless($event->status === 'PENDING_CHAIRPERSON', 403, 'Event is not awaiting Chairperson review.');
        abort_unless(auth()->user()->hasRole('CHAIRPERSON'), 403);

        $reason = $request->validate(['rejection_reason' => 'required|string|max:2000'])['rejection_reason'];

        $event->update([
            'status'           => 'PENDING_APPROVAL',
            'rejection_reason' => $reason,
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'ATTENDANCE_REJECTED_BY_CHAIRPERSON',
            'entity_type' => 'EVENT',
            'entity_id'   => $event->id,
            'details'     => ['reason' => $reason],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return redirect()->route('org.events.show', $event)
            ->with('success', 'Edits rejected. Attendance returned to Auditor for re-review.');
    }
}