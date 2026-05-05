@extends('layouts.app')
@section('title', 'Receipt')
@section('page-title', 'Transaction Receipt')

@section('content')
<div>
    <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between">
        <a href="{{ auth()->user()->canCreateTransactions() ? route('org.transactions.create') : route('org.transactions.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            {{ auth()->user()->canCreateTransactions() ? 'New Transaction' : 'Transaction History' }}
        </a>
        <a href="{{ route('org.transactions.show', $transaction) }}?print=1" target="_blank" class="btn-outline" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:1.5px solid #dde8e1;color:#4a6356">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9" />
                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" />
            </svg>
            Print Receipt
        </a>
    </div>

    <div style="max-width:560px">
        {{-- OR Header --}}
        <div style="background:white;border-radius:12px;border:2px solid #1a7a41;padding:24px 28px;box-shadow:0 2px 8px rgba(26,122,65,.12);margin-bottom:16px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <div>
                    <div style="font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.06em">Official Receipt</div>
                    <div style="font-size:22px;font-weight:700;color:#1a7a41;font-family:monospace">{{ $transaction->or_number }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:11px;color:#8aa89a">{{ $transaction->created_at->format('F d, Y') }}</div>
                    <div style="font-size:11px;color:#8aa89a">{{ $transaction->created_at->format('h:i A') }}</div>
                    @if($transaction->voidRequest && $transaction->voidRequest->status === 'APPROVED')
                    <div style="margin-top:4px">
                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">VOIDED</span>
                    </div>
                    @endif
                </div>
            </div>

            <div style="border-top:1px dashed #b3e5c9;padding-top:16px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Student</div>
                        <div style="font-size:13.5px;font-weight:700">{{ $transaction->student?->full_name }}</div>
                        <div style="font-size:11.5px;color:#4a6356;font-family:monospace">{{ $transaction->student?->student_number }}</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Organization</div>
                        <div style="font-size:13px;font-weight:600">{{ $transaction->organization?->name }}</div>
                        <div style="font-size:11.5px;color:#4a6356">{{ $transaction->academicYear?->name }}</div>
                    </div>
                </div>

                <div style="background:#f8fbf9;border-radius:8px;padding:14px 16px;margin-bottom:14px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <div>
                            <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Description</div>
                            <div style="font-size:13.5px;font-weight:600">
                                {{ $transaction->feeProfile?->name ?? 'Fine / Other' }}
                            </div>
                            <div style="font-size:11.5px;color:#4a6356;margin-top:2px">
                                {{ $transaction->transaction_type === 'FEE' ? 'Student Fee' : 'Fine' }}
                                @if($transaction->notes) · {{ $transaction->notes }} @endif
                            </div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Amount</div>
                            <div style="font-size:22px;font-weight:700;color:#0f1f17">₱{{ number_format($transaction->amount, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px">
                    <div>
                        <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Payment Method</div>
                        <span style="display:inline-block;padding:3px 9px;border-radius:20px;font-size:12px;font-weight:700;{{ $transaction->payment_method === 'GCASH' ? 'background:#eff6ff;color:#2563eb' : 'background:#e6f4ec;color:#1a7a41' }}">{{ $transaction->payment_method }}</span>
                    </div>
                    @if($transaction->payment_method === 'GCASH' && $transaction->reference_number)
                    <div>
                        <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">GCash Ref #</div>
                        <div style="font-size:13px;font-weight:600;font-family:monospace">{{ $transaction->reference_number }}</div>
                    </div>
                    @endif
                    <div>
                        <div style="font-size:10.5px;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px">Processed By</div>
                        <div style="font-size:13px;font-weight:600">{{ $transaction->processedBy?->username }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Void request section --}}
        @if(auth()->user()->canRequestVoid() && !($transaction->voidRequest && $transaction->voidRequest->status === 'APPROVED'))
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:16px 20px">
            <div style="font-size:13px;font-weight:700;margin-bottom:10px">Void Request</div>
            @if($transaction->voidRequest)
            <div style="font-size:13px;color:#4a6356">
                Status: <span style="font-weight:600;color:{{ $transaction->voidRequest->status === 'PENDING' ? '#d4a42a' : '#dc2626' }}">{{ $transaction->voidRequest->status }}</span>
                · Reason: {{ $transaction->voidRequest->reason }}
            </div>
            @else
            <form method="POST" action="{{ route('org.void-requests.store') }}" style="display:flex;gap:8px;align-items:flex-end">
                @csrf
                <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                <div style="flex:1">
                    <input type="text" name="reason" placeholder="State reason for void..." required
                        style="width:100%;padding:8px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
                </div>
                <button type="submit" class="btn-danger-soft" style="padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;border:1.5px solid #fca5a5;color:#dc2626;background:white;cursor:pointer;white-space:nowrap">
                    Request Void
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
