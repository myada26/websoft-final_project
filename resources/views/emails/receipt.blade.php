<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; background: #f5f5f5; padding: 20px; }
        .receipt-container { background: white; width: 100%; max-width: 500px; margin: 0 auto; border: 2px solid #333; }
        .header { text-align: center; padding: 20px 15px; border-bottom: 2px dashed #666; }
        .org-name { font-size: 16px; font-weight: bold; text-transform: uppercase; color: #00491e; }
        .receipt-title { font-size: 18px; font-weight: bold; letter-spacing: 2px; margin-top: 10px; }
        .meta-info { padding: 12px 15px; background: #f9f9f9; border-bottom: 1px solid #ddd; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 12px; }
        .meta-label { font-weight: bold; }
        .student-info { padding: 15px; border-bottom: 2px dashed #666; }
        .info-row { display: flex; margin-bottom: 6px; font-size: 13px; }
        .info-label { font-weight: bold; min-width: 100px; }
        .transaction-details { padding: 15px; border-bottom: 2px dashed #666; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
        .total-section { padding: 20px; background: #00491e; color: white; text-align: center; }
        .total-label { font-size: 12px; letter-spacing: 1px; }
        .total-amount { font-size: 28px; font-weight: bold; margin: 8px 0; }
        .payment-section { padding: 15px; background: #f9f9f9; border-bottom: 2px solid #333; }
        .officer-section { padding: 15px; border-bottom: 1px solid #ddd; }
        .signature-line { border-top: 1px solid #333; width: 180px; margin: 10px 0; padding-top: 5px; text-align: center; font-size: 10px; }
        .footer { padding: 15px; text-align: center; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="org-name">{{ $transaction->organization?->name }}</div>
            <div class="receipt-title">OFFICIAL RECEIPT</div>
        </div>

        <div class="meta-info">
            <div class="meta-row">
                <span class="meta-label">OR No.:</span>
                <span>{{ $transaction->or_number }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Date:</span>
                <span>{{ $transaction->created_at->format('F d, Y h:i A') }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Semester:</span>
                <span>{{ $transaction->academicYear?->name }}</span>
            </div>
        </div>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Student Name:</span>
                <span>{{ $transaction->student?->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Student No.:</span>
                <span>{{ $transaction->student?->student_number }}</span>
            </div>
            @if($transaction->student?->email)
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span>{{ $transaction->student?->email }}</span>
            </div>
            @endif
        </div>

        <div class="transaction-details">
            <div class="detail-row">
                <span style="font-weight:bold;color:#1a5490;text-transform:uppercase;">{{ $transaction->transaction_type }}</span>
            </div>
            <div class="detail-row">
                <span>Description:</span>
                <span>{{ $transaction->feeProfile?->name ?? 'Fine / Other' }}</span>
            </div>
            <div class="detail-row">
                <span>Amount Paid:</span>
                <span style="font-weight:bold;">₱{{ number_format($transaction->amount_paid, 2) }}</span>
            </div>
        </div>

        <div class="payment-section">
            <div class="detail-row">
                <span>Payment Method:</span>
                <span>{{ $transaction->payment_method }}</span>
            </div>
            @if($transaction->payment_method === 'GCASH' && $transaction->reference_number)
            <div class="detail-row">
                <span>GCash Reference:</span>
                <span>{{ $transaction->reference_number }}</span>
            </div>
            @endif
        </div>

        <div class="total-section">
            <div class="total-label">TOTAL AMOUNT</div>
            <div class="total-amount">₱{{ number_format($transaction->amount_paid, 2) }}</div>
        </div>

        <div class="officer-section">
            <div style="font-size:11px;color:#666;">Received by:</div>
            <div style="font-weight:bold;">{{ $transaction->processedBy?->username }}</div>
            <div style="font-size:11px;color:#666;">{{ $transaction->processedBy?->role }}</div>
            <div class="signature-line">Signature</div>
        </div>

        <div class="footer">
            This is a system-generated receipt.<br>
            Thank you for your payment.
        </div>
    </div>
</body>
</html>