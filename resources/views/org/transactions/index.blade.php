@extends('layouts.app')
@section('title', 'Transaction History')
@section('page-title', 'Transaction History')

@section('content')
<div class="page-shell">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Transaction History</h2>
            <p class="text-[13.5px] text-green-400 mt-0.5 font-medium">
                Receipts for {{ auth()->user()->organization?->name ?? 'your organization' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->canCreateTransactions())
            <a href="{{ route('org.transactions.create') }}"
               class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Transaction
            </a>
            @endif
            <a href="{{ route('org.reports.sor') }}"
               class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 text-green-600 hover:border-green-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                SOR Report
            </a>
        </div>
    </div>

    {{-- ── Filter Panel ── --}}
    <form method="GET" action="{{ route('org.transactions.index') }}" id="filter-form">
        <div class="bg-white rounded-xl border border-green-200 shadow-sm p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-[13px] font-bold text-green-700">Filters</p>
                @if(request()->hasAny(['date_from','date_to','academic_year_id','type','payment_method','officer_id','search']))
                <a href="{{ route('org.transactions.index') }}"
                   class="text-[12px] font-semibold text-red-400 hover:text-red-600 transition-colors">
                    Clear all filters
                </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">

                {{-- Date From --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Date From
                    </label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Date To
                    </label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                </div>

                {{-- Semester --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Semester
                    </label>
                    <select name="academic_year_id"
                            class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                        <option value="">All Semesters</option>
                        @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" @selected(request('academic_year_id') == $ay->id)>
                            {{ $ay->name }}{{ $ay->is_active ? ' (Active)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fee Type --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Fee Type
                    </label>
                    <select name="type"
                            class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                        <option value="">All Types</option>
                        <option value="FEE"  @selected(request('type') === 'FEE')>Membership Fee</option>
                        <option value="FINE" @selected(request('type') === 'FINE')>Absence Fine</option>
                    </select>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Method
                    </label>
                    <select name="payment_method"
                            class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                        <option value="">All Methods</option>
                        <option value="CASH"  @selected(request('payment_method') === 'CASH')>Cash</option>
                        <option value="GCASH" @selected(request('payment_method') === 'GCASH')>GCash</option>
                    </select>
                </div>

                {{-- Processing Officer --}}
                <div>
                    <label class="block text-[11px] font-bold text-green-400 uppercase tracking-widest mb-1.5">
                        Officer
                    </label>
                    <select name="officer_id"
                            class="w-full px-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors bg-white">
                        <option value="">All Officers</option>
                        @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" @selected(request('officer_id') == $officer->id)>
                            {{ $officer->student?->full_name ?? $officer->username }}
                        </option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Search + Apply row --}}
            <div class="flex flex-col sm:flex-row gap-3 mt-4 pt-4 border-t border-green-100">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-green-300 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by OR number, student number, or name…"
                           class="w-full pl-10 pr-4 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                </div>
                <button type="submit"
                        class="px-6 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm shrink-0">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>

    {{-- ── Results Panel ── --}}
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">

        {{-- Results header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">Receipts</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">
                    {{ $transactions->total() }} transaction{{ $transactions->total() !== 1 ? 's' : '' }}
                    @if(request()->hasAny(['date_from','date_to','academic_year_id','type','payment_method','officer_id','search']))
                    <span class="text-amber-500">· filtered</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('org.reports.sor.pdf') }}"
               class="px-3 py-1.5 rounded-lg text-[12px] font-bold flex items-center gap-1.5 bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Export SOR PDF
            </a>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" style="min-width:900px">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Date</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">OR No.</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Student</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Fee</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Method</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Officer</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest text-right">Amount</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest">Status</th>
                        <th class="px-5 py-3.5 text-[11px] font-bold text-green-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f6faf7] transition-colors last:border-b-0">

                        {{-- Date --}}
                        <td class="px-5 py-4 text-[12.5px] text-green-400 font-medium whitespace-nowrap">
                            {{ $tx->created_at->format('M d, Y') }}<br>
                            <span class="text-[11px] text-green-300">{{ $tx->created_at->format('H:i') }}</span>
                        </td>

                        {{-- OR Number --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-mono text-[13px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">
                                {{ $tx->or_number }}
                            </span>
                        </td>

                        {{-- Student --}}
                        <td class="px-5 py-4">
                            <p class="text-[13.5px] font-bold text-green-800 leading-tight">
                                {{ $tx->student?->full_name ?? '—' }}
                            </p>
                            <p class="text-[11.5px] text-green-300 font-mono mt-0.5">
                                {{ $tx->student?->student_number }}
                            </p>
                        </td>

                        {{-- Fee Type --}}
                        <td class="px-5 py-4">
                            @if($tx->transaction_type === 'FINE')
                            <span class="inline-flex px-2 py-0.5 rounded-md bg-red-50 text-red-700 text-[11.5px] font-bold">Fine</span>
                            @else
                            <span class="text-[13px] font-semibold text-green-600">
                                {{ $tx->feeProfile?->name ?? 'Fee' }}
                            </span>
                            @if($tx->feeProfile?->category)
                            <span class="ml-1 text-[11px] text-green-300 font-medium">
                                ({{ ucfirst(strtolower($tx->feeProfile->category)) }})
                            </span>
                            @endif
                            @endif
                        </td>

                        {{-- Payment Method --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($tx->payment_method === 'GCASH')
                            <span class="inline-flex items-center gap-1 text-[12.5px] font-bold text-blue-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                GCash
                            </span>
                            @else
                            <span class="text-[12.5px] font-bold text-green-500">Cash</span>
                            @endif
                        </td>

                        {{-- Officer --}}
                        <td class="px-5 py-4">
                            <p class="text-[12.5px] font-semibold text-green-700 leading-tight">
                                {{ $tx->processedBy?->student?->full_name ?? $tx->processedBy?->username ?? '—' }}
                            </p>
                            <p class="text-[11px] text-green-300 font-medium">
                                {{ ucfirst(strtolower($tx->processedBy?->role ?? '')) }}
                            </p>
                        </td>

                        {{-- Amount --}}
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <span class="font-mono text-[14px] font-black text-green-800">
                                ₱{{ number_format($tx->amount_paid, 2) }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($tx->is_void)
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-[11.5px] font-bold">Voided</span>
                            @elseif($tx->remittance_id)
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[11.5px] font-bold">Remitted</span>
                            @else
                            <span class="inline-flex px-2.5 py-1 rounded-md bg-yellow-50 text-yellow-700 text-[11.5px] font-bold">Unremitted</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('org.transactions.show', $tx) }}"
                                   class="px-3 py-1.5 rounded-lg text-[12px] font-bold border border-green-200 text-green-500 hover:border-green-600 hover:text-green-700 transition-colors">
                                    View
                                </a>
                                <a href="{{ route('org.transactions.receipt', $tx) }}" target="_blank"
                                   class="px-3 py-1.5 rounded-lg text-[12px] font-bold border border-green-200 text-green-500 hover:border-green-600 hover:text-green-700 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 text-green-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-[14px] font-semibold text-green-400">No transactions found</p>
                            <p class="text-[12.5px] text-green-300 mt-1">
                                @if(request()->hasAny(['date_from','date_to','academic_year_id','type','payment_method','officer_id','search']))
                                    Try adjusting your filters above.
                                @else
                                    Transactions will appear here once recorded.
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex flex-col sm:flex-row justify-between items-center gap-3 bg-green-50">
            <span class="text-[12.5px] font-medium text-green-400">
                Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                of {{ $transactions->total() }} transactions
            </span>
            <div class="text-[13px]">{{ $transactions->withQueryString()->links() }}</div>
        </div>
        @endif

    </div>
</div>
@endsection
