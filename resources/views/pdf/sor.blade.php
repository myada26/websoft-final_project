<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary of Receipts</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-size:14px;color:#111;font-family:'DejaVu Sans','Arial',sans-serif;padding:0}
        .doc{padding:2rem;color:#111}
        .doc-header{text-align:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1.5px solid #111}
        .doc-header .university{font-size:11px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px}
        .doc-header .doc-title{font-size:17px;font-weight:700;letter-spacing:.02em;margin-bottom:2px}
        .doc-header .doc-subtitle{font-size:11px;color:#555}
        .doc-org{margin-bottom:1rem;padding:10px 12px;border:0.5px solid #ccc;border-radius:4px}
        .doc-org-label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#777;margin-bottom:2px}
        .doc-org-name{font-size:13px;font-weight:700}
.doc-meta{display:flex;gap:2rem;margin-bottom:1.25rem;font-size:11px;color:#555}
.doc-meta span b{color:#111}
        .summary-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:1.25rem}
        .sc{border:0.5px solid #ddd;border-radius:4px;padding:8px 10px;text-align:center}
        .sc-label{font-size:9px;text-transform:uppercase;letter-spacing:.05em;color:#777;margin-bottom:2px}
        .sc-val{font-size:16px;font-weight:700;color:#111}
        .sc-sub{font-size:10px;color:#555}
        .print-table{width:100%;border-collapse:collapse;font-size:11px;margin-bottom:1rem}
        .print-table th{background:#111;color:#fff;padding:5px 8px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
        .print-table th:nth-child(3),.print-table th:nth-child(4),.print-table td:nth-child(3),.print-table td:nth-child(4){text-align:right}
        .print-table td{padding:5px 8px;border-bottom:0.5px solid #e5e5e5;vertical-align:top}
        .print-table tr.subtotal td{background:#f5f5f5;font-weight:700;border-top:0.5px solid #bbb}
        .print-table tr.grandtotal td{background:#111;color:#fff;font-weight:700;font-size:12px}
        .type-pill{display:inline-block;font-size:9px;padding:1px 5px;border-radius:2px;font-weight:700}
        .tp-r{background:#EAF3DE;color:#27500A}
        .tp-e{background:#E6F1FB;color:#0C447C}
        .tp-f{background:#FCEBEB;color:#791F1F}
        .tp-c{background:#f0f0f0;color:#777}
        .print-sig{display:flex;justify-content:space-between;margin-top:1.5rem;padding-top:1rem;border-top:0.5px solid #ccc;font-size:11px}
        .ps-block{text-align:center;width:160px}
        .ps-line{border-top:0.5px solid #111;padding-top:4px;margin-top:28px;font-weight:700;font-size:11px}
        .ps-role{color:#777;font-size:10px}
        .doc-footer{text-align:center;font-size:9px;color:#aaa;margin-top:1rem;padding-top:8px;border-top:0.5px solid #eee}
    </style>
</head>
<body>
    <div class="doc">
        <div class="doc-header">
            <div class="university">University of the Philippines · Office of Student Affairs</div>
            <div class="doc-title">Summary of Receipts</div>
            <div class="doc-subtitle">Official financial collection report · For OSA submission</div>
        </div>

        <div class="doc-org">
            <div class="doc-org-label">Name of organization</div>
            <div class="doc-org-name">{{ $organization->name }}</div>
        </div>

        <div class="doc-meta">
            <span><b>Academic Year:</b> {{ $academicYear->name }}</span>
            <span><b>Semester:</b> {{ $semesterLabel }}</span>
            <span><b>Total Members:</b> {{ $totalMembers }}</span>
            <span><b>Date Prepared:</b> {{ now()->format('F d, Y') }}</span>
        </div>

        <div class="doc-meta" style="margin-bottom:1rem">
            <span><b>Regular fee rate:</b> ₱{{ number_format($regularRate, 2) }}</span>
            <span><b>Extendee fee rate:</b> ₱{{ number_format($extendeeRate, 2) }}</span>
            <span><b>OR series:</b> {{ $orRange }}</span>
        </div>

        <div class="summary-cards">
            <div class="sc"><div class="sc-label">Receipts issued</div><div class="sc-val">{{ $receiptCount }}</div></div>
            <div class="sc"><div class="sc-label">Total collected</div><div class="sc-val">₱{{ number_format($totalCollected, 0) }}</div></div>
            <div class="sc"><div class="sc-label">Regular</div><div class="sc-val">{{ $regularCount - $extendeeCount }}</div><div class="sc-sub">₱{{ number_format($regularPaidAmount, 0) }}</div></div>
            <div class="sc"><div class="sc-label">Extendee</div><div class="sc-val">{{ $extendeeCount }}</div><div class="sc-sub">₱{{ number_format($extendeeAmount, 0) }}</div></div>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th style="width:90px">OR range</th>
                    <th>Breakdown</th>
                    <th style="width:80px">Receipts</th>
                    <th style="width:90px">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    @if($batch['extendee'] > 0)
                    <tr>
                        <td rowspan="2" style="vertical-align:middle;font-weight:700">{{ $batch['range'] }}</td>
                        <td><span class="type-pill tp-r">Regular</span> {{ $batch['regular'] }} receipts</td>
                        <td style="text-align:right">{{ $batch['regular'] }}</td>
                        <td style="text-align:right">₱{{ number_format(($batch['regular'] / max($batch['count'], 1)) * $batch['subtotal'], 2) }}</td>
                    </tr>
                    <tr>
                        <td><span class="type-pill tp-e">Extendee</span> {{ $batch['extendee'] }} receipts</td>
                        <td style="text-align:right">{{ $batch['extendee'] }}</td>
                        <td style="text-align:right">₱{{ number_format(($batch['extendee'] / max($batch['count'], 1)) * $batch['subtotal'], 2) }}</td>
                    </tr>
                    @else
                    <tr>
                        <td style="font-weight:700">{{ $batch['range'] }}</td>
                        <td><span class="type-pill tp-r">Regular</span> {{ $batch['regular'] }} receipts</td>
                        <td style="text-align:right">{{ $batch['regular'] }}</td>
                        <td style="text-align:right">₱{{ number_format($batch['subtotal'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="subtotal">
                        <td></td>
                        <td>Batch total</td>
                        <td style="text-align:right">{{ $batch['count'] }}</td>
                        <td style="text-align:right">₱{{ number_format($batch['subtotal'], 2) }}</td>
                    </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:16px">No transactions recorded.</td>
                </tr>
                @endforelse
                @if(count($batches) > 0)
                <tr class="grandtotal">
                    <td colspan="2">Overall total</td>
                    <td style="text-align:right">{{ $receiptCount }}</td>
                    <td style="text-align:right">₱{{ number_format($totalCollected, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="print-sig">
            <div class="ps-block">
                <div class="ps-line">{{ auth()->user()->student?->full_name ?? auth()->user()->username }}</div>
                <div class="ps-role">Treasurer · {{ $organization->name }}</div>
            </div>
            <div class="ps-block">
                <div class="ps-line">_______________________</div>
                <div class="ps-role">Checked by · OSA Representative</div>
            </div>
            <div class="ps-block">
                <div class="ps-line">_______________________</div>
                <div class="ps-role">Noted by · Adviser / Dean</div>
            </div>
        </div>

        <div class="doc-footer">Generated by FCATS · Fee Collection and Tracking System · Printed on {{ now()->format('F d, Y') }} · This document is system-generated and valid without a wet signature if transmitted electronically to OSA.</div>
    </div>
</body>
</html>