@extends('layouts.app')
@section('title', 'Void Requests')
@section('page-title', 'Void Requests')

@section('content')
<div class="max-w-6xl mx-auto pb-10" x-data="{ open: false }">

    {{-- Warning banner --}}
    <div class="flex items-start gap-4 px-5 py-4 rounded-2xl bg-[#fef9c3] border border-[#fde047] mb-6">
        <svg class="w-5 h-5 text-[#ca8a04] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <p class="text-[13.5px] font-bold text-[#92400e]">Void requests require supervisor approval (FR-0012)</p>
            <p class="text-[12.5px] text-[#a16207] mt-0.5 font-medium">Approved voids are permanently reflected in reports. This action cannot be undone after approval.</p>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Void Requests</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Request to void erroneous transactions pending supervisor review</p>
        </div>
        @if(auth()->user()->canRequestVoid())
        <button @click="open = true" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
            </svg>New Void Request
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-[#eaf0ec] flex items-center justify-between">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">Void Request History</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $voidRequests->total() }} total requests</p>
            </div>
            <form method="GET" action="{{ route('org.void-requests.index') }}" class="flex items-center gap-2.5">
                <select name="status" onchange="this.form.submit()" class="border-2 border-[#dde8e1] rounded-xl py-2 px-3 text-[13px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] bg-white cursor-pointer transition-colors">
                    <option value="">All Status</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">OR No.</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Student</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Reason</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Date Requested</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Status</th>
                        @if(auth()->user()->canApproveVoid())
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($voidRequests as $vr)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $vr->transaction?->or_number }}</span></td>
                        <td class="px-6 py-4 text-[14px] font-bold text-[#0f1f17]">{{ $vr->transaction?->student?->full_name ?? '—' }}</td>
                        <td class="px-6 py-4 font-mono text-[14px] font-bold text-[#0f1f17]">₱{{ number_format($vr->transaction?->amount, 2) }}</td>
                        <td class="px-6 py-4 text-[13px] text-[#4a6356] font-medium max-w-[200px] truncate">{{ $vr->reason }}</td>
                        <td class="px-6 py-4 text-[12.5px] text-[#8aa89a]">{{ $vr->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4">
                            @if($vr->status === 'PENDING')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#eab308]"></span> Pending</span>
                            @elseif($vr->status === 'APPROVED')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Approved</span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rejected</span>
                            @endif
                        </td>
                        @if(auth()->user()->canApproveVoid())
                        <td class="px-6 py-4 text-right">
                            @if($vr->status === 'PENDING')
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('org.void-requests.approve', $vr) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-[12px] font-bold bg-[#dcfce7] text-[#15803d] border border-[#86efac]">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('org.void-requests.reject', $vr) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-[12px] font-bold bg-red-50 text-red-700 border border-red-200">Reject</button>
                                </form>
                            </div>
                            @else
                            <span class="text-[12px] text-[#8aa89a]">Resolved</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->canApproveVoid() ? 7 : 6 }}" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No void requests submitted</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $voidRequests->firstItem() ?? 0 }}–{{ $voidRequests->lastItem() ?? 0 }} of {{ $voidRequests->total() }}</span>
            {{ $voidRequests->withQueryString()->links() }}
        </div>
    </div>

    @if(auth()->user()->canRequestVoid())
    {{-- New Void Request Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f1f17]">New Void Request</h2>
                    <p class="text-[13px] text-[#4a6356] mt-0.5 font-medium">Submit a void request for supervisor review.</p>
                </div>
                <button @click="open = false" class="text-[#8aa89a] hover:bg-[#f0f3f1] p-2 rounded-xl transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form method="POST" action="{{ route('org.void-requests.store') }}" enctype="multipart/form-data" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Official Receipt Number <span class="text-red-500">*</span></label>
                        <input type="text" name="or_number" value="{{ old('or_number') }}" placeholder="e.g. OR-2024-0001"
                            class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-[#f8fbf9] text-[14px] font-bold font-mono text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Reason for Void <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="4" placeholder="Provide a clear explanation for the void request (e.g. duplicate entry, wrong student, incorrect amount)..."
                            class="w-full px-4 py-2.5 border-2 border-[#dde8e1] rounded-xl bg-white text-[14px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[#4a6356] mb-2">Supporting Document <span class="text-[11px] font-normal text-[#8aa89a] ml-1">(Optional)</span></label>
                        <div class="flex flex-col items-center border-2 border-dashed border-[#dde8e1] rounded-xl bg-[#f8fbf9] p-6 text-center">
                            <svg class="w-10 h-10 text-[#1a7a41] opacity-30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <p class="text-[12.5px] text-[#4a6356] mb-3 font-medium">Attach proof or memo (PDF, JPG, PNG)</p>
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" class="text-[13px] text-[#4a6356]">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-[#f8fbf9] flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-[13.5px] font-bold bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
