@extends('layouts.app')
@section('title', 'New Transaction')
@section('page-title', 'New Transaction')

@section('content')
@php
    $feeProfileState = ($feeProfiles ?? collect())->map(fn($fp) => [
        'id' => $fp->id,
        'name' => $fp->name,
        'amount' => (float) $fp->amount,
        'category' => $fp->category,
    ])->values();

    $searchResultState = ($searchResults ?? collect())->map(fn($sr) => [
        'id' => $sr->id,
        'name' => $sr->full_name,
        'number' => $sr->student_number,
        'program' => $sr->latestEnrollment?->program?->code ?? '',
        'hasPaid' => (bool) ($sr->hasPaidThisSemester ?? false),
    ])->values();

    $unpaidFinesState = ($unpaidFines ?? collect())->map(fn($f) => [
        'id' => $f->id,
        'eventName' => $f->event?->name ?? 'Event Fine',
        'eventDate' => $f->event?->date ? \Carbon\Carbon::parse($f->event->date)->format('M d, Y') : '',
        'amount' => (float) $f->fine_amount,
    ])->values();
@endphp
<script type="application/json" id="transaction-fee-profiles">@json($feeProfileState)</script>
<script type="application/json" id="transaction-search-results">@json($searchResultState)</script>
<script type="application/json" id="transaction-unpaid-fines">@json($unpaidFinesState)</script>
<script type="application/json" id="transaction-search-query">@json(request('student', ''))</script>

