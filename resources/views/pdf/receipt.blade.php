<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', 'Arial', sans-serif; background: #f5f5f5; padding: 20px; }
        .receipt-container { background: white; width: 100%; max-width: 400px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0,0.1); border: 2px solid #333; }
        .header { text-align: center; padding: 20px 15px 15px; border-bottom: 2px dashed #666; }
        .logo { width: 60px; height: 60px; background: rgb(0, 73, 30); border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 24px; }
        .org-name { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .college-name { font-size: 11px; color: #555; margin-bottom: 1px; }
        .council-name { font-size: 11px; color: #555; margin-bottom: 10px; }
        .receipt-title { font-size: 16px; font-weight: bold; letter-spacing: 2px; margin-top: 8px; }
        .meta-info { padding: 12px 15px; background: #f9f9f9; border-bottom: 1px solid #ddd; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label { font-weight: 600; color: #333; }
        .meta-value { color: #555; }
        .student-info { padding: 15px; border-bottom: 2px dashed #666; }
        .info-row { display: flex; margin-bottom: 8px; font-size: 12px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 600; min-width: 100px; color: #333; }
        .info-value { color: #555; flex: 1; }
        .transaction-details { padding: 15px; border-bottom: 2px dashed #666; }
        .transaction-type { font-size: 13px; font-weight: bold; color: #1a5490; margin-bottom: 10px; text-transform: uppercase; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; }
        .detail-label { color: #555; }
        .detail-value { font-weight: 600; color: #333; }
        .amount-breakdown { margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd; }
        .breakdown-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; }
        .breakdown-row:last-child { margin-bottom: 0; }
        .breakdown-label { color: #555; }
        .breakdown-value { font-weight: 600; color: #333; }
        .balance-due { color: #d32f2f; font-weight: bold; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
        .status-full { background: rgb(0, 73, 30); color: white; }
        .status-partial { background: #ff9800; color: white; }
        .payment-section { padding: 15px; background: #f9f9f9; border-bottom: 2px solid #333; }
        .payment-method { font-size: 11px; color: #555; margin-bottom: 8px; }
        .payment-method strong { color: #333; }
        .total-section { padding: 15px; background: rgb(0, 73, 30); color: white; text-align: center; }
        .total-label { font-size: 12px; margin-bottom: 5px; letter-spacing: 1px; }
        .total-amount { font-size: 28px; font-weight: bold; letter-spacing: 1px; margin-bottom: 8px; }
        .amount-words { font-size: 11px; font-style: italic; opacity: 0.95; letter-spacing: 0.5px; }
        .officer-section { padding: 15px; border-bottom: 1px solid #ddd; }
        .officer-label { font-size: 10px; color: #666; margin-bottom: 8px; text-transform: uppercase; }
        .officer-name { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 2px; }
        .officer-role { font-size: 11px; color: #666; margin-bottom: 15px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; text-align: center; font-size: 9px; color: #999; }
        .footer { padding: 12px 15px; text-align: center; font-size: 9px; color: #999; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="logo">CMU</div>
            <div class="org-name">{{ $transaction->organization?->name }}</div>
            <div class="college-name">{{ $transaction->organization?->college?->name ?? '' }}</div>
            <div class="council-name">{{ $transaction->organization?->department?->name ?? '' }}</div>
            <div class="receipt-title">OFFICIAL RECEIPT</div>
        </div>

        <div class="meta-info">
            <div class="meta-row">
                <span class="meta-label">OR No.:</span>
                <span class="meta-value">{{ $transaction->or_number }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Date:</span>
                <span class="meta-value">{{ $transaction->created_at->format('F d, Y') }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Semester:</span>
                <span class="meta-value">{{ $transaction->academicYear?->name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Student Name:</span>
                <span class="info-value">{{ $transaction->student?->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Student No.:</span>
                <span class="info-value">{{ $transaction->student?->student_number }}</span>
            </div>
            @if($transaction->student?->program)
            <div class="info-row">
                <span class="info-label">Program:</span>
                <span class="info-value">{{ $transaction->student->program->name }}</span>
            </div>
            @endif
            @if($transaction->student?->year_level)
            <div class="info-row">
                <span class="info-label">Year Level:</span>
                <span class="info-value">{{ $transaction->student->year_level }}st Year</span>
            </div>
            @endif
        </div>

        <div class="transaction-details">
            <div class="transaction-type">{{ $transaction->feeProfile?->name ?? 'Fine / Other' }}</div>
            <div class="detail-row">
                <span class="detail-label">Fee Category:</span>
                <span class="detail-value">{{ $transaction->transaction_type === 'FINE' ? 'Fine' : 'Regular' }}</span>
            </div>
            @if($transaction->notes)
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value">{{ $transaction->notes }}</span>
            </div>
            @endif
            
            <div class="amount-breakdown">
                <div class="breakdown-row">
                    <span class="breakdown-label">Total Amount Due:</span>
                    <span class="breakdown-value">₱{{ number_format($transaction->amount_paid, 2) }}</span>
                </div>
                <div class="breakdown-row">
                    <span class="breakdown-label">Amount Paid (This Receipt):</span>
                    <span class="breakdown-value">₱{{ number_format($transaction->amount_paid, 2) }}</span>
                </div>
                <div class="breakdown-row">
                    <span class="breakdown-label">Balance Remaining:</span>
                    <span class="breakdown-value balance-due">₱0.00</span>
                </div>
                <div style="text-align: center;">
                    <span class="status-badge status-full">✓ FULLY PAID</span>
                </div>
            </div>
        </div>

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

        <div class="total-section">
            <div class="total-label">TOTAL AMOUNT</div>
            <div class="total-amount">₱{{ number_format($transaction->amount_paid, 2) }}</div>
            <div class="amount-words">{{ \App\Services\NumberToWords::convert($transaction->amount_paid) }} Only</div>
        </div>

        <div class="officer-section">
            <div class="officer-label">Received by:</div>
            <div class="officer-name">{{ $transaction->processedBy?->full_name ?? $transaction->processedBy?->username }}</div>
            <div class="officer-role">{{ $transaction->processedBy?->role ?? 'Treasurer' }} – {{ $transaction->organization?->name }}</div>
            <div class="signature-line">Signature</div>
        </div>

        <div class="footer">
            This is a system-generated receipt.<br>
            Void transactions are subject to approval.
        </div>
    </div>
</body>
</html>