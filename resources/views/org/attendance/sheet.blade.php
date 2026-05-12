@extends('layouts.app')
@section('title', 'Attendance Sheet – ' . $event->name)
@section('page-title', 'Attendance Sheet')

@section('content')
<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <a href="{{ route('org.events.show', $event) }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Event
        </a>
        @if(auth()->user()->hasRole('SECRETARY') && $event->status === 'DRAFT')
        <span style="font-size:12px;background:#fef9ec;color:#92400e;padding:4px 10px;border-radius:8px;font-weight:600">Editing — not yet submitted</span>
        @elseif($event->status === 'PENDING_APPROVAL' && auth()->user()->hasRole('AUDITOR'))
        <span style="font-size:12px;background:#eff6ff;color:#1d4ed8;padding:4px 10px;border-radius:8px;font-weight:600">Auditor review mode</span>
        @else
        <span style="font-size:12px;background:#f3f4f6;color:#374151;padding:4px 10px;border-radius:8px;font-weight:600">Read-only view</span>
        @endif
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#15803d;font-weight:600">{{ session('success') }}</div>
    @endif

    <div style="background:white;border:1px solid #dde8e1;border-radius:10px;padding:14px 18px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="font-size:12px;color:#8aa89a">
                <strong style="color:#4a6356">{{ $attendanceData['programName'] }}</strong>
            </div>
            <div style="margin-left:auto">
                <form method="GET" action="{{ route('org.attendance.sheet', $event) }}" style="display:flex;gap:8px">
                    <input type="text" name="search" value="{{ $search ?? '' }}" 
                        placeholder="Search student name or number..."
                        style="padding:6px 12px;border:1px solid #dde8e1;border-radius:6px;font-size:13px;width:250px"
                        onkeydown="if(event.key==='Enter')this.form.submit()">
                    <button type="submit" style="padding:6px 12px;background:#1a7a41;color:white;border:none;border-radius:6px;font-size:13px;cursor:pointer">Search</button>
                    @if($search)
                    <a href="{{ route('org.attendance.sheet', $event) }}" style="padding:6px 12px;background:#f3f4f6;color:#4a6356;border:none;border-radius:6px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center">Clear</a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div x-data="attendanceSheet()" x-init="init()">

        <div style="background:white;border:1px solid #dde8e1;border-radius:10px;padding:12px 18px;margin-bottom:12px;display:flex;gap:16px;flex-wrap:wrap;align-items:center">
            <div style="font-size:12.5px;font-weight:700;color:#0f1f17">Attendance Counter</div>
            <template x-for="slot in slots" :key="slot">
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:11px;font-weight:600;color:#8aa89a;text-transform:uppercase" x-text="slotLabel(slot)"></span>
                    <span style="font-size:13px;font-weight:700;color:#1a7a41" x-text="presentCount(slot) + ' / ' + totalStudents"></span>
                </div>
            </template>
            <div style="margin-left:auto;font-size:12px;color:#8aa89a" x-show="hasPendingRequests">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                Saving...
            </div>
        </div>

        @if($search)
        {{-- Search Results View --}}
        <div class="card" style="overflow-x:auto">
            <div style="padding:10px 14px;background:#f0f3f1;border-bottom:1px solid #dde8e1;font-size:12px;color:#4a6356;font-weight:600">
                Search Results ({{ $students->total() }} students)
            </div>
            <table style="width:100%;border-collapse:collapse;min-width:500px">
                <thead>
                    <tr style="background:#f0f3f1;border-bottom:1px solid #dde8e1">
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase">Student</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase">Year</th>
                        <template x-for="slot in slots" :key="slot">
                            <th style="padding:9px 13px;text-align:center;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase" x-text="slotLabel(slot)"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    @php
                        $enrollment = \App\Models\StudentEnrollment::where('student_id', $student->id)
                            ->where('academic_year_id', $event->academic_year_id)
                            ->first();
                    @endphp
                    <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                        <td style="padding:9px 13px">
                            <div style="font-size:13px;font-weight:600;color:#0f1f17">{{ $student->full_name }}</div>
                            <div style="font-size:11px;color:#8aa89a">{{ $student->student_number }}</div>
                        </td>
                        <td style="padding:9px 13px;font-size:12px;color:#4a6356">
                            {{ $enrollment?->year_level ?? '-' }}st Year
                        </td>
                        <template x-for="slot in slots" :key="slot">
                            <td style="padding:9px 13px;text-align:center">
                                <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer"
                                       :style="canEdit ? '' : 'cursor:default'">
                                    <input type="checkbox"
                                        :checked="isPresent({{ $student->id }}, slot)"
                                        :disabled="!canEdit || isPending({{ $student->id }}, slot)"
                                        @change="toggleSlot({{ $student->id }}, slot)"
                                        style="width:18px;height:18px;accent-color:#1a7a41;cursor:inherit" />
                                </label>
                            </td>
                        </template>
                    </tr>
                    @empty
                    <tr>
                        <td :colspan="slots.length + 3" style="padding:40px;text-align:center;color:#8aa89a;font-size:13px">
                            No students found matching "{{ $search }}"
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
        <div style="padding:12px 0;display:flex;justify-content:flex-end">
            {{ $students->links() }}
        </div>
        @endif
        @else
        {{-- Grouped by Year Level View (Collapsible) --}}
        @forelse($studentsByYear as $year => $yearStudents)
        <details style="margin-bottom:12px" open>
            <summary style="cursor:pointer;display:flex;align-items:center;gap:10px;padding:10px 14px;background:white;border:1px solid #dde8e1;border-radius:10px;list-style:none;font-weight:600;color:#0f1f17">
                <span style="background:#1a7a41;color:white;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:700">
                    {{ $year === 'N/A' ? 'Unknown' : $year . 'st Year' }}
                </span>
                <span style="font-size:12px;color:#8aa89a">{{ $yearStudents->count() }} students</span>
                <span style="margin-left:auto;color:#8aa89a">▼</span>
            </summary>
            <div style="margin-top:8px;overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;min-width:500px">
                    <thead>
                        <tr style="background:#f0f3f1;border-bottom:1px solid #dde8e1">
                            <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase">Student</th>
                            <template x-for="slot in slots" :key="slot">
                                <th style="padding:9px 13px;text-align:center;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase" x-text="slotLabel(slot)"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($yearStudents as $student)
                        <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                            <td style="padding:9px 13px">
                                <div style="font-size:13px;font-weight:600;color:#0f1f17">{{ $student->full_name }}</div>
                                <div style="font-size:11px;color:#8aa89a">{{ $student->student_number }}</div>
                            </td>
                            <template x-for="slot in slots" :key="slot">
                                <td style="padding:9px 13px;text-align:center">
                                    <label style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer"
                                           :style="canEdit ? '' : 'cursor:default'">
                                        <input type="checkbox"
                                            :checked="isPresent({{ $student->id }}, slot)"
                                            :disabled="!canEdit || isPending({{ $student->id }}, slot)"
                                            @change="toggleSlot({{ $student->id }}, slot)"
                                            style="width:18px;height:18px;accent-color:#1a7a41;cursor:inherit" />
                                    </label>
                                </td>
                            </template>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
        @empty
        <div style="padding:40px;text-align:center;color:#8aa89a;font-size:13px">
            No students enrolled in this program for the active semester.
        </div>
        @endforelse
        @endif

        @if(auth()->user()->hasRole('SECRETARY') && $event->status === 'DRAFT')
        <div style="margin-top:16px;padding:16px 20px;background:white;border:1px solid #dde8e1;border-radius:10px">
            <div style="font-size:13px;color:#4a6356;margin-bottom:10px">
                Once submitted, the sheet will be locked for auditor review. You will not be able to make changes.
            </div>
            <form id="submit-form" method="POST" action="{{ route('org.attendance.submit', $event) }}">
                @csrf
                <button type="button" @click="confirmSubmit()"
                    style="padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;background:#1a7a41;color:white">
                    Submit for Auditor Review
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    const __attendanceData = @json($attendanceData);

    function attendanceSheet() {
        return {
            attendance: {},
            slots: [],
            canEdit: false,
            totalStudents: 0,
            pending: {},

            init() {
                this.attendance     = __attendanceData.attendance || {};
                this.slots          = @json($slots);
                this.canEdit        = __attendanceData.canEdit;
                this.totalStudents  = __attendanceData.totalStudents;
            },

            isPresent(studentId, slot) {
                return !!(this.attendance[studentId] && this.attendance[studentId][slot]);
            },

            isPending(studentId, slot) {
                return !!this.pending[`${studentId}-${slot}`];
            },

            get hasPendingRequests() {
                return Object.keys(this.pending).length > 0;
            },

            presentCount(slot) {
                return Object.values(this.attendance).filter(s => s && s[slot]).length;
            },

            slotLabel(slot) {
                return {
                    MORNING_IN:    'AM In',
                    MORNING_OUT:   'AM Out',
                    AFTERNOON_IN:  'PM In',
                    AFTERNOON_OUT: 'PM Out',
                }[slot] || slot;
            },

            async toggleSlot(studentId, slot) {
                const key = `${studentId}-${slot}`;
                if (this.pending[key]) return;
                this.pending[key] = true;

                if (!this.attendance[studentId]) {
                    this.attendance[studentId] = {};
                }

                const prev = !!this.attendance[studentId][slot];
                this.attendance[studentId][slot] = !prev;

                try {
                    const url = __attendanceData.toggleBaseUrl
                        .replace('__STUDENT__', studentId)
                        .replace('__SLOT__', slot);

                    const resp = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    });

                    if (!resp.ok) throw new Error('Server error ' + resp.status);
                    const data = await resp.json();
                    this.attendance[studentId][slot] = data.is_present;
                } catch (err) {
                    this.attendance[studentId][slot] = prev;
                    alert('Failed to save attendance. Please check your connection and try again.');
                } finally {
                    delete this.pending[key];
                }
            },

            confirmSubmit() {
                if (confirm('Submit this attendance sheet for auditor review?\n\nOnce submitted, you will not be able to make any changes.')) {
                    document.getElementById('submit-form').submit();
                }
            },
        };
    }
</script>
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
@endsection