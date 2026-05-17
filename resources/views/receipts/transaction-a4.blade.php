<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; padding: 20pt; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2pt solid #166534; padding-bottom: 12pt; margin-bottom: 14pt; }
  .org-info h1 { font-size: 15pt; color: #166534; margin-bottom: 2pt; }
  .org-info p  { font-size: 9pt; color: #555; }
  .receipt-meta { text-align: right; }
  .receipt-meta .title { font-size: 13pt; font-weight: bold; letter-spacing: 1pt; color: #166534; }
  .receipt-meta .or-number { font-size: 11pt; margin-top: 4pt; }
  .receipt-meta .date { font-size: 9pt; color: #666; }
  .section-title { font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; color: #888; font-weight: bold; margin: 12pt 0 5pt; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4pt 20pt; }
  .info-item { display: flex; flex-direction: column; }
  .info-item .lbl { font-size: 8pt; color: #888; margin-bottom: 1pt; }
  .info-item .val { font-size: 10pt; }
  .divider { border-top: 1pt solid #e5e7eb; margin: 12pt 0; }
  .amount-box { background: #f0fdf4; border: 1pt solid #86efac; border-radius: 4pt; padding: 10pt 14pt; display: flex; justify-content: space-between; align-items: center; margin-top: 10pt; }
  .amount-box .lbl { font-size: 10pt; color: #166534; font-weight: bold; }
  .amount-box .val { font-size: 16pt; font-weight: bold; color: #166534; }
  .footer { margin-top: 20pt; border-top: 1pt solid #e5e7eb; padding-top: 10pt; display: flex; justify-content: space-between; font-size: 8pt; color: #888; }
  .void-banner { text-align: center; background: #fef2f2; border: 1pt solid #fca5a5; color: #dc2626; font-weight: bold; font-size: 12pt; padding: 6pt; margin: 10pt 0; letter-spacing: 3pt; border-radius: 3pt; }
</style>
</head>
<body>

<div class="header">
  <div class="org-info">
    <h1>{{ $transaction->organization->name ?? 'ORGANIZATION' }}</h1>
    <p>Official Receipt · Fee Collection and Tracking System</p>
  </div>
  <div class="receipt-meta">
    <div class="title">OFFICIAL RECEIPT</div>
    <div class="or-number">OR No: <strong>{{ $transaction->or_number }}</strong></div>
    <div class="date">{{ $transaction->created_at->format('F d, Y · h:i A') }}</div>
  </div>
</div>

@if($transaction->is_void)
<div class="void-banner">⚠ VOIDED TRANSACTION</div>
@endif

<div class="section-title">Student Information</div>
<div class="info-grid">
  <div class="info-item">
    <span class="lbl">Student ID Number</span>
    <span class="val">{{ $transaction->student->student_number }}</span>
  </div>
  <div class="info-item">
    <span class="lbl">Full Name</span>
    <span class="val">{{ $transaction->student->last_name }}, {{ $transaction->student->first_name }}
      @if($transaction->student->name_extension) {{ $transaction->student->name_extension }}@endif</span>
  </div>
</div>

<div class="divider"></div>

<div class="section-title">Transaction Details</div>
<div class="info-grid">
  <div class="info-item">
    <span class="lbl">Academic Year / Semester</span>
    <span class="val">{{ $transaction->academicYear->label ?? $transaction->academicYear->school_year ?? 'N/A' }}</span>
  </div>
  <div class="info-item">
    <span class="lbl">Transaction Type</span>
    <span class="val">{{ $transaction->transaction_type }}</span>
  </div>
  <div class="info-item">
    <span class="lbl">Payment Method</span>
    <span class="val">{{ $transaction->payment_method }}</span>
  </div>
  @if($transaction->reference_number)
  <div class="info-item">
    <span class="lbl">GCash Reference No.</span>
    <span class="val">{{ $transaction->reference_number }}</span>
  </div>
  @endif
  <div class="info-item">
    <span class="lbl">Processed By</span>
    <span class="val">{{ $transaction->processedBy?->username ?? 'N/A' }}</span>
  </div>
  <div class="info-item">
    <span class="lbl">Organization</span>
    <span class="val">{{ $transaction->organization->name ?? 'N/A' }}</span>
  </div>
</div>

<div class="amount-box">
  <span class="lbl">TOTAL AMOUNT PAID</span>
  <span class="val">₱ {{ number_format((float)$transaction->amount_paid, 2) }}</span>
</div>

<div class="footer">
  <span>This receipt is system-generated and valid without a physical signature.</span>
  <span>FCATS · Fee Collection and Tracking System</span>
</div>

</body>
</html>
