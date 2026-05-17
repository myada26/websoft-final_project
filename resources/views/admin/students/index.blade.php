@extends('layouts.app')
@section('title', 'Enrolled Students')
@section('page-title', 'Enrolled Students')

@section('content')
@php
    $hasFilters = request()->hasAny(['filter_college','filter_dept','filter_program','filter_year','search']);
@endphp
<div class="page-shell"
     x-data="{ open: false }"
     @close-bulk-modal.window="open = false">

    {{-- Import processing card --}}
    <div x-data="importStatusCard()" x-init="init()"
         x-show="visible" x-cloak
         class="mb-5 rounded-xl border shadow-sm overflow-hidden"
         :class="done ? (failures > 0 ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50') : 'border-blue-200 bg-blue-50'">
        <div class="flex items-center gap-3 px-5 py-3.5">
            {{-- spinner or check --}}
            <template x-if="!done">
                <svg class="w-5 h-5 text-blue-500 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </template>
            <template x-if="done && failures === 0">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </template>
            <template x-if="done && failures > 0">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </template>

            <div class="flex-1 min-w-0">
                <p class="text-[13.5px] font-bold"
                   :class="done ? (failures > 0 ? 'text-amber-800' : 'text-green-800') : 'text-blue-800'"
                   x-text="done
                       ? (failures > 0
                           ? `Import done — ${rows} rows processed, ${failures} failed`
                           : `Import complete — ${rows} rows imported successfully`)
                       : 'Importing students… this may take a minute'">
                </p>
                <p x-show="done && failures > 0"
                   class="text-[12px] text-amber-600 mt-0.5">
                    Download the failure report to see which rows were skipped.
                </p>
            </div>

            <template x-if="done && downloadUrl">
                <a :href="downloadUrl"
                   class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold bg-amber-600 text-white hover:bg-amber-500 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Failure Report
                </a>
            </template>

            <button @click="dismiss()"
                    class="shrink-0 p-1 rounded text-current opacity-40 hover:opacity-100 transition-opacity ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Enrolled Students</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">University-Wide master list · Active Semester: {{ $activeSemester?->name ?? 'N/A' }}</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('admin.students.index', array_merge(request()->except('page'), ['export' => 'csv'])) }}" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export Roster
            </a>
            <button @click="open = 'bulk'" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Bulk Import
            </button>
            <button @click="open = 'manual'" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add Student
            </button>
        </div>
    </div>

    {{-- ── Cascading Filter Bar (FR-0014 · University-Wide scope) ─────────────── --}}
    <div x-data="cascadeFilter()" x-init="init()"
         class="bg-white rounded-xl border border-green-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaf0ec] flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm2 4h12M7 12h10M9 16h6"/></svg>
            <span class="text-[13px] font-bold text-green-700">Filter by Hierarchy</span>
            @if($hasFilters)
            <a href="{{ route('admin.students.index') }}" class="ml-auto text-[12px] font-semibold text-red-400 hover:text-red-600 flex items-center gap-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Clear Filters
            </a>
            @endif
        </div>
        <form method="GET" action="{{ route('admin.students.index') }}" class="px-5 py-4 grid grid-cols-2 md:grid-cols-4 gap-3">
            {{-- Preserve search and sort params --}}
            @foreach(request()->only(['search','sort_college','sort_dept','sort_program','sort_year']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach

            {{-- College --}}
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">College</label>
                <select name="filter_college" x-model="selectedCollege" @change="onCollegeChange(); $el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                    <option value="">All Colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ request('filter_college') == $college->id ? 'selected' : '' }}>
                            {{ $college->code }} — {{ $college->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Department (cascades from College) --}}
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Department</label>
                <select name="filter_dept" x-model="selectedDept" @change="onDeptChange(); $el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none"
                    :disabled="filteredDepts.length === 0">
                    <option value="">All Departments</option>
                    <template x-for="dept in filteredDepts" :key="dept.id">
                        <option :value="dept.id" :selected="dept.id == {{ request('filter_dept', 0) }}" x-text="`${dept.code} — ${dept.name}`"></option>
                    </template>
                </select>
            </div>

            {{-- Program (cascades from Department) --}}
            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Program</label>
                <select name="filter_program" x-model="selectedProgram" @change="$el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none"
                    :disabled="filteredPrograms.length === 0">
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

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">All Students</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $students->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('admin.students.index') }}" class="flex flex-wrap items-center gap-2.5">
                {{-- Preserve active filters when searching --}}
                @foreach(request()->only(['filter_college','filter_dept','filter_program','filter_year','sort_college','sort_dept','sort_program','sort_year']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative w-full md:w-[240px]">
                    <svg class="w-4 h-4 text-green-300 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search by name or ID..."
                           class="w-full pl-9 pr-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                </div>
                <button type="submit" class="px-3 py-2 border-2 border-green-200 rounded-lg text-green-400 hover:border-green-600 hover:text-green-600 transition-colors flex items-center gap-2 text-[13px] font-bold">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Student ID</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Name</th>
                        @php
                            $sortLink = fn(string $param, string $label) =>
                                view('components.sort-th', [
                                    'param'   => $param,
                                    'label'   => $label,
                                    'current' => request("sort_{$param}"),
                                    'route'   => route('admin.students.index', array_merge(request()->except("sort_{$param}"), ['sort_' . $param => request("sort_{$param}") === 'asc' ? 'desc' : 'asc'])),
                                ])->render();
                        @endphp
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">
                            <a href="{{ route('admin.students.index', array_merge(request()->except('sort_program'), ['sort_program' => request('sort_program') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-green-600 transition-colors">
                                Program
                                @if(request('sort_program') === 'asc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 15l5-5 5 5H5z"/></svg>
                                @elseif(request('sort_program') === 'desc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M15 5l-5 5-5-5h10z"/></svg>
                                @else <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5-5 5 5H5zm10 4l-5 5-5-5h10z"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">
                            <a href="{{ route('admin.students.index', array_merge(request()->except('sort_college'), ['sort_college' => request('sort_college') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-green-600 transition-colors">
                                College
                                @if(request('sort_college') === 'asc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 15l5-5 5 5H5z"/></svg>
                                @elseif(request('sort_college') === 'desc') <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M15 5l-5 5-5-5h10z"/></svg>
                                @else <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 20 20"><path d="M5 8l5-5 5 5H5zm10 4l-5 5-5-5h10z"/></svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">{{ $student->student_number }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-green-800">{{ $student->full_name }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $student->latestEnrollment?->program?->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $student->latestEnrollment?->program?->department?->college?->code ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Enrolled
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">No students found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-green-50">
            <span class="text-[12.5px] font-medium text-green-300">Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}</span>
            {{ $students->withQueryString()->links() }}
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
                        <p class="mt-1">Optional: name_extension, middle_name, email, college, department</p>
                        <a href="{{ route('admin.students.template') }}" class="inline-flex items-center gap-1 mt-1.5 font-bold text-blue-600 hover:text-blue-800 underline text-[12px]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download sample template
                        </a>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data"
                  class="px-6 pb-6 pt-4"
                  @submit.prevent="submitImport($el)">
                @csrf

                {{-- Drop Zone --}}
                <div
                    class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all duration-150 cursor-pointer select-none"
                    :class="{
                        'border-green-500 bg-green-100 scale-[1.01]': dragging,
                        'border-green-400 bg-green-50': !dragging && file,
                        'border-green-200 bg-green-50 hover:border-green-400 hover:bg-green-100': !dragging && !file
                    }"
                    @click="openPicker()"
                    @dragover.prevent="dragging = true"
                    @dragenter.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="handleDrop($event)"
                >
                    <input x-ref="fileInput" type="file" name="file" accept=".csv,.txt,.xlsx,.xls"
                           class="sr-only" required @change="handleChange($event)">

                    {{-- Idle / drag-over --}}
                    <div x-show="!file" class="flex flex-col items-center gap-3 pointer-events-none">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
                             :class="dragging ? 'bg-green-200' : 'bg-green-100'">
                            <svg class="w-7 h-7 text-green-600 transition-transform" :class="dragging ? 'scale-110' : ''"
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
                                &nbsp;·&nbsp; .csv, .txt, .xlsx, .xls &nbsp;·&nbsp; max 10 MB
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
                        <button type="button" @click.stop="clear()" class="pointer-events-auto p-1.5 rounded-lg text-green-300 hover:bg-red-50 hover:text-red-500 transition-colors" title="Remove file">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- File error --}}
                <p x-show="error" x-text="error" x-cloak
                   class="mt-2 text-[12px] font-semibold text-red-500 flex items-center gap-1">
                </p>

                {{-- Submit error --}}
                <p x-show="submitError" x-text="submitError" x-cloak
                   class="mt-2 text-[12px] font-semibold text-red-500"></p>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 mt-5">
                    <button type="button" @click="open = false; clear()"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        :disabled="!file || !!error || submitting"
                        :class="(!file || !!error || submitting) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-500'"
                        class="px-5 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 text-white transition-all shadow-sm flex items-center gap-2">
                        <template x-if="submitting">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </template>
                        <template x-if="!submitting">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </template>
                        <span x-text="submitting ? 'Uploading…' : 'Process Upload'"></span>
                    </button>
                </div>
            </form>
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
            <form method="POST" action="{{ route('admin.students.store') }}" class="flex flex-col min-h-0">
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

</div>
@endsection

@push('scripts')
<script>
function importStatusCard() {
    const STORAGE_KEY = 'fcats_admin_import';
    return {
        visible: false,
        done: false,
        rows: 0,
        failures: 0,
        downloadUrl: null,
        _importId: null,
        _timer: null,

        init() {
            window.addEventListener('import-queued', e => this.start(e.detail.id));
            const saved = sessionStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    const d = JSON.parse(saved);
                    if (d.id && !d.done) { this.start(d.id); }
                    else if (d.id && d.done) { this._applyDone(d); this.visible = true; }
                } catch {}
            }
        },

        start(id) {
            this._importId = id;
            this.visible = true;
            this.done = false;
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ id }));
            this._poll();
        },

        _poll() {
            clearInterval(this._timer);
            this._timer = setInterval(async () => {
                try {
                    const r = await fetch(`/admin/imports/${this._importId}/status`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!r.ok) return;
                    const data = await r.json();
                    if (['SUCCESS','PARTIAL','FAILED'].includes(data.status)) {
                        clearInterval(this._timer);
                        this._applyDone({ done: true, rows: data.rows_processed, failures: data.failures_count, downloadUrl: data.download_url });
                        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ id: this._importId, done: true, rows: data.rows_processed, failures: data.failures_count, downloadUrl: data.download_url }));
                    }
                } catch {}
            }, 3000);
        },

        _applyDone(d) {
            this.done = true;
            this.rows = d.rows ?? 0;
            this.failures = d.failures ?? 0;
            this.downloadUrl = d.downloadUrl ?? null;
        },

        dismiss() {
            clearInterval(this._timer);
            this.visible = false;
            sessionStorage.removeItem(STORAGE_KEY);
        }
    };
}

