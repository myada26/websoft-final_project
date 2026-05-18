<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary of Receipts</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-size: 14px; color: #111; font-family: 'DejaVu Sans', Arial, sans-serif; padding: 0; }

        .doc { padding: 2rem; color: #111; }

        /* ── Header ── */
        .doc-header { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1.5px solid #111; }
        .doc-header .university { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 4px; }
        .doc-header .doc-title { font-size: 17px; font-weight: 700; letter-spacing: .02em; margin-bottom: 2px; }
        .doc-header .doc-subtitle { font-size: 11px; color: #555; }

        /* ── Org block ── */
        .doc-org { margin-bottom: 1rem; padding: 10px 12px; border: 0.5px solid #ccc; }
        .doc-org-label { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #777; margin-bottom: 2px; }
        .doc-org-name { font-size: 13px; font-weight: 700; }

        /* ── Meta table ── */
        .doc-meta-table { width: 100%; font-size: 11px; color: #555; margin-bottom: 1.25rem; border-collapse: collapse; }
        .doc-meta-table td { padding: 0 8px 0 0; white-space: nowrap; }
        .doc-meta-table b { color: #111; }

        /* ── Summary cards (4-column table) ── */
        .cards-table { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 1.25rem; }
        .sc { border: 0.5px solid #ddd; padding: 8px 10px; text-align: center; }
        .sc-label { font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #777; margin-bottom: 2px; }
        .sc-val { font-size: 16px; font-weight: 700; color: #111; }
        .sc-sub { font-size: 10px; color: #555; }

        /* ── Batch detail table ── */
        .print-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 1rem; }
        .print-table th { background: #111; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .print-table td { padding: 5px 8px; border-bottom: 0.5px solid #e5e5e5; vertical-align: top; }
        .print-table tr.subtotal td { background: #f5f5f5; font-weight: 700; border-top: 0.5px solid #bbb; }
        .print-table tr.grandtotal td { background: #111; color: #fff; font-weight: 700; font-size: 12px; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: 700; }

        /* ── Type pills ── */
        .type-pill { display: inline-block; font-size: 9px; padding: 1px 5px; border-radius: 2px; font-weight: 700; }
        .tp-r { background: #EAF3DE; color: #27500A; }
        .tp-e { background: #E6F1FB; color: #0C447C; }
        .tp-f { background: #FCEBEB; color: #791F1F; }

        /* ── Signature (3-column table) ── */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; padding-top: 1rem; border-top: 0.5px solid #ccc; font-size: 11px; }
        .sig-table td { text-align: center; vertical-align: bottom; padding: 0 10px; }
        .ps-line { border-top: 0.5px solid #111; padding-top: 4px; margin-top: 28px; font-weight: 700; font-size: 11px; }
        .ps-role { color: #777; font-size: 10px; margin-top: 2px; }

        /* ── Footer ── */
        .doc-footer { text-align: center; font-size: 9px; color: #aaa; margin-top: 1rem; padding-top: 8px; border-top: 0.5px solid #eee; }
    </style>
</head>
<body>
<div class="doc">

    {{-- Header --}}
    <div class="doc-header">
        <div class="university">Central Mindanao University &middot; Office of Student Affairs</div>
        <div class="doc-title">Summary of Receipts</div>
        <div class="doc-subtitle">Official financial collection report &middot; For OSA submission</div>
    </div>

    {{-- Org block --}}
    <div class="doc-org">
        <div class="doc-org-label">Name of organization</div>
        <div class="doc-org-name">{{ $organization->name }}</div>
    </div>

    {{-- Meta row 1 --}}
    <table class="doc-meta-table">
        <tr>
            <td><b>Academic Year:</b> {{ $academicYear->name }}</td>
            <td><b>Semester:</b> {{ $semesterLabel }}</td>
            <td><b>Total Members:</b> {{ $totalMembers }}</td>
            <td><b>Date Prepared:</b> {{ now()->format('F d, Y') }}</td>
        </tr>
    </table>

    {{-- Meta row 2 --}}
    <table class="doc-meta-table" style="margin-bottom:1rem">
        <tr>
            <td><b>Regular fee rate:</b> &#x20B1;{{ number_format($regularRate, 2) }}</td>
            <td><b>Extendee fee rate:</b> &#x20B1;{{ number_format($extendeeRate, 2) }}</td>
            <td><b>OR series:</b> {{ $orRange }}</td>
            <td></td>
        </tr>
    </table>

    {{-- Summary cards --}}
    <table class="cards-table">
        <tr>
            <td class="sc">
                <div class="sc-label">Receipts issued</div>
                <div class="sc-val">{{ $receiptCount }}</div>
            </td>
            <td class="sc">
                <div class="sc-label">Total collected</div>
                <div class="sc-val">&#x20B1;{{ number_format($totalCollected, 0) }}</div>
            </td>
            <td class="sc">
                <div class="sc-label">Regular</div>
                <div class="sc-val">{{ $regularCount - $extendeeCount }}</div>
                <div class="sc-sub">&#x20B1;{{ number_format($regularPaidAmount, 0) }}</div>
            </td>
            <td class="sc">
                <div class="sc-label">Extendee</div>
                <div class="sc-val">{{ $extendeeCount }}</div>
                <div class="sc-sub">&#x20B1;{{ number_format($extendeeAmount, 0) }}</div>
            </td>
        </tr>
    </table>

    {{-- Batch detail table --}}
    <table class="print-table">
        <thead>
            <tr>
                <th style="width:110px">OR range</th>
                <th>Breakdown</th>
                <th style="width:80px;text-align:right">Receipts</th>
                <th style="width:100px;text-align:right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($batches as $batch)
                @if($batch['extendee'] > 0)
                <tr>
                    <td rowspan="{{ ($batch['fine'] > 0 ? 3 : 2) }}" style="vertical-align:middle;font-weight:700">{{ $batch['range'] }}</td>
                    <td><span class="type-pill tp-r">Regular</span> {{ $batch['regular'] }} receipts</td>
                    <td class="text-right">{{ $batch['regular'] }}</td>
                    <td class="text-right">&#x20B1;{{ number_format($batch['regular_amount'], 2) }}</td>
                </tr>
                <tr>
                    <td><span class="type-pill tp-e">Extendee</span> {{ $batch['extendee'] }} receipts</td>
                    <td class="text-right">{{ $batch['extendee'] }}</td>
                    <td class="text-right">&#x20B1;{{ number_format($batch['extendee_amount'], 2) }}</td>
                </tr>
                @else
                <tr>
                    <td rowspan="{{ ($batch['fine'] > 0 ? 2 : 1) }}" style="font-weight:700">{{ $batch['range'] }}</td>
                    <td><span class="type-pill tp-r">Regular</span> {{ $batch['regular'] }} receipts</td>
                    <td class="text-right">{{ $batch['regular'] }}</td>
                    <td class="text-right">&#x20B1;{{ number_format($batch['regular_amount'], 2) }}</td>
                </tr>
                @endif
                @if($batch['fine'] > 0)
                <tr>
                    <td><span class="type-pill tp-f">Fine</span> {{ $batch['fine'] }} receipts</td>
                    <td class="text-right">{{ $batch['fine'] }}</td>
                    <td class="text-right">&#x20B1;{{ number_format($batch['fine_amount'], 2) }}</td>
                </tr>
                @endif
                <tr class="subtotal">
                    <td></td>
                    <td>Batch total</td>
                    <td class="text-right">{{ $batch['count'] }}</td>
                    <td class="text-right">&#x20B1;{{ number_format($batch['subtotal'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:16px">No transactions recorded.</td>
                </tr>
            @endforelse
            @if(count($batches) > 0)
            <tr class="grandtotal">
                <td colspan="2">Overall total</td>
                <td class="text-right">{{ $receiptCount }}</td>
                <td class="text-right">&#x20B1;{{ number_format($totalCollected, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Signatures --}}
    @php
        $treasurerName = $treasurer?->student?->full_name ?? $treasurer?->username ?? '— no treasurer assigned —';
    @endphp
    <table class="sig-table">
        <tr>
            <td>
                <div class="ps-line">{{ $treasurerName }}</div>
                <div class="ps-role">Treasurer &middot; {{ $organization->name }}</div>
            </td>
            <td>
                <div class="ps-line">_______________________</div>
                <div class="ps-role">Checked by &middot; OSA Representative</div>
            </td>
            <td>
                <div class="ps-line">_______________________</div>
                <div class="ps-role">Noted by &middot; Adviser / Dean</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="doc-footer">Generated by FCATS &middot; Fee Collection and Tracking System &middot; Printed on {{ now()->format('F d, Y') }} &middot; This document is system-generated and valid without a wet signature if transmitted electronically to OSA.</div>

</div>
</body>
</html>