<div class="max-w-5xl mx-auto pb-10" x-data="transactionFlow">

    {{-- Page Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('org.dashboard') }}" class="p-2 rounded-lg text-green-300 hover:bg-white hover:text-green-600 border-2 border-transparent hover:border-green-200 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h2 class="text-[22px] font-bold text-green-800">New Transaction</h2>
            <p class="text-[13.5px] text-green-400 mt-0.5 font-medium">Point-of-sale fee collection · 4-step process</p>
        </div>
    </div>

    {{-- Step progress indicator --}}
    <div class="flex items-center gap-0 mb-8 px-2">
        @foreach([
        ['num' => 1, 'label' => 'Find Student'],
        ['num' => 2, 'label' => 'Select Fees'],
        ['num' => 3, 'label' => 'Payment'],
        ['num' => 4, 'label' => 'Confirmation'],
        ] as $s)
        <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center" style="min-width:60px">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-[14px] font-black transition-all"
                    :class="step > {{ $s['num'] }} ? 'bg-green-600 text-white' : (step === {{ $s['num'] }} ? 'bg-gold-500 text-green-800' : 'bg-white border-2 border-green-200 text-green-300')">
                    <template x-if="step > {{ $s['num'] }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg></template>
                    <template x-if="step <= {{ $s['num'] }}"><span>{{ $s['num'] }}</span></template>
                </div>
                <span class="text-[11.5px] font-bold mt-1.5 whitespace-nowrap"
                    :class="step === {{ $s['num'] }} ? 'text-green-600' : 'text-green-300'">{{ $s['label'] }}</span>
            </div>
            @if(!$loop->last)
            <div class="flex-1 h-[2px] mx-2 mt-[-18px]"
                :class="step > {{ $s['num'] }} ? 'bg-green-600' : 'bg-green-200'"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Main panel + Summary sidebar --}}
    <div class="flex gap-6 items-start">

        {{-- ── Step panels ──────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- STEP 1 — Find Student --}}
            <div x-show="step === 1" class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 1 — Find Student</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Search by student number or name</p>
                </div>
                <div class="p-6">
                    <div class="flex gap-3 mb-6">
                        <div class="relative flex-1">
                            <svg class="w-5 h-5 text-green-300 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                            <input x-model="studentSearch" @keydown.enter.prevent="searchStudent()" type="text" placeholder="e.g. 2024-0001 or Juan Dela Cruz"
                                class="w-full pl-11 pr-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                        <button type="button" @click="searchStudent()"
                            class="px-5 py-3 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white transition-all shadow-sm">
                            Search
                        </button>
                    </div>

                    @if($searchResults ?? false)
                    <div class="space-y-2 mb-2">
                        @forelse($searchResults as $sr)
                        <button type="button"
                            @click="chooseStudent({{ $sr->id }})"
                            class="w-full flex items-center gap-4 p-4 rounded-lg border-2 border-green-200 hover:border-green-600 hover:bg-green-50 transition-all text-left cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-[14px] font-bold text-green-800">{{ $sr->full_name }}</p>
                                <p class="text-[12.5px] text-green-400 font-medium">{{ $sr->student_number }} · {{ $sr->latestEnrollment?->program?->code ?? 'No program' }}</p>
                            </div>
                            @if($sr->hasPaidThisSemester ?? false)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Paid</span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#eab308]"></span> Pending</span>
                            @endif
                        </button>
                        @empty
                        <div class="text-center py-10 text-[14px] font-semibold text-green-400">No students found for "{{ $searchQuery ?? '' }}"</div>
                        @endforelse
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="w-14 h-14 text-green-300 opacity-20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-[14px] font-semibold text-green-400">Search for a student to begin</p>
                        <p class="text-[12.5px] text-green-300 mt-1 font-medium">Enter a student number or full name above</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- STEP 2 — Select Fees --}}
            <div x-show="step === 2" class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 2 — Select Fees</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Choose the applicable fee profiles</p>
                </div>
                <div class="p-6 space-y-3">
                    @if($unpaidFines->count() > 0)
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <h4 class="text-[14px] font-bold text-amber-800 mb-2">Outstanding Fines</h4>
                        <div class="space-y-2">
                            @foreach($unpaidFines as $fine)
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="fine_{{ $fine->id }}" value="{{ $fine->id }}" class="fine-checkbox w-4 h-4 text-green-600 rounded border-green-300 focus:ring-green-500">
                                <label for="fine_{{ $fine->id }}" class="flex-1 text-[13px] font-medium text-green-700">
                                    {{ $fine->event?->name ?? 'Event Fine' }} ({{ $fine->event?->date ? \Carbon\Carbon::parse($fine->event->date)->format('M d, Y') : '' }})
                                </label>
                                <span class="font-mono text-[14px] font-bold text-amber-700">₱{{ number_format($fine->fine_amount, 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @forelse($feeProfiles as $fp)
                    <button type="button" @click="toggleFee({{ $fp->id }})"
                        :class="hasFee({{ $fp->id }}) ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-600'"
                        class="w-full flex items-center justify-between p-4 rounded-lg border-2 transition-all text-left cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div :class="hasFee({{ $fp->id }}) ? 'bg-green-600 border-green-600' : 'bg-white border-green-200'"
                                class="w-5 h-5 rounded-md border-2 flex items-center justify-center shrink-0 transition-all">
                                <svg x-show="hasFee({{ $fp->id }})" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-green-800">{{ $fp->name }}</p>
                                <p class="text-[12.5px] text-green-400 font-medium">{{ $fp->academicYear?->name ?? '—' }} · {{ $fp->scope_label ?? 'All Students' }}</p>
                            </div>
                        </div>
                        <span class="font-mono text-[15px] font-black text-green-800">₱{{ number_format($fp->amount, 2) }}</span>
                    </button>
                    @empty
                    <div class="text-center py-10 text-[14px] font-semibold text-green-400">No fee profiles configured. <a href="{{ route('org.fee-profiles.index') }}" class="text-green-600 underline">Create one first.</a></div>
                    @endforelse

                    <div class="flex justify-between items-center pt-4 mt-4 border-t border-[#eaf0ec]">
                        <button @click="step = 1" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">← Back</button>
                        <button @click="if(canProceedStep2()) step = 3" :disabled="!canProceedStep2()"
                            class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Continue →
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 3 — Payment --}}
            <div x-show="step === 3" class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 3 — Payment Method</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Select how the student is paying</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="paymentMethod = 'CASH'"
                            :class="paymentMethod === 'CASH' ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-600'"
                            class="flex flex-col items-center gap-2 p-5 rounded-lg border-2 transition-all cursor-pointer">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                                <line x1="1" y1="10" x2="23" y2="10" />
                            </svg>
                            <span class="text-[14px] font-bold text-green-800">Cash</span>
                        </button>
                        <button type="button" @click="paymentMethod = 'GCASH'"
                            :class="paymentMethod === 'GCASH' ? 'border-[#2563eb] bg-blue-50' : 'border-green-200 hover:border-[#2563eb]'"
                            class="flex flex-col items-center gap-2 p-5 rounded-lg border-2 transition-all cursor-pointer">
                            <svg class="w-8 h-8 text-[#2563eb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[14px] font-bold text-green-800">GCash</span>
                        </button>
                    </div>

                    <div x-show="paymentMethod === 'GCASH'" class="mt-2">
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">GCash Reference Number <span class="text-red-500">*</span></label>
                        <input x-model="gcashRef" type="text" placeholder="e.g. 1234567890123"
                            class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-green-50 text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors">
                    </div>

                    <div class="mt-2">
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">Remarks <span class="text-[11px] font-normal text-green-300 ml-1">(Optional)</span></label>
                        <textarea x-model="remarks" rows="2" placeholder="e.g. Installment payment, senior discount, etc."
                            class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors resize-none"></textarea>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#eaf0ec]">
                        <button @click="step = 2" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">← Back</button>
                        <button @click="if(canProceedStep3()) step = 4" :disabled="!canProceedStep3()"
                            class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Review →
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 4 — Confirm & Submit --}}
            <div x-show="step === 4" class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 4 — Confirm &amp; Issue Receipt</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Review the transaction before posting</p>
                </div>
                <div class="p-6">
                    <div class="bg-green-50 rounded-lg border border-[#eaf0ec] p-5 space-y-3 mb-6">
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Student</span>
                            <span class="font-bold text-green-800" x-text="student?.name ?? '—'"></span>
                        </div>
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Student No.</span>
                            <span class="font-mono font-bold text-green-600" x-text="student?.number ?? '—'"></span>
                        </div>
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Payment Method</span>
                            <span class="font-bold text-green-800" x-text="paymentMethod"></span>
                        </div>
                        <template x-if="paymentMethod === 'GCASH'">
                            <div class="flex justify-between text-[13.5px]">
                                <span class="text-green-400 font-semibold">GCash Ref</span>
                                <span class="font-mono font-bold text-[#2563eb]" x-text="gcashRef"></span>
                            </div>
                        </template>
                        <div class="border-t border-green-200 pt-3">
                            <template x-for="fee in selectedFees" :key="fee.id">
                                <div class="flex justify-between text-[13.5px] mb-1">
                                    <span class="text-green-400 font-medium" x-text="fee.name"></span>
                                    <span class="font-mono font-semibold text-green-800" x-text="'₱' + parseFloat(fee.amount).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-between text-[16px] font-black border-t border-green-200 pt-3">
                            <span class="text-green-800">Total</span>
                            <span class="text-green-600" x-text="'₱' + totalAmount().toFixed(2)"></span>
                        </div>
                    </div>

                    <form method="POST" :action="selectedFines.length > 0 ? '{{ route('org.transactions.fine') }}' : '{{ route('org.transactions.store') }}'" id="pos-form">
                        @csrf
                        <input type="hidden" name="student_id" :value="student?.id ?? ''">
                        <input type="hidden" name="payment_method" :value="paymentMethod">
                        <input type="hidden" name="gcash_reference" :value="gcashRef">
                        <input type="hidden" name="remarks" :value="remarks">
                        <input type="hidden" name="amount_paid" :value="totalAmount()">
                        <input type="hidden" name="student_fine_id" :value="selectedFines.length > 0 ? selectedFines[0].id : ''">
                        <template x-for="fee in selectedFees" :key="fee.id">
                            <input type="hidden" name="fee_profile_ids[]" :value="fee.id">
                        </template>

                        <div class="flex justify-between items-center">
                            <button type="button" @click="step = 3" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">← Back</button>
                            <button type="submit"
                                class="px-6 py-3 rounded-lg text-[14px] font-black bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-md shadow-green-600/20 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Confirm &amp; Issue Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- ── Summary Sidebar ──────────────────────────────────── --}}
        <div class="w-72 shrink-0 hidden xl:block">
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden sticky top-6">
                <div class="px-5 py-4 border-b border-[#eaf0ec]">
                    <h3 class="text-[14px] font-bold text-green-800">Transaction Summary</h3>
                </div>
                <div class="p-5 space-y-4">
                    {{-- Student --}}
                    <div>
                        <p class="text-[11.5px] font-bold text-green-300 uppercase tracking-widest mb-1.5">Student</p>
                        <template x-if="student">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[13px] font-bold text-green-800 leading-tight" x-text="student.name"></p>
                                    <p class="text-[12px] text-green-400 font-medium" x-text="student.number"></p>
                                </div>
                            </div>
                        </template>
                        <template x-if="!student">
                            <p class="text-[13px] text-green-300 font-medium italic">Not selected</p>
                        </template>
                    </div>

                    {{-- Selected fees --}}
                    <div>
                        <p class="text-[11.5px] font-bold text-green-300 uppercase tracking-widest mb-1.5">Selected Fees</p>
                        <template x-if="selectedFees.length === 0 && selectedFines.length === 0">
                            <p class="text-[13px] text-green-300 font-medium italic">None selected</p>
                        </template>
                        <div class="space-y-1">
                            <template x-for="fee in selectedFees" :key="fee.id">
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-green-400 font-medium truncate pr-2" x-text="fee.name"></span>
                                    <span class="font-mono font-bold text-green-800 shrink-0" x-text="'₱' + parseFloat(fee.amount).toFixed(2)"></span>
                                </div>
                            </template>
                            <template x-for="fine in selectedFines" :key="fine.id">
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-amber-600 font-medium truncate pr-2" x-text="fine.eventName"></span>
                                    <span class="font-mono font-bold text-amber-700 shrink-0" x-text="'₱' + parseFloat(fine.amount).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="border-t border-[#eaf0ec] pt-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-[13px] font-bold text-green-400">Total Amount</span>
                            <span class="text-[22px] font-black text-green-600" x-text="'₱' + totalAmount().toFixed(2)"></span>
                        </div>
                        <p class="text-[12px] text-green-300 font-medium mt-0.5" x-text="paymentMethod || 'No payment method'"></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
