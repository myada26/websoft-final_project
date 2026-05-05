<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $transaction->or_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #0f1f17; background: white; }

        .receipt { width: 100%; max-width: 560px; margin: 0 auto; padding: 24px; }

        .header { text-align: center; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 2px solid #1a7a41; }
        .header .org-name { font-size: 14px; font-weight: bold; color: #0d4a1e; }
        .header .or-label { font-size: 10px; color: #8aa89a; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }
        .header .or-number { font-size: 20px; font-weight: bold; color: #1a7a41; letter-spacing: 2px; }

        .section { margin-bottom: 14px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .label { font-size: 10px; color: #8aa89a; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .value { font-size: 12px; font-weight: 600; color: #0f1f17; }

        .divider { border: none; border-top: 1px dashed #b3e5c9; margin: 12px 0; }

        .amount-box { background: #f0faf4; border: 1px solid #b3e5c9; border-radius: 6px; padding: 12px 16px; text-align: center; margin: 14px 0; }
        .amount-label { font-size: 10px; color: #4a6356; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .amount-value { font-size: 24px; font-weight: bold; color: #0d4a1e; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-cash { background: #e6f4ec; color: #1a7a41; }
        .badge-gcash { background: #eff6ff; color: #2563eb; }
        .badge-fee { background: #dbeafe; color: #1d4ed8; }
        .badge-fine { background: #fce7f3; color: #be185d; }
        .badge-void { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .footer { margin-top: 20px; padding-top: 14px; border-top: 1px solid #dde8e1; display: flex; justify-content: space-between; }
        .sig-line { width: 160px; border-top: 1px solid #0f1f17; padding-top: 4px; font-size: 10px; color: #4a6356; }
    </style>
</head>
<body>
<div class="receipt">

    {{-- Header --}}
    <div class="header">
        <div class="org-name">{{ $transaction->organization?->name }}</div>
        <div style="font-size:10px;color:#4a6356;margin-top:2px">{{ $transaction->academicYear?->name }}</div>
        <div class="or-label">Official Receipt</div>
        <div class="or-number">{{ $transaction->or_number }}</div>
        @if($transaction->voidRequest && $transaction->voidRequest->status === 'APPROVED')
        <div style="margin-top:6px"><span class="badge badge-void">⊘ VOIDED</span></div>
        @endif
    </div>

    {{-- Student info --}}
    <div class="section">
        <div class="row">
            <div>
                <div class="label">Student Name</div>
                <div class="value" style="font-size:13px">{{ $transaction->student?->full_name }}</div>
            </div>
            <div style="text-align:right">
                <div class="label">Student ID</div>
                <div class="value" style="font-family:monospace">{{ $transaction->student?->student_number }}</div>
            </div>
        </div>
    </div>

    <hr class="divider">

    {{-- Fee details --}}
    <div class="section">
        <div class="row">
            <div>
                <div class="label">Description</div>
                <div class="value">{{ $transaction->feeProfile?->name ?? 'Fine / Other' }}</div>
            </div>
            <div style="text-align:right">
                <div class="label">Type</div>
                <div><span class="badge {{ $transaction->transaction_type === 'FEE' ? 'badge-fee' : 'badge-fine' }}">{{ $transaction->transaction_type }}</span></div>
            </div>
        </div>
        @if($transaction->notes)
        <div style="margin-top:4px;font-size:10.5px;color:#4a6356">Note: {{ $transaction->notes }}</div>
        @endif
    </div>

    {{-- Amount box --}}
    <div class="amount-box">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">₱{{ number_format($transaction->amount, 2) }}</div>
    </div>

    {{-- Payment info --}}
    <div class="section">
        <div class="row">
            <div>
                <div class="label">Payment Method</div>
                <div><span class="badge {{ $transaction->payment_method === 'GCASH' ? 'badge-gcash' : 'badge-cash' }}">{{ $transaction->payment_method }}</span></div>
            </div>
            @if($transaction->payment_method === 'GCASH' && $transaction->reference_number)
            <div style="text-align:right">
                <div class="label">GCash Reference</div>
                <div class="value" style="font-family:monospace">{{ $transaction->reference_number }}</div>
            </div>
            @endif
        </div>
    </div>

    <hr class="divider">

    {{-- Meta --}}
    <div class="section">
        <div class="row">
            <div>
                <div class="label">Date & Time</div>
                <div class="value">{{ $transaction->created_at->format('F d, Y h:i A') }}</div>
            </div>
            <div style="text-align:right">
                <div class="label">Processed By</div>
                <div class="value">{{ $transaction->processedBy?->username }}</div>
            </div>
        </div>
    </div>

    {{-- Footer signatures --}}
    <div class="footer">
        <div>
            <div class="sig-line">Student Signature</div>
        </div>
        <div>
            <div class="sig-line">Treasurer / Officer</div>
        </div>
    </div>

    <div style="text-align:center;margin-top:16px;font-size:9px;color:#8aa89a">
        This is an official receipt issued by {{ $transaction->organization?->name }}.
        Keep this for your records.
    </div>

</div>
</body>
</html>
