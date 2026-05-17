@extends('layouts.app')
@section('title', 'Attendance – ' . $event->name)
@section('page-title', 'Attendance Sheet')

@section('content')
@php
    $user     = auth()->user();
    $isSecretary = $user->hasRole('SECRETARY');
    $isAuditor   = $user->hasRole('AUDITOR');
    $canEdit     = $attendanceData['canEdit'];
    $slotLabels  = [
        'MORNING_IN'    => 'AM In',
        'MORNING_OUT'   => 'AM Out',
        'AFTERNOON_IN'  => 'PM In',
        'AFTERNOON_OUT' => 'PM Out',
    ];
@endphp

{{-- ── Header banner ─────────────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#00491e 0%,#1a7a41 100%);border-radius:12px;padding:20px 24px;margin-bottom:16px;color:white">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <a href="{{ route('org.events.show', $event) }}"
               style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:rgba(255,255,255,.75);text-decoration:none;margin-bottom:6px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Event
            </a>
            <div style="font-size:20px;font-weight:700;letter-spacing:-.01em">{{ $event->name }}</div>
            <div style="font-size:12.5px;color:rgba(255,255,255,.8);margin-top:3px">
                {{ $event->date->format('F d, Y') }}
                @if($event->venue) &middot; {{ $event->venue }} @endif
                &middot; {{ $event->time_type === 'FULL_DAY' ? 'Full Day · 4 slots' : 'Half Day · 2 slots' }}
            </div>
            <div style="font-size:11.5px;color:rgba(255,255,255,.65);margin-top:2px">
                {{ $attendanceData['programName'] }} &middot; {{ $attendanceData['totalStudents'] }} enrolled
            </div>
        </div>
        <div>
            @if($isSecretary && $event->status === 'DRAFT')
            <span style="display:inline-block;padding:5px 13px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(255,193,7,.2);border:1.5px solid rgba(255,193,7,.5);color:#ffd966">
                ✏ Secretary — Editing
            </span>
            @elseif($isAuditor && $event->status === 'PENDING_APPROVAL')
            <span style="display:inline-block;padding:5px 13px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(59,130,246,.2);border:1.5px solid rgba(59,130,246,.5);color:#93c5fd">
                🔍 Auditor — Review Mode
            </span>
            @else
            <span style="display:inline-block;padding:5px 13px;border-radius:20px;font-size:12px;font-weight:700;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);color:white">
                👁 Read-Only
            </span>
            @endif
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#15803d;font-weight:600">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#dc2626;font-weight:600">
    {{ session('error') }}
</div>
@endif

<div x-data="attendanceSheet()" x-init="init()">

    {{-- ── Attendance counter bar ─────────────────────────────────────────── --}}
    <div style="background:white;border:1px solid #dde8e1;border-radius:10px;padding:12px 18px;margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="font-size:12px;font-weight:700;color:#4a6356;text-transform:uppercase;letter-spacing:.05em">
                Live Count
            </div>
            <template x-for="slot in slots" :key="slot">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:8px;height:8px;border-radius:50%;background:#1a7a41"></div>
                    <span style="font-size:11px;font-weight:600;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em" x-text="slotLabel(slot)"></span>
                    <span style="font-size:14px;font-weight:700;color:#0f1f17" x-text="presentCount(slot)"></span>
                    <span style="font-size:11px;color:#8aa89a" x-text="'/ ' + totalStudents"></span>
                </div>
            </template>
            <div style="margin-left:auto;font-size:11.5px;color:#8aa89a;display:flex;align-items:center;gap:5px" x-show="hasPendingRequests">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                Saving…
            </div>
        </div>
    </div>

    {{-- ── Search bar ─────────────────────────────────────────────────────── --}}
    <div style="background:white;border:1px solid #dde8e1;border-radius:10px;padding:12px 18px;margin-bottom:12px">
        <form method="GET" action="{{ route('org.attendance.sheet', $event) }}" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="search" value="{{ $search ?? '' }}"
                placeholder="Search by name or student number…"
                style="flex:1;padding:8px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13px;color:#0f1f17;outline:none"
                onfocus="this.style.borderColor='#1a7a41'" onblur="this.style.borderColor='#dde8e1'">
            <button type="submit"
                style="padding:8px 16px;background:#1a7a41;color:white;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer">
                Search
            </button>
            @if($search)
            <a href="{{ route('org.attendance.sheet', $event) }}"
               style="padding:8px 12px;background:#f0f3f1;color:#4a6356;border-radius:8px;font-size:13px;text-decoration:none">
                Clear
            </a>
            @endif
        </form>
    </div>

    {{-- ── Attendance table ────────────────────────────────────────────────── --}}
    @if($search)
    {{-- Search Results --}}
    <div style="background:white;border:1px solid #dde8e1;border-radius:10px;overflow:hidden;margin-bottom:12px">
        <div style="padding:10px 16px;background:#f0f3f1;border-bottom:1px solid #dde8e1;font-size:12px;color:#4a6356;font-weight:700">
            Search Results — {{ $students->total() }} student(s) found
        </div>
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:480px">
            <thead>
                <tr style="background:#00491e">
                    <th style="padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em">Student</th>
                    <th style="padding:9px 14px;text-align:center;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em">Year</th>
                    <template x-for="slot in slots" :key="slot">
                        <th style="padding:9px 14px;text-align:center;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em" x-text="slotLabel(slot)"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $enrollment = \App\Models\StudentEnrollment::where('student_id', $student->id)
                        ->where('academic_year_id', $event->academic_year_id)
                        ->first();
                    $yr = $enrollment?->year_level;
                    $suffix = $yr ? match((int)$yr) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } : '';
                @endphp
                <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                    <td style="padding:9px 14px">
                        <div style="font-size:13px;font-weight:600;color:#0f1f17">{{ $student->full_name }}</div>
                        <div style="font-size:11px;color:#8aa89a;margin-top:1px">{{ $student->student_number }}</div>
                    </td>
                    <td style="padding:9px 14px;text-align:center;font-size:12px;color:#4a6356;font-weight:600">
                        {{ $yr ? $yr . $suffix : '—' }}
                    </td>
                    <template x-for="slot in slots" :key="slot">
                        <td style="padding:9px 14px;text-align:center">
                            <label :style="canEdit ? 'cursor:pointer' : 'cursor:default'">
                                <input type="checkbox"
                                    :checked="isPresent({{ $student->id }}, slot)"
                                    :disabled="!canEdit || isPending({{ $student->id }}, slot)"
                                    @click.prevent="if (canEdit && !isPending({{ $student->id }}, slot)) toggleSlot({{ $student->id }}, slot)"
                                    style="width:18px;height:18px;accent-color:#1a7a41;cursor:inherit" />
                            </label>
                        </td>
                    </template>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="padding:40px;text-align:center;color:#8aa89a;font-size:13px">
                        No students found matching "{{ $search }}"
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($students->hasPages())
    <div style="padding:8px 0;display:flex;justify-content:flex-end">
        {{ $students->links() }}
    </div>
    @endif

    @else
    {{-- Grouped by Year Level --}}
    @forelse($studentsByYear as $year => $yearStudents)
    @php
        $yrInt  = (int) $year;
        $suffix = match($yrInt) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
        $yearLabel = ($year === 'N/A' || $yrInt === 0) ? 'Unknown Year' : $yrInt . $suffix . ' Year';
    @endphp
    <details style="margin-bottom:10px" open>
        <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;gap:10px;padding:10px 16px;background:white;border:1px solid #dde8e1;border-radius:10px;font-weight:600;color:#0f1f17;user-select:none">
            <span style="background:#00491e;color:white;padding:3px 11px;border-radius:12px;font-size:12px;font-weight:700">
                {{ $yearLabel }}
            </span>
            <span style="font-size:12px;color:#8aa89a;font-weight:400">{{ $yearStudents->count() }} students</span>
            <span style="margin-left:auto;font-size:11px;color:#8aa89a">▼</span>
        </summary>
        <div style="margin-top:6px;background:white;border:1px solid #dde8e1;border-radius:10px;overflow:hidden">
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:420px">
                <thead>
                    <tr style="background:#00491e">
                        <th style="padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em">Student</th>
                        <th style="padding:9px 14px;text-align:left;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em">No.</th>
                        <template x-for="slot in slots" :key="slot">
                            <th style="padding:9px 14px;text-align:center;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.05em" x-text="slotLabel(slot)"></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    @foreach($yearStudents as $student)
                    <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                        <td style="padding:9px 14px">
                            <div style="font-size:13px;font-weight:600;color:#0f1f17">{{ $student->full_name }}</div>
                        </td>
                        <td style="padding:9px 14px;font-size:11.5px;color:#8aa89a;white-space:nowrap">{{ $student->student_number }}</td>
                        <template x-for="slot in slots" :key="slot">
                            <td style="padding:9px 14px;text-align:center">
                                <label :style="canEdit ? 'cursor:pointer' : 'cursor:default'">
                                    <input type="checkbox"
                                        :checked="isPresent({{ $student->id }}, slot)"
                                        :disabled="!canEdit || isPending({{ $student->id }}, slot)"
                                        x-on:change="toggleSlot({{ $student->id }}, slot)"
                                        style="width:18px;height:18px;accent-color:#1a7a41;cursor:inherit" />
                                </label>
                            </td>
                        </template>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </details>
    @empty
    <div style="padding:44px;text-align:center;color:#8aa89a;font-size:13px;background:white;border:1px solid #dde8e1;border-radius:10px">
        <div style="font-size:14px;font-weight:600;color:#4a6356;margin-bottom:4px">No students found</div>
        <div>No enrolled students were found for this program in the active semester.</div>
    </div>
    @endforelse
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Secretary — Submit panel                                              --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isSecretary && $event->status === 'DRAFT')
    <div style="margin-top:16px;background:white;border:1.5px solid #dde8e1;border-radius:12px;padding:20px 24px">
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px">
            <div style="width:36px;height:36px;border-radius:50%;background:#fef9ec;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <div style="font-size:13.5px;font-weight:700;color:#0f1f17;margin-bottom:3px">Ready to submit?</div>
                <div style="font-size:12.5px;color:#4a6356;line-height:1.5">
                    Once submitted, the sheet is <strong>locked</strong> and forwarded to the Auditor for review.
                    Fines are <strong>not applied yet</strong> — the Auditor must approve first.
                </div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            <button type="button" @click="saveDraft()"
                :disabled="isSaving"
                style="padding:10px 20px;border-radius:8px;font-size:13.5px;font-weight:700;border:2px solid #1a7a41;color:#1a7a41;background:white;cursor:pointer;letter-spacing:.01em"
                x-text="isSaving ? 'Saving…' : 'Save as Draft'">
            </button>
            <form id="submit-form" method="POST" action="{{ route('org.attendance.submit', $event) }}">
                @csrf
                <button type="button" @click="confirmSubmit()"
                    style="padding:10px 22px;border-radius:8px;font-size:13.5px;font-weight:700;border:none;cursor:pointer;background:#00491e;color:white;letter-spacing:.01em">
                    Submit Attendance for Auditor Review
                </button>
            </form>
            <span x-show="savedAt" x-text="'Last saved at ' + savedAt"
                  style="font-size:12px;color:#4a6356;font-weight:500"></span>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Auditor — Review panel                                                --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if($isAuditor && $event->status === 'PENDING_APPROVAL')
    <div style="margin-top:16px;background:white;border:2px solid #3b82f6;border-radius:12px;overflow:hidden">
        {{-- Header --}}
        <div style="background:#eff6ff;padding:14px 20px;border-bottom:1px solid #bfdbfe">
            <div style="font-size:14px;font-weight:700;color:#1d4ed8;margin-bottom:2px">Auditor Review</div>
            <div style="font-size:12.5px;color:#3b82f6">
                Review the attendance sheet above. You may correct any checkbox before approving.
                Fines (₱10.00/missed slot) are posted <strong>only upon approval</strong>.
            </div>
        </div>

        <div style="padding:18px 20px">
            {{-- Attendance summary for auditor --}}
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
                @foreach($slots as $slot)
                @php $slotLabel = $slotLabels[$slot] ?? $slot; @endphp
                <div style="background:#f8fbf9;border:1px solid #dde8e1;border-radius:8px;padding:10px 16px;text-align:center;min-width:72px">
                    <div style="font-size:10px;color:#8aa89a;text-transform:uppercase;font-weight:600;letter-spacing:.04em">{{ $slotLabel }}</div>
                    <div style="font-size:18px;font-weight:700;color:#00491e;margin-top:2px" x-text="presentCount('{{ $slot }}')"></div>
                    <div style="font-size:11px;color:#8aa89a">/ {{ $attendanceData['totalStudents'] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Action buttons --}}
            <div style="display:flex;flex-wrap:wrap;gap:10px">
                {{-- Approve --}}
                <form method="POST" action="{{ route('org.attendance.auditor-approve', $event) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                        onclick="return confirm('Approve this attendance sheet?\n\nThis will:\n✓ Lock the sheet permanently\n✓ Compute ₱10.00 fines for each missed slot\n\nThis cannot be undone.')"
                        style="padding:10px 22px;border-radius:8px;font-size:13.5px;font-weight:700;border:none;cursor:pointer;background:#00491e;color:white;letter-spacing:.01em">
                        ✓ Approve &amp; Compute Fines
                    </button>
                </form>

                {{-- Forward to Chairperson --}}
                <form method="POST" action="{{ route('org.attendance.auditor-forward', $event) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                        onclick="return confirm('Forward to Chairperson for final confirmation?\n\nThe Chairperson will review your edits before fines are computed.')"
                        style="padding:10px 22px;border-radius:8px;font-size:13.5px;font-weight:700;border:2px solid #3b82f6;color:#1d4ed8;background:white;cursor:pointer">
                        → Forward to Chairperson
                    </button>
                </form>

                {{-- Reject --}}
                <button type="button"
                    onclick="document.getElementById('auditor-reject-panel').style.display='block';this.style.display='none'"
                    style="padding:10px 22px;border-radius:8px;font-size:13.5px;font-weight:700;border:2px solid #dc2626;color:#dc2626;background:white;cursor:pointer">
                    ✕ Reject &amp; Return
                </button>
            </div>

            {{-- Reject inline form --}}
            <div id="auditor-reject-panel" style="display:none;margin-top:14px;background:#fff5f5;border:1px solid #fca5a5;border-radius:8px;padding:14px 18px">
                <div style="font-size:13px;font-weight:700;color:#dc2626;margin-bottom:8px">Return to Secretary — Reason Required</div>
                <form method="POST" action="{{ route('org.attendance.auditor-reject', $event) }}">
                    @csrf @method('PATCH')
                    <textarea name="rejection_reason" rows="3" required
                        placeholder="Describe what needs to be corrected before re-submission…"
                        style="width:100%;padding:8px 12px;border:1.5px solid #fca5a5;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;background:white;outline:none"></textarea>
                    <div style="display:flex;gap:8px;margin-top:10px">
                        <button type="submit"
                            style="padding:8px 16px;border-radius:7px;font-size:13px;font-weight:700;border:none;cursor:pointer;background:#dc2626;color:white">
                            Confirm Rejection
                        </button>
                        <button type="button"
                            onclick="document.getElementById('auditor-reject-panel').style.display='none';document.querySelector('[onclick*=reject-panel]').style.display=''"
                            style="padding:8px 14px;border-radius:7px;font-size:13px;border:1.5px solid #dde8e1;color:#4a6356;background:white;cursor:pointer">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>{{-- end x-data --}}

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
            isSaving: false,
            savedAt: null,

            init() {
                this.attendance    = __attendanceData.attendance || {};
                this.slots         = @json($slots);
                this.canEdit       = __attendanceData.canEdit;
                this.totalStudents = __attendanceData.totalStudents;
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
                return { MORNING_IN: 'AM In', MORNING_OUT: 'AM Out', AFTERNOON_IN: 'PM In', AFTERNOON_OUT: 'PM Out' }[slot] || slot;
            },

            async toggleSlot(studentId, slot) {
                const key = `${studentId}-${slot}`;
                if (this.pending[key]) return;
                this.pending[key] = true;

                if (!this.attendance[studentId]) this.attendance[studentId] = {};

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
                } catch {
                    this.attendance[studentId][slot] = prev;
                    alert('Could not save. Check your connection and try again.');
                } finally {
                    delete this.pending[key];
                }
            },

            async saveDraft() {
                if (this.isSaving) return;
                this.isSaving = true;
                try {
                    const resp = await fetch('{{ route('org.attendance.save-draft', $event) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                    });
                    if (!resp.ok) throw new Error('Server error ' + resp.status);
                    const data = await resp.json();
                    this.savedAt = data.at;
                } catch {
                    alert('Could not save draft. Check your connection and try again.');
                } finally {
                    this.isSaving = false;
                }
            },

            confirmSubmit() {
                if (confirm('Submit attendance for Auditor review?\n\nThe sheet will be locked. Fines are NOT applied yet — the Auditor must approve first.')) {
                    document.getElementById('submit-form').submit();
                }
            },
        };
    }
</script>
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    details summary::-webkit-details-marker { display: none; }
</style>
@endpush
@endsection
