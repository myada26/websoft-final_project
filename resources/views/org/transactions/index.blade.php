@extends('layouts.app')
@section('title', 'Transaction History')
@section('page-title', 'Transaction History')

@section('content')
<div class="max-w-6xl mx-auto pb-10">
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Transaction History</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Receipts scoped to {{ auth()->user()->organization?->name ?? 'your organization' }}</p>
        </div>
        @if(auth()->user()->canCreateTransactions())
        <a href="{{ route('org.transactions.create') }}" class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-[#1a7a41] hover:bg-[#27a05a] text-white border-2 border-transparent transition-all shadow-sm">
            @include('partials.ui-icon', ['name' => 'credit-card', 'class' => 'w-4 h-4'])
            Create Transaction
        </a>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">Receipts</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $transactions->total() }} total transactions</p>
            </div>
            <form method="GET" action="{{ route('org.transactions.index') }}" class="relative w-full md:w-[280px]">
                <input name="search" value="{{ request('search') }}" type="text" placeholder="Search OR, student no., or name"
                    class="w-full px-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[820px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">OR No.</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Student</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Fee</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Method</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0">
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $tx->or_number }}</span></td>
                        <td class="px-6 py-4">
                            <div class="text-[14px] font-bold text-[#0f1f17]">{{ $tx->student?->full_name ?? 'Student' }}</div>
                            <div class="text-[12px] text-[#8aa89a] font-mono">{{ $tx->student?->student_number }}</div>
                        </td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $tx->feeProfile?->name ?? $tx->transaction_type }}</td>
                        <td class="px-6 py-4 text-[13px] font-bold text-[#4a6356]">{{ $tx->payment_method }}</td>
                        <td class="px-6 py-4 font-mono text-[14px] font-bold text-[#0f1f17]">₱{{ number_format($tx->amount_paid, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($tx->is_void)
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-[11.5px] font-bold">Voided</span>
                            @elseif($tx->remittance_id)
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-[#eff6ff] text-[#1d4ed8] text-[11.5px] font-bold">Remitted</span>
                            @else
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold">Unremitted</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('org.transactions.show', $tx) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-[12px] font-bold border border-[#dde8e1] text-[#4a6356] hover:border-[#1a7a41] hover:text-[#1a7a41]">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $transactions->firstItem() ?? 0 }}-{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}</span>
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
