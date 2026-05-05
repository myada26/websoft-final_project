@extends('layouts.app')
@section('title', 'Manage Organizations')
@section('page-title', 'Manage Organizations')

@section('content')
<div class="max-w-6xl mx-auto pb-10" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Manage Organizations</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Each organization is scoped to a hierarchy level (SSC / College / Department)</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('admin.organizations.index', ['export' => 'csv']) }}" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export Data
            </a>
            <button @click="open = true" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add Organization
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">All Organizations</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $organizations->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('admin.organizations.index') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative w-full md:w-[220px]">
                    <svg class="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search organizations..."
                           class="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                </div>
                <select name="type" onchange="this.form.submit()" class="border-2 border-[#dde8e1] rounded-xl py-2 px-3 text-[13px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] bg-white cursor-pointer transition-colors">
                    <option value="">All Types</option>
                    <option value="SSC" {{ request('type') === 'SSC' ? 'selected' : '' }}>SSC</option>
                    <option value="COLLEGE_COUNCIL" {{ request('type') === 'COLLEGE_COUNCIL' ? 'selected' : '' }}>College Council</option>
                    <option value="DEPT_SOCIETY" {{ request('type') === 'DEPT_SOCIETY' ? 'selected' : '' }}>Dept Society</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Organization Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Type</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Parent Unit</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Users</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $org)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4 text-[14px] font-bold text-[#0f1f17]">{{ $org->name }}</td>
                        <td class="px-6 py-4"><span class="font-mono text-[12px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $org->type }}</span></td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $org->college->code ?? $org->department->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $org->users_count ?? $org->users()->count() }}</td>
                        <td class="px-6 py-4">
                            @if($org->is_active ?? true)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Active</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#f3f4f6] text-[#4b5563] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#6b7280]"></span> Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.organizations.edit', $org) }}" class="p-1.5 rounded-lg text-[#8aa89a] hover:bg-[#dde8e1] hover:text-[#1a7a41] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.organizations.destroy', $org) }}" onsubmit="return confirm('Delete {{ addslashes($org->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-[#8aa89a] hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No organizations found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $organizations->firstItem() ?? 0 }}–{{ $organizations->lastItem() ?? 0 }} of {{ $organizations->total() }}</span>
            {{ $organizations->withQueryString()->links() }}
        </div>
    </div>

    {{-- Add Organization Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div><h2 class="text-[18px] font-bold text-[#0f1f17]">Add New Organization</h2><p class="text-[13px] text-[#4a6356] mt-0.5 font-medium">Create an organization scoped to a hierarchy level.</p></div>
                <button @click="open = false" class="text-[#8aa89a] hover:bg-[#f0f3f1] p-2 rounded-xl transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form method="POST" action="{{ route('admin.organizations.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Organization Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. COE Student Council" class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Organization Type <span class="text-red-500">*</span></label>
                        <select name="type" class="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                            <option value="SSC">Supreme Student Council (SSC)</option>
                            <option value="COLLEGE_COUNCIL">College Council</option>
                            <option value="DEPT_SOCIETY">Department Society</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Parent College (if applicable)</label>
                        <select name="college_id" class="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                            <option value="">— None —</option>
                            @foreach($colleges as $c)<option value="{{ $c->id }}" {{ old('college_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Parent Department (if applicable)</label>
                        <select name="department_id" class="w-full px-4 py-3 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors cursor-pointer appearance-none">
                            <option value="">— None —</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }} ({{ $d->code }})</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">Save Organization</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
