<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt {{ $transaction->or_number }}</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; margin: 0; padding: 0; color: #333; }
.receipt { background: white; border: 1.5px solid #333; margin: 0 auto; width: 100%; max-width: 100%; }
.r-head { background: #00491E; color: white; padding: 16px 14px; text-align: center; }
.r-logo { width: 48px; height: 48px; background: #1B6332; border-radius: 24px; margin: 0 auto 9px; display: block; line-height: 48px; font-weight: 700; font-size: 15px; }
.r-uni { font-size: 9.5px; color: #EBF3E8; letter-spacing: .07em; text-transform: uppercase; }
.r-college { font-size: 11px; margin-top: 2px; }
.r-council { font-size: 11px; }
.r-title { font-size: 13px; font-weight: 700; letter-spacing: .12em; margin-top: 10px; }

.section { padding: 9px 13px; border-bottom: 1.5px dashed #ccc; }
.section.solid { border-bottom: 1px solid #ddd; }
.section.bg { background: #f9f9f7; }

table { width: 100%; border-collapse: collapse; }
td { padding: 2px 0; vertical-align: top; font-size: 11px; }
.td-right { text-align: right; }

.r-lbl { font-weight: 600; color: #444; width: 40%; }
.r-val { color: #555; }
.r-val-mono { color: #555; font-family: monospace; font-size: 11.5px; }

.txtype { font-size: 11px; font-weight: 700; color: #1A3F78; text-transform: uppercase; margin-bottom: 8px; }
.green { color: #1B6332; font-weight: 700; }
.red { color: #c62828; font-weight: 700; }

.status-pill { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 7px; text-align: center;}
.sp-full { background: #EBF3E8; color: #1E4D0F; }
.sp-partial { background: #FFF3DC; color: #7A4800; }

.r-total { padding: 14px; background: #00491E; color: white; text-align: center; }
.r-total-lbl { font-size: 10px; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 3px; }
.r-total-amt { font-size: 26px; font-weight: 700; font-family: monospace; margin-bottom: 3px; }

.r-officer { padding: 12px 13px; border-bottom: 1px solid #ddd; text-align: center; }
.r-olbl { font-size: 9px; text-transform: uppercase; letter-spacing: .07em; color: #888; margin-bottom: 5px; }
.r-oname { font-size: 12.5px; font-weight: 600; color: #222; }
.r-orole { font-size: 11px; color: #666; margin-bottom: 12px; }
.r-osig { border-top: 1px solid #444; width: 170px; margin: 0 auto; padding-top: 4px; font-size: 8.5px; color: #aaa; text-align: center; }

.r-foot { padding: 10px 13px; text-align: center; font-size: 9px; color: #aaa; line-height: 1.5; }
.text-center { text-align: center; }
</style>
</head>
<body>

@php
    $isFine = $transaction->isFine();
    $isPartial = false;
    $totalDue = 0;
    $balance = 0;
    $title = 'Membership Fee';
    
    if ($isFine) {
        $title = 'Absence Fine';
        $totalDue = $transaction->studentFine ? $transaction->studentFine->fine_amount : $transaction->amount_paid;
        $balance = $totalDue - $transaction->amount_paid;
    } else {
        $totalDue = $transaction->feeProfile ? $transaction->feeProfile->amount : $transaction->amount_paid;
        $balance = $totalDue - $transaction->amount_paid;
        if ($balance > 0) {
            $isPartial = true;
            $title = 'Membership Fee — Partial Payment';
        }
    }
@endphp

<div class="receipt">
    <div class="r-head">
        <div class="r-logo">CMU</div>
        <div class="r-uni">Central Mindanao University</div>
        <div class="r-college">{{ $transaction->organization->linkedCollege->name ?? 'College' }}</div>
        <div class="r-council">{{ $transaction->organization->name }}</div>
        <div class="r-title">OFFICIAL RECEIPT</div>
    </div>
    
    <div class="section bg">
        <table>
            <tr><td class="r-lbl">OR No.</td><td class="r-val td-right">{{ $transaction->or_number }}</td></tr>
            <tr><td class="r-lbl">Date</td><td class="r-val td-right">{{ $transaction->created_at->format('M d, Y') }}</td></tr>
            <tr><td class="r-lbl">Semester</td><td class="r-val td-right">{{ $transaction->academicYear->name ?? '' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr><td class="r-lbl">Student Name</td><td class="r-val td-right">{{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</td></tr>
            <tr><td class="r-lbl">Student No.</td><td class="r-val td-right">{{ $transaction->student->student_number }}</td></tr>
            {{-- Assuming enrollment data is accessible if needed, fallback to basic info --}}
        </table>
    </div>

    <div class="section">
        <div class="txtype">{{ $title }}</div>
        <table>
            @if(!$isFine)
                <tr><td class="r-lbl">Fee Category</td><td class="r-val td-right">{{ $transaction->feeProfile->category ?? 'Regular' }}</td></tr>
                <tr><td class="r-lbl">Description</td><td class="r-val td-right">{{ $transaction->feeProfile->name ?? 'Membership Fee' }}</td></tr>
            @else
                <tr><td class="r-lbl">Events Covered</td><td class="r-val td-right">Missed Events</td></tr>
            @endif
        </table>

        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #e5e5e5;">
            <table>
                <tr><td class="r-lbl">Total Amount Due</td><td class="r-val-mono td-right">P{{ number_format($totalDue, 2) }}</td></tr>
                <tr><td class="r-lbl">Amount Paid</td><td class="r-val-mono td-right">P{{ number_format($transaction->amount_paid, 2) }}</td></tr>
                <tr>
                    <td class="r-lbl">Balance Remaining</td>
                    <td class="r-val-mono td-right {{ $balance > 0 ? 'red' : 'green' }}">P{{ number_format($balance, 2) }}</td>
                </tr>
            </table>
            <div class="text-center">
                @if($balance > 0)
                    <span class="status-pill sp-partial">⏳ Partial Payment</span>
                @else
                    <span class="status-pill sp-full">✓ Fully Settled</span>
                @endif
            </div>
        </div>
    </div>

    <div class="section bg solid">
        <table>
            <tr><td class="r-lbl">Payment Method:</td><td class="r-val td-right">{{ ucfirst(strtolower($transaction->payment_method)) }}</td></tr>
            <tr><td class="r-lbl">Reference No.:</td><td class="r-val td-right">{{ $transaction->reference_number ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="r-total">
        <div class="r-total-lbl">Total Amount Paid</div>
        <div class="r-total-amt">P{{ number_format($transaction->amount_paid, 2) }}</div>
    </div>

    <div class="r-officer">
        <div class="r-olbl">Received by</div>
        <div class="r-oname">{{ $transaction->processedBy->first_name ?? 'Officer' }} {{ $transaction->processedBy->last_name ?? '' }}</div>
        <div class="r-orole">FCATS Officer</div>
        <div style="margin-top:15px;">
            <div class="r-osig">Signature over printed name</div>
        </div>
    </div>

    <div class="r-foot">
        This is a system-generated receipt.<br>
        @if($balance > 0)
            <strong style="color:#7A4800">Partial payment — remaining balance of P{{ number_format($balance, 2) }} must be settled.</strong><br>
        @endif
        Void transactions are subject to Chairperson approval.
    </div>
</div>

</body>
</html>
