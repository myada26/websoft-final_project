<div>

    {{-- ── Search field ── --}}
    <div style="margin-bottom:18px">
        <label style="display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:7px;padding-left:2px">
            Student ID Number
        </label>
        <div style="position:relative;display:flex;align-items:center;background:rgba(255,255,255,0.40);border:1.5px solid {{ $errors->has('studentNumber') ? '#f97066' : 'rgba(255,255,255,0.60)' }};border-radius:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.04);transition:border-color .2s">
            <span style="display:flex;align-items:center;padding-left:14px;color:#9ca3af;flex-shrink:0" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </span>
            <input
                type="text"
                id="portal-student-number"
                wire:model="studentNumber"
                wire:keydown.enter="checkStatus"
                placeholder="e.g. 2024-000001"
                autocomplete="off"
                autocapitalize="none"
                spellcheck="false"
                style="flex:1;border:0;outline:none;background:transparent;padding:14px 14px;font-family:inherit;font-size:14.5px;font-weight:400;color:#111827;letter-spacing:0.01em;min-width:0">
        </div>
        @error('studentNumber')
        <div style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:#b42318;font-weight:500">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $message }}</span>
        </div>
        @enderror
    </div>

    {{-- ── Check button ── --}}
    <button type="button"
        wire:click="checkStatus"
        wire:loading.attr="disabled"
        style="width:100%;padding:14px 24px;border:0;border-radius:1rem;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-family:inherit;font-size:15px;font-weight:500;letter-spacing:0.03em;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 4px 20px rgba(22,163,74,0.28);transition:transform .2s,box-shadow .2s">
        <span wire:loading.remove wire:target="checkStatus">Check Fee Status</span>
        <span wire:loading wire:target="checkStatus">Checking…</span>
        <svg wire:loading.remove wire:target="checkStatus" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
        </svg>
    </button>

    {{-- ── Results panel ── --}}
    @if($searched)
    <div style="margin-top:24px;border-top:1px solid rgba(0,0,0,0.07);padding-top:22px">

        {{-- Not found --}}
        @if($notFound)
        <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fef9ec;border:1px solid #fde68a;border-radius:12px;color:#92400e;font-size:13px;font-weight:500">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink:0;margin-top:1px">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            <span>No student found for <strong>{{ $studentNumber }}</strong>. Please check the ID and try again.</span>
        </div>

        {{-- No active semester --}}
        @elseif($studentInfo && empty($feeData) && empty($finesData) && !$semesterName)
        <div style="background:rgba(255,255,255,0.50);border:1px solid rgba(0,0,0,0.08);border-radius:10px;padding:14px 18px;font-size:13px;color:#6b7280">
            No active semester configured. Please check back later.
        </div>

        {{-- Found — render results --}}
        @elseif($studentInfo)

        {{-- Student identity card --}}
        <div style="background:rgba(255,255,255,0.55);border:1.5px solid rgba(255,255,255,0.75);border-radius:14px;padding:14px 18px;margin-bottom:18px;backdrop-filter:blur(8px);box-shadow:0 2px 10px rgba(0,0,0,0.05)">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap">
                <div>
                    <div style="font-size:15.5px;font-weight:700;color:#111827;letter-spacing:-.01em">{{ $studentInfo['full_name'] }}</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:3px">
                        {{ $studentInfo['student_number'] }}
                        @if($studentInfo['program'] !== '—')&nbsp;·&nbsp;{{ $studentInfo['program'] }}@endif
                        @if($studentInfo['year_level'] !== '—')&nbsp;·&nbsp;{{ $studentInfo['year_level'] }}@endif
                    </div>
                </div>
                @if($semesterName)
                <span style="font-size:10.5px;font-weight:700;letter-spacing:.10em;text-transform:uppercase;color:#15803d;background:rgba(22,163,74,0.10);padding:4px 10px;border-radius:20px;white-space:nowrap;border:1px solid rgba(22,163,74,0.20)">
                    {{ $semesterName }}
                </span>
                @endif
            </div>
        </div>

        {{-- ── Fee status ── --}}
        @if(!empty($feeData))
        <div style="margin-bottom:18px">
            <div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:10px">Membership Fee Status</div>

            @foreach($feeData as $fee)
            @php
                $cfg = match($fee['status']) {
                    'PAID'    => ['bg'=>'rgba(220,252,231,0.70)','border'=>'#bbf7d0','color'=>'#15803d','label'=>'Fully Paid','icon'=>'M9 12l2 2 4-4'],
                    'PARTIAL' => ['bg'=>'rgba(254,249,236,0.70)','border'=>'#fde68a','color'=>'#92400e','label'=>'Partial',   'icon'=>'M12 8v4l3 3'],
                    default   => ['bg'=>'rgba(254,226,226,0.70)','border'=>'#fca5a5','color'=>'#dc2626','label'=>'Unpaid',    'icon'=>'M18 6 6 18M6 6l12 12'],
                };
            @endphp
            <div style="border:1px solid {{ $cfg['border'] }};background:{{ $cfg['bg'] }};border-radius:12px;padding:14px 16px;margin-bottom:10px;backdrop-filter:blur(6px)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                    <div>
                        <div style="font-size:13.5px;font-weight:700;color:#111827">{{ $fee['fee_name'] }}</div>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px">{{ $fee['org_name'] }} &middot; Rate: ₱{{ number_format($fee['fee_rate'], 2) }}</div>
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:{{ $cfg['color'] }};padding:4px 11px;border-radius:20px;background:rgba(255,255,255,.55)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $cfg['icon'] }}"/></svg>
                        {{ $cfg['label'] }}
                    </span>
                </div>
                @if($fee['status'] === 'PAID' || $fee['status'] === 'PARTIAL')
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid {{ $cfg['border'] }};display:flex;gap:16px;flex-wrap:wrap;font-size:12px">
                    <span style="color:#6b7280">Paid: <strong style="color:#111827">₱{{ number_format($fee['paid'], 2) }}</strong></span>
                    @if($fee['status'] === 'PARTIAL')
                    <span style="color:#6b7280">Balance: <strong style="color:#dc2626">₱{{ number_format($fee['balance'], 2) }}</strong></span>
                    @endif
                    @if(!empty($fee['receipts']))
                    <span style="color:#6b7280">OR: <strong style="color:#111827">{{ implode(', ', $fee['receipts']) }}</strong></span>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- ── Attendance fines ── --}}
        <div>
            <div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:10px">Attendance Fines — {{ $semesterName }}</div>

            @if(empty($finesData))
            <div style="background:rgba(220,252,231,0.70);border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;font-size:13px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                No attendance fines on record this semester.
            </div>
            @else
            <div style="border:1px solid rgba(0,0,0,0.08);border-radius:12px;overflow:hidden;margin-bottom:12px;background:rgba(255,255,255,0.45);backdrop-filter:blur(6px)">
                <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;min-width:420px;font-size:12.5px">
                    <thead>
                        <tr style="background:rgba(255,255,255,0.55)">
                            <th style="padding:10px 12px;text-align:left;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(0,0,0,0.07)">Event</th>
                            <th style="padding:10px 12px;text-align:left;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(0,0,0,0.07)">Date</th>
                            <th style="padding:10px 12px;text-align:center;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(0,0,0,0.07)">Missed</th>
                            <th style="padding:10px 12px;text-align:right;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(0,0,0,0.07)">Fine</th>
                            <th style="padding:10px 12px;text-align:center;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid rgba(0,0,0,0.07)">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($finesData as $fine)
                        <tr style="border-bottom:1px solid rgba(0,0,0,0.06)">
                            <td style="padding:10px 12px;font-weight:600;color:#111827">
                                {{ $fine['event_name'] }}
                                <div style="font-size:11px;font-weight:400;color:#9ca3af">{{ $fine['org_name'] }}</div>
                            </td>
                            <td style="padding:10px 12px;color:#6b7280;white-space:nowrap">{{ $fine['event_date'] }}</td>
                            <td style="padding:10px 12px;text-align:center;color:#111827">
                                {{ $fine['slots_missed'] }}<span style="color:#9ca3af">/{{ $fine['total_slots'] }}</span>
                            </td>
                            <td style="padding:10px 12px;text-align:right;font-weight:700;color:{{ $fine['status'] === 'UNPAID' ? '#dc2626' : '#111827' }}">
                                ₱{{ number_format($fine['fine_amount'], 2) }}
                            </td>
                            <td style="padding:10px 12px;text-align:center">
                                @if($fine['status'] === 'PAID')
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(220,252,231,0.80);color:#15803d">Paid</span>
                                @if($fine['or_number'])
                                <div style="font-size:10px;color:#9ca3af;margin-top:2px">{{ $fine['or_number'] }}</div>
                                @endif
                                @else
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(254,226,226,0.80);color:#dc2626">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            {{-- Total outstanding --}}
            @if($totalUnpaidFines > 0)
            <div style="background:rgba(254,249,236,0.75);border:1.5px solid #fde68a;border-radius:12px;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;backdrop-filter:blur(6px)">
                <div style="font-size:13px;color:#92400e;font-weight:500;display:flex;align-items:center;gap:6px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Total outstanding fine balance
                </div>
                <div style="font-size:17px;font-weight:800;color:#dc2626">₱{{ number_format($totalUnpaidFines, 2) }}</div>
            </div>
            <p style="font-size:11.5px;color:#9ca3af;margin-top:8px;text-align:center;line-height:1.5">
                Please approach your organization Treasurer to settle your fine balance.
            </p>
            @else
            <div style="background:rgba(220,252,231,0.70);border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;font-size:13px;color:#15803d;font-weight:600;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                All fines settled — no outstanding balance.
            </div>
            @endif

            @endif
        </div>

        @endif {{-- end studentInfo --}}
    </div>
    @endif {{-- end searched --}}

    <p style="margin-top:22px;font-size:11.5px;color:#9ca3af;text-align:center;line-height:1.5">
        This view is read-only and for inquiry purposes only.<br>
        Showing records for the active semester only.
    </p>

</div>
