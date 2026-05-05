@extends('layouts.app')
@section('title', 'Remittance Detail')
@section('page-title', 'Remittance Detail')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('org.remittances.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Remittances
        </a>
    </div>

    {{-- Status bar --}}
    <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:20px 24px;margin-bottom:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div>
                <div style="font-size:11px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Remittance</div>
                <div style="font-size:17px;font-weight:700;font-family:monospace;color:#1a7a41">REM-{{ str_pad($remittance->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div style="font-size:12px;color:#4a6356;margin-top:2px">{{ $remittance->academicYear?->name }} · Created {{ $remittance->created_at->format('M d, Y') }}</div>
            </div>
            @php
            $statusMap = [
            'PENDING' => ['bg'=>'#fdf7e3','color'=>'#b58a1a','label'=>'Pending Verification'],
            'VERIFIED' => ['bg'=>'#eff6ff','color'=>'#1d4ed8','label'=>'Verified — Awaiting Acceptance'],
            'ACCEPTED' => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'Accepted'],
            ];
            $s = $statusMap[$remittance->status] ?? ['bg'=>'#f3f4f6','color'=>'#374151','label'=>$remittance->status];
            @endphp
            <span style="display:inline-block;padding:5px 14px;border-radius:20px;font-size:12.5px;font-weight:700;background:{{ $s['bg'] }};color:{{ $s['color'] }}">{{ $s['label'] }}</span>
        </div>

        {{-- Progress steps --}}
        <div style="display:flex;align-items:center;gap:0">
            @foreach(['PENDING'=>'1. Pending','VERIFIED'=>'2. Verified','ACCEPTED'=>'3. Accepted'] as $step => $label)
            @php $done = in_array($step, match($remittance->status) { 'PENDING'=>['PENDING'], 'VERIFIED'=>['PENDING','VERIFIED'], 'ACCEPTED'=>['PENDING','VERIFIED','ACCEPTED'] }); @endphp
            <div style="flex:1;display:flex;align-items:center">
                <div style="display:flex;align-items:center;gap:6px">
                    <div style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;{{ $done ? 'background:#1a7a41;color:white' : 'background:#dde8e1;color:#8aa89a' }}">
                        {{ array_search($step, array_keys(['PENDING'=>'','VERIFIED'=>'','ACCEPTED'=>'']))+1 }}
                    </div>
                    <span style="font-size:12px;font-weight:600;color:{{ $done ? '#1a7a41' : '#8aa89a' }}">{{ $label }}</span>
                </div>
                @if($step !== 'ACCEPTED')
                <div style="flex:1;height:2px;background:{{ $done ? '#1a7a41' : '#dde8e1' }};margin:0 8px"></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Action buttons --}}
    @if(auth()->user()->canReviewRemittances() && $remittance->status === 'PENDING')
    <div style="margin-bottom:16px">
        <form method="POST" action="{{ route('org.remittances.verify', $remittance) }}" style="display:inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn-green" style="padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer">
                Mark as Verified
            </button>
        </form>
    </div>
    @elseif(auth()->user()->canReviewRemittances() && $remittance->status === 'VERIFIED')
    <div style="margin-bottom:16px">
        <form method="POST" action="{{ route('org.remittances.accept', $remittance) }}" style="display:inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn-green" style="padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer">
                Accept Remittance
            </button>
        </form>
    </div>
    @endif

    {{-- Transactions table --}}
    <div style="background:white;border-radius:12px;border:1px solid #dde8e1">
        <div style="padding:13px 20px;border-bottom:1px solid #eaf0ec;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-size:14px;font-weight:700">Included Transactions</div>
                <div style="font-size:12px;color:#8aa89a">{{ $remittance->transactions->count() }} receipts · Total ₱{{ number_format($remittance->transactions->sum('amount_paid'), 2) }}</div>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f0f3f1;border-bottom:1px solid #dde8e1">
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">OR No.</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Student</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Type</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Method</th>
                        <th style="padding:9px 13px;text-align:right;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Amount</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($remittance->transactions as $tx)
                    <tr style="border-bottom:1px solid #eaf0ec">
                        <td style="padding:9px 13px"><span style="font-family:monospace;font-size:12px;color:#1a7a41;background:#e6f4ec;padding:2px 6px;border-radius:4px">{{ $tx->or_number }}</span></td>
                        <td style="padding:9px 13px;font-size:13px;font-weight:600">{{ $tx->student?->full_name }}</td>
                        <td style="padding:9px 13px"><span style="font-size:12px;padding:2px 7px;border-radius:4px;{{ $tx->transaction_type === 'FEE' ? 'background:#dbeafe;color:#1d4ed8' : 'background:#fce7f3;color:#be185d' }}">{{ $tx->transaction_type }}</span></td>
                        <td style="padding:9px 13px;font-size:12.5px;color:#4a6356">{{ $tx->payment_method }}</td>
                        <td style="padding:9px 13px;font-size:13px;font-weight:600;text-align:right">₱{{ number_format($tx->amount_paid, 2) }}</td>
                        <td style="padding:9px 13px;font-size:12px;color:#8aa89a">{{ $tx->created_at->format('M d, H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:30px;text-align:center;font-size:13px;color:#8aa89a">No transactions in this remittance.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#f8fbf9;border-top:2px solid #dde8e1">
                        <td colspan="4" style="padding:10px 13px;font-size:13px;font-weight:700;text-align:right">Total</td>
                        <td style="padding:10px 13px;font-size:14px;font-weight:700;color:#1a7a41;text-align:right">₱{{ number_format($remittance->transactions->sum('amount_paid'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
