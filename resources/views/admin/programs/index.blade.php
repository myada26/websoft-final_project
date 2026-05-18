@extends('layouts.app')
@section('title', 'Manage Programs')
@section('page-title', 'Manage Programs')

@section('content')
<div class="page-shell" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Manage Programs</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">Programs belong to departments (Level 3 — Membership scope)</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <button @click="open = 'manual'" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add Program
            </button>
        </div>
    </div>

    @php $hasFilters = request()->hasAny(['filter_college','filter_dept','search']); @endphp

    {{-- Cascading hierarchy filter: College → Department → Program list --}}
    <div x-data="programCascadeFilter()" x-init="init()"
         class="bg-white rounded-xl border border-green-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaf0ec] flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm2 4h12M7 12h10M9 16h6"/></svg>
            <span class="text-[13px] font-bold text-green-700">Filter by Hierarchy</span>
            @if($hasFilters)
            <a href="{{ route('admin.programs.index') }}" class="ml-auto text-[12px] font-semibold text-red-400 hover:text-red-600 flex items-center gap-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Clear Filters
            </a>
            @endif
        </div>
        <form method="GET" action="{{ route('admin.programs.index') }}" class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach(request()->only(['search']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach

            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Filter by College</label>
                <select name="filter_college" x-model="selectedCollege" @change="onCollegeChange(); $el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                    <option value="">All Colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ request('filter_college') == $college->id ? 'selected' : '' }}>
                            {{ $college->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Filter by Department</label>
                <select name="filter_dept" x-model="selectedDept" @change="$el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none disabled:bg-[#f0f3f1] disabled:cursor-not-allowed"
                    :disabled="!selectedCollege">
                    <option value="">All Departments</option>
                    <template x-for="dept in filteredDepts" :key="dept.id">
                        <option :value="dept.id" :selected="dept.id == {{ request('filter_dept', 0) }}" x-text="dept.name"></option>
                    </template>
                </select>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">All Programs</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $programs->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('admin.programs.index') }}" class="flex flex-wrap items-center gap-2.5">
                @foreach(request()->only(['filter_college','filter_dept']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative w-full md:w-[220px]">
                    <svg class="w-4 h-4 text-green-300 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search programs..."
                           class="w-full pl-9 pr-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Code</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Program Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Department</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Students</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programs as $prog)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">{{ $prog->code }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-green-800">{{ $prog->name }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $prog->department->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $prog->enrollments_count ?? '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.programs.edit', $prog) }}" class="p-1.5 rounded-lg text-green-300 hover:bg-green-200 hover:text-green-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.programs.destroy', $prog) }}" onsubmit="return confirm('Delete {{ addslashes($prog->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-green-300 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">No programs found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-green-50">
            <span class="text-[12.5px] font-medium text-green-300">Showing {{ $programs->firstItem() ?? 0 }}–{{ $programs->lastItem() ?? 0 }} of {{ $programs->total() }}</span>
            {{ $programs->withQueryString()->links() }}
        </div>
    </div>

    {{-- Add Program Modal --}}
    <div x-show="open === 'manual'" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-xl w-full max-w-lg shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div><h2 class="text-[18px] font-bold text-green-800">Add New Program</h2><p class="text-[13px] text-green-400 mt-0.5 font-medium">Create a program within a department.</p></div>
                <button @click="open = false" class="text-green-300 hover:bg-[#f0f3f1] p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form method="POST" action="{{ route('admin.programs.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">Program Code <span class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. BSCS" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-green-50 text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors uppercase">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">Program Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. BS Computer Science" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">Parent Department <span class="text-red-500">*</span></label>
                        <select name="department_id" class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                            <option value="">— Select a department —</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }} ({{ $d->code }})</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-green-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">Save Program</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function programCascadeFilter() {
        return {
            allDepts:        @json($allDepartments),
            selectedCollege: '{{ request('filter_college', '') }}',
            selectedDept:    '{{ request('filter_dept', '') }}',
            filteredDepts:   [],

            init() {
                this.filteredDepts = this.selectedCollege
                    ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                    : [];
            },

            onCollegeChange() {
                this.filteredDepts = this.selectedCollege
                    ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                    : [];
                this.selectedDept = '';
            },
        };
    }
</script>
@endpush
@endsection