function bulkImportUploader() {
    return {
        dragging: false,
        file: null,
        error: null,
        submitting: false,
        submitError: null,

        async submitImport(form) {
            if (!this.file || this.error || this.submitting) return;
            this.submitting = true;
            this.submitError = null;
            try {
                const fd = new FormData(form);
                const r = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                const data = await r.json();
                if (!r.ok) {
                    this.submitError = data.message ?? 'Upload failed. Please try again.';
                    return;
                }
                // Close modal and kick off status polling
                this.$dispatch('close-bulk-modal');
                this.$dispatch('import-queued', { id: data.import_log_id });
                this.clear();
            } catch {
                this.submitError = 'Network error — please try again.';
            } finally {
                this.submitting = false;
            }
        },

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
            if (!['csv', 'txt', 'xlsx', 'xls'].includes(ext)) {
                this.error = 'Only .csv, .txt, .xlsx, or .xls files are accepted.';
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

            // For drag-and-drop: assign dropped file to the hidden input via DataTransfer
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
            this.submitting = false;
            this.submitError = null;
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
        selectedCollege: '{{ request('filter_college', '') }}',
        selectedDept:    '{{ request('filter_dept', '') }}',
        selectedProgram: '{{ request('filter_program', '') }}',
        filteredDepts:   [],
        filteredPrograms:[],

        init() {
            this.filteredDepts    = this.selectedCollege
                ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                : this.allDepts;
            this.filteredPrograms = this.selectedDept
                ? this.allProgs.filter(p => String(p.department_id) === String(this.selectedDept))
                : (this.selectedCollege ? this.allProgs.filter(p =>
                    this.filteredDepts.some(d => String(d.id) === String(p.department_id))
                  ) : this.allProgs);
        },

        onCollegeChange() {
            this.filteredDepts    = this.selectedCollege
                ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                : this.allDepts;
            this.selectedDept    = '';
            this.selectedProgram = '';
            this.filteredPrograms = [];
        },

        onDeptChange() {
            this.filteredPrograms = this.selectedDept
                ? this.allProgs.filter(p => String(p.department_id) === String(this.selectedDept))
                : (this.selectedCollege
                    ? this.allProgs.filter(p =>
                        this.filteredDepts.some(d => String(d.id) === String(p.department_id))
                      )
                    : this.allProgs);
            this.selectedProgram = '';
        },
    };
}
</script>
@endpush
