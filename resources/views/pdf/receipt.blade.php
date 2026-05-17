<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Receipt – {{ $transaction->or_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .receipt-container {
            background: white;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 2px solid #333;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            text-align: center;
            padding: 20px 15px 15px;
            border-bottom: 2px dashed #666;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgb(0, 73, 30);
            border-radius: 30px;
            margin: 0 auto 10px;
            color: white;
            font-weight: bold;
            font-size: 22px;
            line-height: 60px;
            text-align: center;
        }

        .org-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .college-name {
            font-size: 11px;
            color: #555;
            margin-bottom: 1px;
        }

        .council-name {
            font-size: 11px;
            color: #555;
            margin-bottom: 10px;
        }

        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 8px;
        }

        /* ── Meta Info ──────────────────────────────────────── */
        .meta-info {
            padding: 12px 15px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }

        .meta-row {
            overflow: hidden;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .meta-row:last-child { margin-bottom: 0; }

        .meta-label { font-weight: bold; color: #333; }

        .meta-value { float: right; color: #555; }

        /* ── Student Info ───────────────────────────────────── */
        .student-info {
            padding: 15px;
            border-bottom: 2px dashed #666;
        }

        .info-row {
            overflow: hidden;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .info-row:last-child { margin-bottom: 0; }

        .info-label {
            font-weight: bold;
            color: #333;
            float: left;
            width: 100px;
        }

        .info-value {
            color: #555;
            margin-left: 105px;
        }

        /* ── Transaction Details ────────────────────────────── */
        .transaction-details {
            padding: 15px;
            border-bottom: 2px dashed #666;
        }

        .transaction-type {
            font-size: 13px;
            font-weight: bold;
            color: #1a5490;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .detail-row {
            overflow: hidden;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .detail-row:last-child { margin-bottom: 0; }

        .detail-label { color: #555; }

        .detail-value { float: right; font-weight: bold; color: #333; }

        /* ── Amount Breakdown ───────────────────────────────── */
        .amount-breakdown {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
        }

        .breakdown-row {
            overflow: hidden;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .breakdown-row:last-child { margin-bottom: 0; }

        .breakdown-label { color: #555; }

        .breakdown-value { float: right; font-weight: bold; color: #333; }

        .balance-due { color: #d32f2f; font-weight: bold; }

        .status-badge-wrap { text-align: center; margin-top: 8px; }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-full    { background: rgb(0, 73, 30); color: white; }
        .status-partial { background: #ff9800; color: white; }

        /* ── Payment Section ────────────────────────────────── */
        .payment-section {
            padding: 15px;
            background: #f9f9f9;
            border-bottom: 2px solid #333;
        }

        .payment-method {
            font-size: 11px;
            color: #555;
            margin-bottom: 8px;
        }

        .payment-method:last-child { margin-bottom: 0; }

        .payment-method strong { color: #333; }

        /* ── Total Section ──────────────────────────────────── */
        .total-section {
            padding: 15px;
            background: rgb(0, 73, 30);
            color: white;
            text-align: center;
        }

        .total-label {
            font-size: 12px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .total-amount {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .amount-words {
            font-size: 11px;
            font-style: italic;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        /* ── Officer Section ────────────────────────────────── */
        .officer-section {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .officer-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .officer-name {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }

        .officer-role {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 0 auto;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
            color: #999;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .footer {
            padding: 12px 15px;
            text-align: center;
            font-size: 9px;
            color: #999;
            line-height: 1.4;
        }
    </style>
</head>
<body>
<div class="receipt-container">

    {{-- Header --}}
    <div class="header">
        <div class="logo">CMU</div>
        <div class="org-name">Central Mindanao University</div>
        @if($transaction->organization?->college?->name)
        <div class="college-name">{{ $transaction->organization->college->name }}</div>
        @elseif($transaction->organization?->department?->college?->name)
        <div class="college-name">{{ $transaction->organization->department->college->name }}</div>
        @endif
        <div class="council-name">{{ $transaction->organization?->name }}</div>
        <div class="receipt-title">OFFICIAL RECEIPT</div>
    </div>

    {{-- Meta Info --}}
    <div class="meta-info">
        <div class="meta-row">
            <span class="meta-value">{{ $transaction->or_number }}</span>
            <span class="meta-label">OR No.:</span>
        </div>
        <div class="meta-row">
            <span class="meta-value">{{ $transaction->created_at->format('F d, Y') }}</span>
            <span class="meta-label">Date:</span>
        </div>
        <div class="meta-row">
            <span class="meta-value">{{ $transaction->academicYear?->name ?? 'N/A' }}</span>
            <span class="meta-label">Semester:</span>
        </div>
    </div>

    {{-- Student Info --}}
    <div class="student-info">
        <div class="info-row">
            <span class="info-label">Student Name:</span>
            <span class="info-value">{{ $transaction->student?->full_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Student No.:</span>
            <span class="info-value">{{ $transaction->student?->student_number }}</span>
        </div>
        @if($transaction->student?->latestEnrollment?->program?->name)
        <div class="info-row">
            <span class="info-label">Program:</span>
            <span class="info-value">{{ $transaction->student->latestEnrollment->program->name }}</span>
        </div>
        @endif
        @if($transaction->student?->latestEnrollment?->year_level)
        <div class="info-row">
            <span class="info-label">Year Level:</span>
            <span class="info-value">
                @php
                    $yr = $transaction->student->latestEnrollment->year_level;
                    $suffix = match((int)$yr) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
                @endphp
                {{ $yr }}{{ $suffix }} Year
            </span>
        </div>
        @endif
    </div>

    {{-- Transaction Details --}}
    <div class="transaction-details">
        <div class="transaction-type">
            {{ $transaction->feeProfile?->name ?? ($transaction->transaction_type === 'FINE' ? 'Fine Payment' : 'Fee Payment') }}
        </div>

        <div class="detail-row">
            <span class="detail-value">
                @if($transaction->transaction_type === 'FINE') Fine
                @elseif($transaction->feeProfile?->category === 'EXTENDEE') Extendee
                @elseif($transaction->feeProfile?->category === 'IRREGULAR') Irregular
                @else Regular
                @endif
            </span>
            <span class="detail-label">Fee Category:</span>
        </div>

        @if($transaction->feeProfile?->name)
        <div class="detail-row">
            <span class="detail-value">{{ $transaction->feeProfile->name }}</span>
            <span class="detail-label">Description:</span>
        </div>
        @endif

        {{-- Amount Breakdown --}}
        @php
            $feeTotal = $transaction->feeProfile ? (float) $transaction->feeProfile->amount : (float) $transaction->amount_paid;
            $amountPaid = (float) $transaction->amount_paid;
            $balance = max(0, $feeTotal - $amountPaid);
            $isFullyPaid = $balance <= 0;
        @endphp
        <div class="amount-breakdown">
            <div class="breakdown-row">
                <span class="breakdown-value">&#x20B1;{{ number_format($feeTotal, 2) }}</span>
                <span class="breakdown-label">Total Amount Due:</span>
            </div>
            <div class="breakdown-row">
                <span class="breakdown-value">&#x20B1;{{ number_format($amountPaid, 2) }}</span>
                <span class="breakdown-label">Amount Paid (This Receipt):</span>
            </div>
            <div class="breakdown-row">
                <span class="breakdown-value {{ !$isFullyPaid ? 'balance-due' : '' }}">&#x20B1;{{ number_format($balance, 2) }}</span>
                <span class="breakdown-label">Balance Remaining:</span>
            </div>
            <div class="status-badge-wrap">
                @if($isFullyPaid)
                <span class="status-badge status-full">FULLY PAID</span>
                @else
                <span class="status-badge status-partial">PARTIAL PAYMENT</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment Section --}}
    <div class="payment-section">
        <div class="payment-method">
            <strong>Payment Method:</strong> {{ $transaction->payment_method }}
        </div>
        @if($transaction->reference_number)
        <div class="payment-method">
            <strong>Reference No.:</strong> {{ $transaction->reference_number }}
        </div>
        @endif
    </div>

    {{-- Total Section --}}
    <div class="total-section">
        <div class="total-label">TOTAL AMOUNT</div>
        <div class="total-amount">&#x20B1;{{ number_format($amountPaid, 2) }}</div>
        <div class="amount-words">{{ \App\Services\NumberToWords::convert($amountPaid) }} Only</div>
    </div>

    {{-- Officer Section --}}
    @php
        $officerName = $transaction->processedBy?->student?->full_name
            ?? $transaction->processedBy?->username
            ?? '—';
        $officerRole = match($transaction->processedBy?->role) {
            'TREASURER'   => 'Treasurer',
            'CHAIRPERSON' => 'Chairperson',
            'COLLECTOR'   => 'Collector',
            'SECRETARY'   => 'Secretary',
            'AUDITOR'     => 'Auditor',
            default       => $transaction->processedBy?->role ?? 'Officer',
        };
    @endphp
    <div class="officer-section">
        <div class="officer-label">Received by:</div>
        <div class="officer-name">{{ $officerName }}</div>
        <div class="officer-role">{{ $officerRole }} – {{ $transaction->organization?->name }}</div>
        <div class="signature-line">Signature</div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        This is a system-generated receipt.<br>
        Void transactions are subject to approval.
    </div>

</div>
</body>
</html>
