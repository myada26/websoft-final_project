<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Courier New', monospace;
    font-size: 9pt;
    width: 226pt;
    padding: 8pt 10pt;
    color: #000;
  }
  .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6pt; margin-bottom: 6pt; }
  .org-name { font-size: 11pt; font-weight: bold; }
  .org-type { font-size: 8pt; text-transform: uppercase; }
  .receipt-title { font-size: 10pt; font-weight: bold; margin: 4pt 0 2pt; letter-spacing: 2pt; }
  .or-number { font-size: 9pt; }
  .section { margin-bottom: 4pt; }
  .row { display: flex; justify-content: space-between; margin: 1pt 0; }
  .label { color: #555; }
  .divider { border-top: 1px dashed #000; margin: 5pt 0; }
  .amount-row { font-size: 11pt; font-weight: bold; }
  .footer { text-align: center; font-size: 7.5pt; color: #555; margin-top: 6pt; border-top: 1px dashed #000; padding-top: 5pt; }
  .status-badge {
    display: inline-block;
    background: #000;
    color: #fff;
    padding: 1pt 4pt;
    font-size: 7pt;
    letter-spacing: 1pt;
  }
</style>
</head>
<body>

<div class="header">
  <div class="org-name">{{ $transaction->organization->name ?? 'ORGANIZATION' }}</div>
  <div class="org-type">Official Receipt</div>
  <div class="receipt-title">OFFICIAL RECEIPT</div>
  <div class="or-number">OR No: <strong>{{ $transaction->or_number }}</strong></div>
</div>

<div class="section">
  <div class="row">
    <span class="label">Student No.</span>
    <span>{{ $transaction->student->student_number }}</span>
  </div>
  <div class="row">
    <span class="label">Name</span>
    <span>{{ $transaction->student->last_name }}, {{ $transaction->student->first_name }}</span>
  </div>
</div>

<div class="divider"></div>

<div class="section">
  <div class="row">
    <span class="label">Date</span>
    <span>{{ $transaction->created_at->format('m/d/Y h:i A') }}</span>
  </div>
  <div class="row">
    <span class="label">Academic Year</span>
    <span>{{ $transaction->academicYear->label ?? $transaction->academicYear->school_year ?? '' }}</span>
  </div>
  <div class="row">
    <span class="label">Type</span>
    <span>{{ $transaction->transaction_type }}</span>
  </div>
  <div class="row">
    <span class="label">Payment</span>
    <span>{{ $transaction->payment_method }}</span>
  </div>
  @if($transaction->reference_number)
  <div class="row">
    <span class="label">GCash Ref</span>
    <span>{{ $transaction->reference_number }}</span>
  </div>
  @endif
</div>

<div class="divider"></div>

<div class="section">
  <div class="row amount-row">
    <span>TOTAL PAID</span>
    <span>₱ {{ number_format((float)$transaction->amount_paid, 2) }}</span>
  </div>
</div>

@if($transaction->is_void)
<div style="text-align:center; margin-top: 4pt;">
  <span class="status-badge">VOIDED</span>
</div>
@endif

<div class="footer">
  Processed by: {{ $transaction->processedBy?->username ?? 'N/A' }}<br>
  {{ $transaction->organization->name ?? '' }}<br>
  This receipt is system-generated.
</div>

</body>
</html>
