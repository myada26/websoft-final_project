@extends('layouts.app')
@section('title', 'Enrolled Students')
@section('page-title', 'Enrolled Students')

@section('content')
<div class="max-w-6xl mx-auto pb-10" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Enrolled Students</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Scoped to {{ auth()->user()->organization?->name ?? 'your organization' }} · Active Semester</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('org.students.index', ['export' => 'csv']) }}" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>Export Status
            </a>
            @if(auth()->user()->canEnrollStudents())
            <button @click="open = 'bulk'" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>Bulk Import
            </button>
            <button @click="open = 'manual'" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                </svg>Add Student
            </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">Student Records</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $students->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('org.students.index') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative w-full md:w-[240px]">
                    <svg class="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by name or ID..."
                        class="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                </div>
                <select name="status" onchange="this.form.submit()" class="border-2 border-[#dde8e1] rounded-xl py-2 px-3 text-[13px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] bg-white cursor-pointer transition-colors">
                    <option value="">All Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Student ID</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Program</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Membership</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Payment Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $student->student_number }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-[#0f1f17]">{{ $student->full_name }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $student->latestEnrollment?->program?->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $student->membership_type ?? 'Regular' }}</td>
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
                                    class="p-1.5 rounded-lg text-[#8aa89a] hover:bg-[#e6f4ec] hover:text-[#1a7a41] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </a>
                                @endif
                                @if(auth()->user()->canEnrollStudents())
                                <a href="{{ route('org.students.edit', $student) }}" class="p-1.5 rounded-lg text-[#8aa89a] hover:bg-[#dde8e1] hover:text-[#1a7a41] transition-colors">
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
                        <td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No students found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}</span>
            {{ $students->withQueryString()->links() }}
        </div>
    </div>

    {{-- Enroll Student Modal (same form as admin) --}}
    <div x-show="open === 'manual'" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f1f17]">Enroll New Student</h2>
                    <p class="text-[13px] text-[#4a6356] mt-0.5 font-medium">Add a student to your organization's roster.</p>
                </div>
                <button @click="open = false" class="text-[#8aa89a] hover:bg-[#f0f3f1] p-2 rounded-xl transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form method="POST" action="{{ route('org.students.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-6">
                    <div>
                        <h3 class="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Student Identity</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Student Number <span class="text-red-500">*</span></label>
                            <input type="text" name="student_number" value="{{ old('student_number') }}" placeholder="e.g. 2024-0001" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Juan" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Dela Cruz" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-bold text-[#1a7a41] mb-4 pb-2 border-b border-[#dde8e1]">Enrollment Details</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Program <span class="text-red-500">*</span></label>
                            <select name="program_id" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                                <option value="">— Select program —</option>
                                @foreach($programs as $p)<option value="{{ $p->id }}" {{ old('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>@endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Year Level <span class="text-red-500">*</span></label>
                                <select name="year_level" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                                    @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $yr)<option>{{ $yr }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Student Type <span class="text-red-500">*</span></label>
                                <select name="student_type" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                                    <option>Regular</option>
                                    <option>Irregular</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Import Modal --}}
    <div x-show="open === 'bulk'" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl z-10">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec]">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f1f17]">Bulk Import Students</h2>
                    <p class="text-[13px] text-[#4a6356] mt-0.5">Upload a CSV or Excel file to import multiple students.</p>
                </div>
                <button @click="open = false" class="text-[#8aa89a] hover:bg-[#f0f3f1] p-2 rounded-xl transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form method="POST" action="{{ route('org.students.import.store') }}" enctype="multipart/form-data" class="p-6">
                @csrf
                <div class="flex flex-col items-center border-2 border-dashed border-[#dde8e1] rounded-xl bg-[#f8fbf9] p-8 text-center mb-5">
                    <svg class="w-12 h-12 text-[#1a7a41] mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <p class="text-[13px] text-[#4a6356] mb-4">Supported: .csv, .xlsx, .xls (max 5MB)</p>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" class="text-[13px] text-[#4a6356]">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-[#1a7a41] hover:bg-[#27a05a] text-white transition-all shadow-sm">Process Upload</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
