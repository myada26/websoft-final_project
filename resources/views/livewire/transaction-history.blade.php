<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Transaction History</h2>
            <p class="text-sm text-gray-500">View and print receipts for all transactions.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="date" wire:model.live="date" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-[#00491E] focus:ring-[#00491E]">
            
            <select wire:model.live="type" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-[#00491E] focus:ring-[#00491E]">
                <option value="">All Types</option>
                <option value="FEE">Membership Fee</option>
                <option value="FINE">Absence Fine</option>
            </select>
            
            <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-[#00491E] focus:ring-[#00491E]">
                <option value="">All Statuses</option>
                <option value="FULLY_PAID">Fully Paid</option>
                <option value="PARTIAL">Partial</option>
                <option value="VOID">Voided</option>
            </select>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OR Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($transactions as $tx)
                        @php
                            $totalDue = $tx->isFine() 
                                ? ($tx->studentFine ? $tx->studentFine->fine_amount : $tx->amount_paid)
                                : ($tx->feeProfile ? $tx->feeProfile->amount : $tx->amount_paid);
                            $balance = $totalDue - $tx->amount_paid;
                            $isPartial = $balance > 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tx->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $tx->or_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tx->student->first_name }} {{ $tx->student->last_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tx->isFine() ? 'Fine' : 'Fee' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                ₱{{ number_format($tx->amount_paid, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $balance > 0 ? 'text-amber-600 font-medium' : 'text-gray-500' }}">
                                ₱{{ number_format($balance, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($tx->is_void)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Void
                                    </span>
                                @elseif($balance > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#FFF3DC] text-[#7A4800]">
                                        Partial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#EBF3E8] text-[#1E4D0F]">
                                        Fully Paid
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('transactions.receipt.download', $tx->id) }}" target="_blank" class="text-[#00491E] hover:text-[#1B6332] inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                No transactions found matching the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
