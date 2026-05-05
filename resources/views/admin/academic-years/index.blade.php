@extends('layouts.app')
@section('title', 'Academic Years')
@section('page-title', 'Academic Years')

@section('content')
<div class="max-w-6xl mx-auto pb-10" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Academic Years &amp; Semesters</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Only one semester may be active at a time. All transactions default to the active semester.</p>
        </div>
        <button @click="open = true" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add Semester
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">All Semesters</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $academicYears->total() }} total records</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Academic Year</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Semester</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Start Date</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">End Date</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $ay)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $ay->year }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-[#0f1f17]">{{ $ay->semester }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $ay->start_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $ay->end_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @if($ay->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#f3f4f6] text-[#4b5563] text-[11.5px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#6b7280]"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if(!$ay->is_active)
                                <form method="POST" action="{{ route('admin.academic-years.set-active', $ay) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="Set as Active" class="px-3 py-1 text-[12px] font-bold rounded-lg border-2 border-[#1a7a41] text-[#1a7a41] hover:bg-[#e6f4ec] transition-colors">
                                        Set Active
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('admin.academic-years.edit', $ay) }}" class="p-1.5 rounded-lg text-[#8aa89a] hover:bg-[#dde8e1] hover:text-[#1a7a41] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No semesters found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $academicYears->firstItem() ?? 0 }}–{{ $academicYears->lastItem() ?? 0 }} of {{ $academicYears->total() }}</span>
            {{ $academicYears->links() }}
        </div>
    </div>

    {{-- Add Semester Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div><h2 class="text-[18px] font-bold text-[#0f1f17]">Add New Semester</h2><p class="text-[13px] text-[#4a6356] mt-0.5 font-medium">Define a new academic year and semester.</p></div>
                <button @click="open = false" class="text-[#8aa89a] hover:bg-[#f0f3f1] p-2 rounded-xl transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form method="POST" action="{{ route('admin.academic-years.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Academic Year <span class="text-red-500">*</span></label>
                        <input type="text" name="year" value="{{ old('year') }}" placeholder="e.g. 2024-2025" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Semester <span class="text-red-500">*</span></label>
                        <select name="semester" class="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Midyear">Midyear</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Start Date <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] transition-colors">
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">End Date <span class="text-red-500">*</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] transition-colors">
                        </div>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-[#1a7a41]">
                        <span class="text-[13px] font-semibold text-[#4a6356]">Set as active semester immediately</span>
                    </label>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">Save Semester</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
