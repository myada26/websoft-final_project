<div class="max-w-5xl mx-auto pb-10">

    {{-- Page Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('org.dashboard') }}"
           class="p-2 rounded-lg text-green-300 hover:bg-white hover:text-green-600 border-2 border-transparent hover:border-green-200 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-[22px] font-bold text-green-800">New Transaction</h2>
            <p class="text-[13.5px] text-green-400 mt-0.5 font-medium">Point-of-sale fee collection · 4-step process</p>
        </div>
    </div>

    {{-- Step Progress Indicator --}}
    <div class="flex items-center gap-0 mb-8 px-2">
        @foreach([
            ['num' => 1, 'label' => 'Find Student'],
            ['num' => 2, 'label' => 'Select Fee'],
            ['num' => 3, 'label' => 'Payment'],
            ['num' => 4, 'label' => 'Confirmation'],
        ] as $s)
        <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center" style="min-width:60px">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-[14px] font-black transition-all
                    {{ $step > $s['num'] ? 'bg-green-600 text-white' : ($step === $s['num'] ? 'bg-yellow-400 text-green-800' : 'bg-white border-2 border-green-200 text-green-300') }}">
                    @if($step > $s['num'])
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $s['num'] }}
                    @endif
                </div>
                <span class="text-[11.5px] font-bold mt-1.5 whitespace-nowrap
                    {{ $step === $s['num'] ? 'text-green-600' : 'text-green-300' }}">{{ $s['label'] }}</span>
            </div>
            @if(!$loop->last)
            <div class="flex-1 h-[2px] mx-2 mt-[-18px] {{ $step > $s['num'] ? 'bg-green-600' : 'bg-green-200' }}"></div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="flex gap-6 items-start">

        {{-- ── Step Panels ── --}}
        <div class="flex-1 min-w-0">

            {{-- STEP 1 — Find Student --}}
            @if($step === 1)
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 1 — Find Student</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Type at least 2 characters to search</p>
                </div>
                <div class="p-6">
                    {{-- Search Input --}}
                    <div class="relative mb-4" x-data>
                        <svg class="w-5 h-5 text-green-300 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input
                            wire:model.live.debounce.300ms="studentQuery"
                            type="text"
                            placeholder="e.g. 2024000012 or Juan Dela Cruz"
                            autocomplete="off"
                            class="w-full pl-11 pr-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors"
                        >
                        @if($studentQuery)
                        <button type="button" wire:click="$set('studentQuery', '')" wire:click.stop
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-green-300 hover:text-green-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        @endif
                    </div>

                    {{-- Loading indicator --}}
                    <div wire:loading wire:target="studentQuery" class="flex items-center gap-2 text-[13px] text-green-400 font-medium mb-3">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Searching…
                    </div>

                    {{-- Results --}}
                    @if(count($searchResults) > 0)
                    <div class="space-y-2" wire:loading.remove wire:target="studentQuery">
                        @foreach($searchResults as $sr)
                        <button type="button" wire:click="selectStudent({{ $sr['id'] }})"
                                class="w-full flex items-center gap-4 p-4 rounded-lg border-2 border-green-200 hover:border-green-600 hover:bg-green-50 transition-all text-left cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[14px] font-bold text-green-800">{{ $sr['name'] }}</p>
                                <p class="text-[12.5px] text-green-400 font-medium">
                                    {{ $sr['number'] }}{{ $sr['program'] ? ' · ' . $sr['program'] : '' }}
                                </p>
                            </div>
                            @if($sr['hasPaid'])
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Paid
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#fef9c3] text-[#ca8a04] text-[11.5px] font-bold shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#eab308]"></span> Pending
                            </span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    @elseif(strlen(trim($studentQuery)) >= 2)
                    <div wire:loading.remove wire:target="studentQuery"
                         class="text-center py-10 text-[14px] font-semibold text-green-400">
                        No students found for "{{ $studentQuery }}"
                    </div>
                    @else
                    <div class="text-center py-12">
                        <svg class="w-14 h-14 text-green-300 opacity-20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-[14px] font-semibold text-green-400">Search for a student to begin</p>
                        <p class="text-[12.5px] text-green-300 mt-1 font-medium">Enter a student number or full name above</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- STEP 2 — Select Fee --}}
            @if($step === 2)
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 2 — Select Fee Type</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Choose one fee option for this transaction (FR-0017)</p>
                </div>
                <div class="p-6 space-y-3">

                    {{-- Membership Fee (Regular) --}}
                    @if(isset($feeProfiles['REGULAR']))
                    <button type="button" wire:click="selectFeeType('REGULAR')"
                            class="w-full flex items-center justify-between p-4 rounded-lg border-2 transition-all text-left
                                {{ $feeType === 'REGULAR' ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-400' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all
                                {{ $feeType === 'REGULAR' ? 'border-green-600 bg-green-600' : 'border-green-300 bg-white' }}">
                                @if($feeType === 'REGULAR')
                                <div class="w-2 h-2 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-green-800">{{ $feeProfiles['REGULAR']['name'] }}</p>
                                <p class="text-[12px] text-green-400 font-medium">Regular enrolled student · Read-only amount</p>
                            </div>
                        </div>
                        <span class="font-mono text-[15px] font-black text-green-800">
                            ₱{{ number_format($feeProfiles['REGULAR']['amount'], 2) }}
                        </span>
                    </button>
                    @endif

                    {{-- Extendee Fee --}}
                    @if(isset($feeProfiles['EXTENDEE']))
                    <button type="button" wire:click="selectFeeType('EXTENDEE')"
                            class="w-full flex items-center justify-between p-4 rounded-lg border-2 transition-all text-left
                                {{ $feeType === 'EXTENDEE' ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-400' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all
                                {{ $feeType === 'EXTENDEE' ? 'border-green-600 bg-green-600' : 'border-green-300 bg-white' }}">
                                @if($feeType === 'EXTENDEE')
                                <div class="w-2 h-2 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <div>
                                <p class="text-[14px] font-bold text-green-800">{{ $feeProfiles['EXTENDEE']['name'] }}</p>
                                <p class="text-[12px] text-green-400 font-medium">Extendee / overloading student · Read-only amount</p>
                            </div>
                        </div>
                        <span class="font-mono text-[15px] font-black text-green-800">
                            ₱{{ number_format($feeProfiles['EXTENDEE']['amount'], 2) }}
                        </span>
                    </button>
                    @endif

                    {{-- Irregular Fee Options --}}
                    @if(count($irregularProfiles) > 0)
                    <div class="space-y-2">
                        <div class="text-[12px] font-black uppercase tracking-widest text-green-400 px-1">Irregular Rates</div>
                        @foreach($irregularProfiles as $profile)
                        <button type="button" wire:click="selectFeeProfile({{ $profile['id'] }})"
                                class="w-full flex items-center justify-between p-4 rounded-lg border-2 transition-all text-left
                                    {{ $feeProfileId === $profile['id'] ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-400' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all
                                    {{ $feeProfileId === $profile['id'] ? 'border-green-600 bg-green-600' : 'border-green-300 bg-white' }}">
                                    @if($feeProfileId === $profile['id'])
                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[14px] font-bold text-green-800">{{ $profile['name'] }}</p>
                                    <p class="text-[12px] text-green-400 font-medium">Irregular student option · Read-only amount</p>
                                </div>
                            </div>
                            <span class="font-mono text-[15px] font-black text-green-800">
                                ₱{{ number_format($profile['amount'], 2) }}
                            </span>
                        </button>
                        @endforeach
                    </div>
                    @endif

                    {{-- Partial Payment (Treasurer only) --}}
                    @if(false)
                    <button type="button" wire:click="selectFeeType('PARTIAL')"
                            class="w-full flex items-start gap-3 p-4 rounded-lg border-2 transition-all text-left
                                {{ $feeType === 'PARTIAL' ? 'border-amber-500 bg-amber-50' : 'border-amber-200 hover:border-amber-400' }}">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 transition-all
                            {{ $feeType === 'PARTIAL' ? 'border-amber-500 bg-amber-500' : 'border-amber-300 bg-white' }}">
                            @if($feeType === 'PARTIAL')
                            <div class="w-2 h-2 rounded-full bg-white"></div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-[14px] font-bold text-amber-800">Partial Payment</p>
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 text-[10.5px] font-bold uppercase tracking-wide">Treasurer Only</span>
                            </div>
                            <p class="text-[12px] text-amber-600 font-medium mt-0.5">
                                Manual amount · max ₱{{ number_format($this->maxPartialAmount, 2) }}
                            </p>
                            @if($feeType === 'PARTIAL')
                            <div class="mt-3">
                                <label class="block text-[12px] font-semibold text-amber-700 mb-1.5">Amount to Collect</label>
                                <input
                                    wire:model.live="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $this->maxPartialAmount }}"
                                    placeholder="0.00"
                                    class="w-full px-4 py-2.5 border-2 border-amber-300 rounded-lg bg-white text-[14px] font-bold font-mono text-amber-800 outline-none focus:border-amber-500 transition-colors"
                                    onclick="event.stopPropagation()"
                                >
                            </div>
                            @endif
                        </div>
                    </button>
                    @endif

                    {{-- Fine Payment --}}
                    <button type="button"
                            @if(!$fineWindowOpen) disabled @endif
                            wire:click="{{ $fineWindowOpen ? 'selectFeeType(\'FINE\')' : '' }}"
                            class="w-full flex items-start gap-3 p-4 rounded-lg border-2 transition-all text-left
                                {{ !$fineWindowOpen ? 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' : ($feeType === 'FINE' ? 'border-red-500 bg-red-50' : 'border-red-200 hover:border-red-400') }}">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 transition-all
                            {{ $feeType === 'FINE' ? 'border-red-500 bg-red-500' : 'border-red-300 bg-white' }}">
                            @if($feeType === 'FINE')
                            <div class="w-2 h-2 rounded-full bg-white"></div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-[14px] font-bold {{ $fineWindowOpen ? 'text-red-800' : 'text-gray-500' }}">Fine Payment</p>
                                @if(!$fineWindowOpen)
                                <span class="px-2 py-0.5 rounded-md bg-gray-200 text-gray-500 text-[10.5px] font-bold uppercase tracking-wide">Window Closed</span>
                                @endif
                            </div>
                            @if(count($unpaidFines) > 0)
                            <p class="text-[12px] text-red-500 font-medium mt-0.5">
                                Outstanding: ₱{{ number_format(collect($unpaidFines)->sum('amount'), 2) }}
                                ({{ count($unpaidFines) }} fine{{ count($unpaidFines) > 1 ? 's' : '' }})
                            </p>
                            @else
                            <p class="text-[12px] text-gray-400 font-medium mt-0.5">No outstanding fines recorded</p>
                            @endif

                            @if($feeType === 'FINE')
                            <div class="mt-3 space-y-2">
                                {{-- Fine list --}}
                                @foreach($unpaidFines as $fine)
                                <div class="flex justify-between text-[12.5px] py-1 border-b border-red-100">
                                    <span class="text-red-700 font-medium">{{ $fine['eventName'] }}</span>
                                    <span class="font-mono font-bold text-red-800">₱{{ number_format($fine['amount'], 2) }}</span>
                                </div>
                                @endforeach
                                <div class="mt-2">
                                    <label class="block text-[12px] font-semibold text-red-700 mb-1.5">Amount to Pay (full balance required)</label>
                                    <input
                                        wire:model.live="amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        readonly
                                        placeholder="0.00"
                                        class="w-full px-4 py-2.5 border-2 border-red-300 rounded-lg bg-red-50 text-[14px] font-bold font-mono text-red-800 outline-none"
                                        onclick="event.stopPropagation()"
                                    >
                                </div>
                            </div>
                            @endif
                        </div>
                    </button>

                    {{-- Nav --}}
                    <div class="flex justify-between items-center pt-4 mt-2 border-t border-[#eaf0ec]">
                        <button type="button" wire:click="goToStep(1)"
                                class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                            ← Back
                        </button>
                        <button type="button"
                                wire:click="{{ ($feeType && $this->amountFloat > 0) ? 'goToStep(3)' : '' }}"
                                @if(!$feeType || $this->amountFloat <= 0) disabled @endif
                                class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Continue →
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- STEP 3 — Payment Method --}}
            @if($step === 3)
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 3 — Payment Method</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Select how the student is paying</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" wire:click="$set('paymentMethod', 'CASH')"
                                class="flex flex-col items-center gap-2 p-5 rounded-lg border-2 transition-all cursor-pointer
                                    {{ $paymentMethod === 'CASH' ? 'border-green-600 bg-[#f0f9f4]' : 'border-green-200 hover:border-green-600' }}">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                            <span class="text-[14px] font-bold text-green-800">Cash</span>
                        </button>
                        <button type="button" wire:click="$set('paymentMethod', 'GCASH')"
                                class="flex flex-col items-center gap-2 p-5 rounded-lg border-2 transition-all cursor-pointer
                                    {{ $paymentMethod === 'GCASH' ? 'border-[#2563eb] bg-blue-50' : 'border-green-200 hover:border-[#2563eb]' }}">
                            <svg class="w-8 h-8 text-[#2563eb]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-[14px] font-bold text-green-800">GCash</span>
                        </button>
                    </div>

                    @if($paymentMethod === 'GCASH')
                    <div>
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">
                            GCash Reference Number <span class="text-red-500">*</span>
                        </label>
                        <input wire:model.live="gcashRef" type="text" placeholder="e.g. 1234567890123"
                               class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-green-50 text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors">
                    </div>
                    @endif

                    <div>
                        <label class="block text-[13px] font-semibold text-green-400 mb-2">
                            Remarks <span class="text-[11px] font-normal text-green-300 ml-1">(Optional)</span>
                        </label>
                        <textarea wire:model="remarks" rows="2" placeholder="e.g. Installment payment, senior discount, etc."
                                  class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors resize-none"></textarea>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#eaf0ec]">
                        <button type="button" wire:click="goToStep(2)"
                                class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                            ← Back
                        </button>
                        @php
                            $canProceed = $paymentMethod && ($paymentMethod !== 'GCASH' || $gcashRef);
                        @endphp
                        <button type="button"
                                wire:click="{{ $canProceed ? 'goToStep(4)' : '' }}"
                                @if(!$canProceed) disabled @endif
                                class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            Review →
                        </button>
                    </div>
                </div>
            </div>
            @endif

            {{-- STEP 4 — Confirm & Submit --}}
            @if($step === 4)
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-[#eaf0ec]">
                    <h3 class="text-[17px] font-bold text-green-800">Step 4 — Confirm &amp; Issue Receipt</h3>
                    <p class="text-[13px] text-green-400 mt-0.5 font-medium">Review the transaction before posting</p>
                </div>
                <div class="p-6">
                    <div class="bg-green-50 rounded-lg border border-[#eaf0ec] p-5 space-y-3 mb-6">
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Student</span>
                            <span class="font-bold text-green-800">{{ $selectedStudent['name'] ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Student No.</span>
                            <span class="font-mono font-bold text-green-600">{{ $selectedStudent['number'] ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Fee Type</span>
                            <span class="font-bold text-green-800">{{ $this->selectedFeeName }}</span>
                        </div>
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">Payment Method</span>
                            <span class="font-bold text-green-800">{{ $paymentMethod }}</span>
                        </div>
                        @if($paymentMethod === 'GCASH')
                        <div class="flex justify-between text-[13.5px]">
                            <span class="text-green-400 font-semibold">GCash Ref</span>
                            <span class="font-mono font-bold text-[#2563eb]">{{ $gcashRef }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-[16px] font-black border-t border-green-200 pt-3">
                            <span class="text-green-800">Total</span>
                            <span class="text-green-600">₱{{ number_format($this->amountFloat, 2) }}</span>
                        </div>
                    </div>

                    {{-- Standard form POST — routes based on transaction type --}}
                    @if($transactionType === 'FINE')
                    <form method="POST" action="{{ route('org.transactions.fine') }}">
                        @csrf
                        <input type="hidden" name="student_id"      value="{{ $selectedStudentId }}">
                        <input type="hidden" name="payment_method"  value="{{ $paymentMethod }}">
                        <input type="hidden" name="gcash_reference" value="{{ $gcashRef }}">
                        <input type="hidden" name="amount_paid"     value="{{ $this->amountFloat }}">
                        <input type="hidden" name="student_fine_id" value="{{ $studentFineId }}">
                        <input type="hidden" name="remarks"         value="{{ $remarks }}">
                        <div class="flex justify-between items-center">
                            <button type="button" wire:click="goToStep(3)"
                                    class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                                ← Back
                            </button>
                            <button type="submit"
                                    class="px-6 py-3 rounded-lg text-[14px] font-black bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-md shadow-green-600/20 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Confirm &amp; Issue Receipt
                            </button>
                        </div>
                    </form>
                    @else
                    <form method="POST" action="{{ route('org.transactions.store') }}">
                        @csrf
                        <input type="hidden" name="student_id"      value="{{ $selectedStudentId }}">
                        <input type="hidden" name="payment_method"  value="{{ $paymentMethod }}">
                        <input type="hidden" name="gcash_reference" value="{{ $gcashRef }}">
                        <input type="hidden" name="amount_paid"     value="{{ $this->amountFloat }}">
                        <input type="hidden" name="fee_profile_ids[]" value="{{ $feeProfileId }}">
                        <input type="hidden" name="remarks"         value="{{ $remarks }}">
                        <div class="flex justify-between items-center">
                            <button type="button" wire:click="goToStep(3)"
                                    class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                                ← Back
                            </button>
                            <button type="submit"
                                    class="px-6 py-3 rounded-lg text-[14px] font-black bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-md shadow-green-600/20 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Confirm &amp; Issue Receipt
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── Summary Sidebar ── --}}
        <div class="w-72 shrink-0 hidden xl:block">
            <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden sticky top-6">
                <div class="px-5 py-4 border-b border-[#eaf0ec]">
                    <h3 class="text-[14px] font-bold text-green-800">Transaction Summary</h3>
                </div>
                <div class="p-5 space-y-4">

                    <div>
                        <p class="text-[11.5px] font-bold text-green-300 uppercase tracking-widest mb-1.5">Student</p>
                        @if($selectedStudent)
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[13px] font-bold text-green-800 leading-tight">{{ $selectedStudent['name'] }}</p>
                                <p class="text-[12px] text-green-400 font-medium">{{ $selectedStudent['number'] }}</p>
                            </div>
                        </div>
                        @else
                        <p class="text-[13px] text-green-300 font-medium italic">Not selected</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-[11.5px] font-bold text-green-300 uppercase tracking-widest mb-1.5">Fee</p>
                        @if($feeType)
                        <p class="text-[13px] font-bold text-green-800">{{ $this->selectedFeeName }}</p>
                        @else
                        <p class="text-[13px] text-green-300 font-medium italic">Not selected</p>
                        @endif
                    </div>

                    <div class="border-t border-[#eaf0ec] pt-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-[13px] font-bold text-green-400">Total Amount</span>
                            <span class="text-[22px] font-black text-green-600">
                                ₱{{ $this->amountFloat > 0 ? number_format($this->amountFloat, 2) : '—' }}
                            </span>
                        </div>
                        <p class="text-[12px] text-green-300 font-medium mt-0.5">
                            {{ $paymentMethod ?: 'No payment method' }}
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
