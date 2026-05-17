@extends('layouts.app')
@section('title', 'Enrolled Students')
@section('page-title', 'Enrolled Students')

@section('content')
<div class="page-shell" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Enrolled Students</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">Scoped to {{ auth()->user()->organization?->name ?? 'your organization' }} · Active Semester: {{ $activeSemester?->name ?? 'N/A' }}</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('org.students.index', ['export' => 'csv']) }}" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>Export Status
            </a>
            @if(auth()->user()->canEnrollStudents())
            <button @click="open = 'bulk'" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>Bulk Import
            </button>
            <button @click="open = 'manual'" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                </svg>Add Student
            </button>
            @endif
        </div>
    </div>

    {{-- ── Context-Aware Cascading Filter Bar (FR-0014) ──────────────────────── --}}
    @if($orgType === 'COLLEGE_COUNCIL' || $orgType === 'CLASS_ORG')
    <div x-data="cascadeFilter()" x-init="init()"
         class="bg-white rounded-xl border border-green-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaf0ec] flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm2 4h12M7 12h10M9 16h6"/></svg>
            <span class="text-[13px] font-bold text-green-700">Filter by Hierarchy</span>
            @if($hasFilters)
            <a href="{{ route('org.students.index') }}" class="ml-auto text-[12px] font-semibold text-red-400 hover:text-red-600 flex items-center gap-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Clear Filters
            </a>
            @endif
        </div>
        <form method="GET" action="{{ route('org.students.index') }}"
              class="px-5 py-4 grid gap-3
                     {{ $orgType === 'CLASS_ORG' ? 'grid-cols-2' : 'grid-cols-2 md:grid-cols-3' }}">
            {{-- Preserve search, status, sort params --}}
            @foreach(request()->only(['search','status','sort_dept','sort_program','sort_year']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach

            {{-- Department — COLLEGE_COUNCIL only (pre-filtered to the org's college) --}}
            @if($orgType === 'COLLEGE_COUNCIL')
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Department</label>
                <select name="filter_dept" x-model="selectedDept" @change="onDeptChange(); $el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                    <option value="">All Departments</option>
                    <template x-for="dept in allDepts" :key="dept.id">
                        <option :value="dept.id" :selected="dept.id == {{ request('filter_dept', 0) }}" x-text="`${dept.code} — ${dept.name}`"></option>
                    </template>
                </select>
            </div>
            @endif

            {{-- Program (cascades from Department for COLLEGE_COUNCIL; flat list for CLASS_ORG) --}}
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Program</label>
                <select name="filter_program" x-model="selectedProgram" @change="$el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none"
                    @if($orgType === 'COLLEGE_COUNCIL') :disabled="filteredPrograms.length === 0" @endif>
                    <option value="">All Programs</option>
                    <template x-for="prog in filteredPrograms" :key="prog.id">
                        <option :value="prog.id" :selected="prog.id == {{ request('filter_program', 0) }}" x-text="`${prog.code} — ${prog.name}`"></option>
                    </template>
                </select>
            </div>

            {{-- Year Level --}}
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Year Level</label>
                <select name="filter_year" @change="$el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                    <option value="">All Years</option>
                    @foreach(range(1,5) as $yr)
                        <option value="{{ $yr }}" {{ request('filter_year') == $yr ? 'selected' : '' }}>Year {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">Student Records</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $students->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('org.students.index') }}" class="flex flex-wrap items-center gap-2.5">
                {{-- Preserve active filter params when searching --}}
                @foreach(request()->only(['filter_dept','filter_program','filter_year','sort_dept','sort_program','sort_year']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative w-full md:w-[240px]">
                    <svg class="w-4 h-4 text-green-300 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by name or ID..."
                        class="w-full pl-9 pr-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                </div>
                <select name="status" onchange="this.form.submit()" class="border-2 border-green-200 rounded-lg py-2 px-3 text-[13px] font-medium text-green-400 outline-none focus:border-green-600 bg-white cursor-pointer transition-colors">
                    <option value="">All Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Student ID</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">
                            <a href="{{ route('org.students.index', array_merge(request()->except('sort_program'), ['sort_program' => request('sort_program') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-green-600 transition-colors">
                                Program
                                @if(request('sort_program') === 'asc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 15l5-5 5 5H5z"/></svg>
                                @elseif(request('sort_program') === 'desc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M15 5l-5 5-5-5h10z"/></svg>
                                @else <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5-5 5 5H5zm10 4l-5 5-5-5h10z"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">
                            <a href="{{ route('org.students.index', array_merge(request()->except('sort_year'), ['sort_year' => request('sort_year') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-green-600 transition-colors">
                                Year
                                @if(request('sort_year') === 'asc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 15l5-5 5 5H5z"/></svg>
                                @elseif(request('sort_year') === 'desc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M15 5l-5 5-5-5h10z"/></svg>
                                @else <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5-5 5 5H5zm10 4l-5 5-5-5h10z"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Payment Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">{{ $student->student_number }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-green-800">{{ $student->full_name }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $student->latestEnrollment?->program?->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $student->latestEnrollment?->year_level ? 'Year ' . $student->latestEnrollment->year_level : '—' }}</td>
                        <td class="px-6 py-4">
                            @if($student->hasPaidThisSemester ?? false)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Paid</span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#eab308]"></span> Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if(auth()->user()->canCreateTransactions())
                                <a href="{{ route('org.transactions.create', ['student' => $student->student_number]) }}"
                                    title="Create Transaction"
                                    class="p-1.5 rounded-lg text-green-300 hover:bg-green-100 hover:text-green-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </a>
                                @endif
                                @if(auth()->user()->canEnrollStudents())
                                <a href="{{ route('org.students.edit', $student) }}" class="p-1.5 rounded-lg text-green-300 hover:bg-green-200 hover:text-green-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">No students found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-green-50">
            <span class="text-[12.5px] font-medium text-green-300">Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}</span>
            {{ $students->withQueryString()->links() }}
        </div>
    </div>

    {{-- Enroll Student Modal --}}
    <div x-show="open === 'manual'" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-xl w-full max-w-2xl shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div><h2 class="text-[18px] font-bold text-green-800">Enroll New Student</h2><p class="text-[13px] text-green-400 mt-0.5 font-medium">Add a student to the active semester.</p></div>
                <button @click="open = false" class="text-green-300 hover:bg-[#f0f3f1] p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form method="POST" action="{{ route('org.students.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-6">
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Student Identity</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Student ID Number <span class="text-red-500">*</span> <span class="text-[11px] font-normal text-green-300">(exactly 10 digits, no dashes)</span></label>
                            <input type="text" name="student_number" value="{{ old('student_number') }}" placeholder="e.g. 2024000001" maxlength="10" pattern="\d{10}"
                                class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-green-50 text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Dela Cruz" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Name Extension <span class="text-[11px] font-normal text-green-300">(Jr., Sr., III…)</span></label>
                                <input type="text" name="name_extension" value="{{ old('name_extension') }}" placeholder="e.g. Jr." class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Juan" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Middle Name <span class="text-[11px] font-normal text-green-300">(Optional)</span></label>
                                <input type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="e.g. Santos" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Email <span class="text-[11px] font-normal text-green-300">(Optional)</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. jdelacruz@cmu.edu.ph" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Enrollment Details</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Active Semester</label>
                            <input type="text" value="{{ $activeSemester?->name ?? 'No active semester' }}" readonly class="w-full px-4 py-2.5 border-2 border-[#eaf0ec] rounded-lg bg-[#f0f3f1] text-[14px] font-semibold text-green-400 outline-none cursor-not-allowed">
                        </div>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Program <span class="text-red-500">*</span></label>
                            <select name="program_id" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                <option value="">— Select program —</option>
                                @foreach($programs as $p)<option value="{{ $p->id }}" {{ old('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>@endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Year Level <span class="text-red-500">*</span></label>
                                <select name="year_level" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                    @foreach(range(1,5) as $yr)<option value="{{ $yr }}" {{ old('year_level') == $yr ? 'selected' : '' }}>Year {{ $yr }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Student Type <span class="text-red-500">*</span></label>
                                <select name="student_type" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                    <option value="REGULAR"   {{ old('student_type','REGULAR') === 'REGULAR'   ? 'selected' : '' }}>Regular</option>
                                    <option value="IRREGULAR" {{ old('student_type') === 'IRREGULAR' ? 'selected' : '' }}>Irregular</option>
                                    <option value="EXTENDEE"  {{ old('student_type') === 'EXTENDEE'  ? 'selected' : '' }}>Extendee</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-green-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Import Modal --}}
    <div x-show="open === 'bulk'" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false; $dispatch('bulk-clear')"></div>
        <div class="relative bg-white rounded-xl w-full max-w-lg shadow-2xl z-10"
             x-data="bulkImportUploader()"
             @bulk-clear.window="clear()">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec]">
                <div>
                    <h2 class="text-[18px] font-bold text-green-800">Bulk Import Students</h2>
                    <p class="text-[13px] text-green-400 mt-0.5">Drag & drop a CSV file or click to browse.</p>
                </div>
                <button type="button" @click="open = false; clear()" class="text-green-300 hover:bg-[#f0f3f1] p-2 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Column guide --}}
            <div class="px-6 pt-4 pb-2">
                <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-[12.5px] text-blue-700">
                        <p class="font-bold mb-1">Required columns (in any order):</p>
                        <p class="font-mono text-[11px] leading-relaxed break-all">student_id_number, last_name, first_name, program, year_level, student_type</p>
                        <p class="mt-1">Optional: name_extension, middle_name, email, department</p>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('org.students.import.store') }}" enctype="multipart/form-data" class="px-6 pb-6 pt-4">
                @csrf

                {{-- Drop Zone --}}
                <div
                    class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all duration-150 cursor-pointer select-none"
                    :class="dragging ? 'border-green-500 bg-green-100' : (file ? 'border-green-400 bg-green-50' : 'border-green-200 bg-green-50 hover:border-green-400 hover:bg-green-100')"
                    @click="openPicker()"
                    @dragover.prevent="dragging = true"
                    @dragenter.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="handleDrop($event)"
                >
                    <input x-ref="fileInput" type="file" name="file" accept=".csv,.txt"
                           class="sr-only" required @change="handleChange($event)">

                    {{-- Idle / drag-over --}}
                    <div x-show="!file" class="flex flex-col items-center gap-3 pointer-events-none">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
                             :class="dragging ? 'bg-green-200' : 'bg-green-100'">
                            <svg class="w-7 h-7 text-green-600 transition-transform"
                                 :class="dragging ? 'scale-110' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[14px] font-bold text-green-800"
                               x-text="dragging ? 'Drop it here!' : 'Drag & drop your CSV here'"></p>
                            <p class="text-[12px] text-green-400 mt-1">
                                or <span class="text-green-600 font-semibold underline">click to browse</span>
                                &nbsp;·&nbsp; .csv or .txt &nbsp;·&nbsp; max 10 MB
                            </p>
                        </div>
                    </div>

                    {{-- File selected --}}
                    <div x-show="file" class="flex items-center gap-4 pointer-events-none">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="text-left min-w-0 flex-1">
                            <p class="text-[13px] font-bold text-green-800 truncate" x-text="file?.name"></p>
                            <p class="text-[12px] text-green-400 mt-0.5" x-text="file ? formatSize(file.size) : ''"></p>
                        </div>
                        <button type="button" @click.stop="clear()"
                            class="pointer-events-auto p-1.5 rounded-lg text-green-300 hover:bg-red-50 hover:text-red-500 transition-colors" title="Remove file">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- File error --}}
                <p x-show="error" x-text="error" x-cloak class="mt-2 text-[12px] font-semibold text-red-500"></p>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 mt-5">
                    <button type="button" @click="open = false; clear()"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        :disabled="!file || !!error"
                        :class="(!file || !!error) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-500'"
                        class="px-5 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 text-white transition-all shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Process Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bulkImportUploader() {
    return {
        dragging: false,
        file: null,
        error: null,

        handleDrop(e) {
            this.dragging = false;
            const f = e.dataTransfer?.files[0];
            if (f) this.setFile(f, e.dataTransfer);
        },

        handleChange(e) {
            const f = e.target.files[0];
            if (f) this.setFile(f, null);
        },

        setFile(f, dt) {
            const ext = f.name.split('.').pop().toLowerCase();
            if (!['csv', 'txt'].includes(ext)) {
                this.error = 'Only .csv or .txt files are accepted.';
                this.file = null;
                this.$refs.fileInput.value = '';
                return;
            }
            if (f.size > 10 * 1024 * 1024) {
                this.error = 'File size exceeds the 10 MB limit.';
                this.file = null;
                this.$refs.fileInput.value = '';
                return;
            }
            this.error = null;
            this.file = { name: f.name, size: f.size };

            if (dt) {
                const transfer = new DataTransfer();
                transfer.items.add(f);
                this.$refs.fileInput.files = transfer.files;
            }
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        clear() {
            this.file = null;
            this.error = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        openPicker() {
            this.$refs.fileInput.click();
        }
    };
}

function cascadeFilter() {
    return {
        allDepts:        @json($allDepartments),
        allProgs:        @json($allPrograms),
        selectedDept:    '{{ request('filter_dept', '') }}',
        selectedProgram: '{{ request('filter_program', '') }}',
        filteredPrograms: [],

        init() {
            // On COLLEGE_COUNCIL: cascade programs from selected dept; fall back to all scoped programs
            // On CLASS_ORG: allDepts is empty, allProgs is the full dept-scoped list
            this.filteredPrograms = this.selectedDept
                ? this.allProgs.filter(p => String(p.department_id) === String(this.selectedDept))
                : this.allProgs;
        },

        onDeptChange() {
            this.filteredPrograms = this.selectedDept
                ? this.allProgs.filter(p => String(p.department_id) === String(this.selectedDept))
                : this.allProgs;
            this.selectedProgram = '';
        },
    };
}
</script>
@endpush
