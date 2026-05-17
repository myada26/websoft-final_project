@extends('layouts.app')
@section('title', 'Fee Profiles')
@section('page-title', 'Fee Profiles')

@section('content')
<div class="page-shell" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Fee Profiles</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">Membership fees and collection rules for your organization</p>
        </div>
        <button @click="open = true" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
            </svg>Create Fee Profile
        </button>
    </div>

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">All Fee Profiles</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $feeProfiles->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('org.fee-profiles.index') }}" class="flex items-center gap-2.5">
                <select name="semester_id" onchange="this.form.submit()" class="border-2 border-green-200 rounded-lg py-2 px-3 text-[13px] font-medium text-green-400 outline-none focus:border-green-600 bg-white cursor-pointer transition-colors">
                    <option value="">All Semesters</option>
                    @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>{{ $sem->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Profile Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Semester</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Applies To</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeProfiles as $fp)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4 text-[14px] font-bold text-green-800">{{ $fp->name }}</td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $fp->academicYear?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-[14px] font-bold text-green-800">₱{{ number_format($fp->amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $fp->scope_label ?? 'All Students' }}</td>
                        <td class="px-6 py-4">
                            @if($fp->is_active ?? true)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Active</span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#f3f4f6] text-[#4b5563] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#6b7280]"></span> Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('org.fee-profiles.edit', $fp) }}" class="p-1.5 rounded-lg text-green-300 hover:bg-green-200 hover:text-green-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('org.fee-profiles.destroy', $fp) }}" onsubmit="return confirm('Delete {{ addslashes($fp->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-green-300 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">No fee profiles configured yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-green-50">
            <span class="text-[12.5px] font-medium text-green-300">Showing {{ $feeProfiles->firstItem() ?? 0 }}–{{ $feeProfiles->lastItem() ?? 0 }} of {{ $feeProfiles->total() }}</span>
            {{ $feeProfiles->withQueryString()->links() }}
        </div>
    </div>

    {{-- Create Fee Profile Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-xl w-full max-w-2xl shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div>
                    <h2 class="text-[18px] font-bold text-green-800">Create Fee Profile</h2>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Define membership fee amounts and collection rules.</p>
                </div>
                <button @click="open = false" class="text-green-300 hover:bg-[#f0f3f1] p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form method="POST" action="{{ route('org.fee-profiles.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-6">
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Profile Details</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Profile Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. SSC Membership Fee 2024-2025 1st Sem" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Fee Amount (₱) <span class="text-red-500">*</span></label>
                                <input type="number" name="amount" step="0.01" value="{{ old('amount') }}" placeholder="0.00" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Semester <span class="text-red-500">*</span></label>
                                <select name="academic_year_id" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                    <option value="">— Select —</option>
                                    @foreach($semesters as $sem)<option value="{{ $sem->id }}" {{ old('academic_year_id') == $sem->id ? 'selected' : '' }}>{{ $sem->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Collection Rules</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Applies To</label>
                            <select name="applies_to" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                <option value="all">All Students</option>
                                <option value="regular">Regular Students Only</option>
                                <option value="irregular">Irregular Students Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Notes / Description <span class="text-[11px] font-normal text-green-300 ml-1">(Optional)</span></label>
                            <textarea name="notes" rows="3" placeholder="Additional fee collection notes..." class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors resize-none"></textarea>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-green-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">Save Profile</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection