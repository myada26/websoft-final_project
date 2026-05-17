<!DOCTYPE html>
{{-- [AI Narrator] DomPDF template — inline CSS only, no flexbox, no Tailwind, Arial font --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ── Header ──────────────────────────────────────────────── */
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 20px 24px;
            margin-bottom: 18px;
        }
        .header-title {
            font-size: 9px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #93b8d8;
            margin-bottom: 4px;
        }
        .header-org {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-meta {
            font-size: 11px;
            color: #93b8d8;
        }

        /* ── Stat tables ─────────────────────────────────────────── */
        .stat-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .stat-cell {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 10px 14px;
            vertical-align: top;
        }
        .stat-label {
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 17px;
            font-weight: bold;
            color: #111827;
        }

        /* ── Section headings ────────────────────────────────────── */
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin-bottom: 10px;
            margin-top: 16px;
        }

        /* ── Charts ──────────────────────────────────────────────── */
        .chart-box {
            border: 1px solid #e5e7eb;
            background-color: #fafafa;
            padding: 10px;
            margin-bottom: 12px;
            text-align: center;
        }
        .chart-box img {
            max-width: 100%;
            height: auto;
        }
        .chart-placeholder {
            font-size: 11px;
            color: #9ca3af;
            font-style: italic;
            padding: 16px 0;
        }

        /* ── AI Narrative ────────────────────────────────────────── */
        .narrative-box {
            background-color: #e8f0fe;
            border-left: 3px solid #3a7bd5;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .narrative-label {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }
        .narrative-text {
            font-size: 12px;
            color: #1e293b;
            line-height: 1.7;
        }

        /* ── Footer ──────────────────────────────────────────────── */
        .footer {
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            font-style: italic;
        }
        .footer-name {
            margin-bottom: 3px;
        }
        .footer-disclaimer {
            color: #b0b7c3;
        }
    </style>
</head>
<body>

    {{-- ── 1. HEADER ─────────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-title">AI Financial Report</div>
        <div class="header-org">{{ $org->name }}</div>
        <div class="header-meta">
            {{ $semester?->name ?? 'N/A' }}&nbsp;&nbsp;·&nbsp;&nbsp;Generated: {{ $generatedAt }}
        </div>
    </div>

    {{-- ── 2. STAT ROW — Total Collected | Transactions | Fee | Fine ──────────── --}}
    <div class="section-heading">Collection Summary</div>
    <table class="stat-table">
        <tr>
            <td class="stat-cell" style="width:25%">
                <div class="stat-label">Total Collected</div>
                <div class="stat-value">&#8369;{{ number_format((float) $totalCollected, 2) }}</div>
            </td>
            <td class="stat-cell" style="width:25%">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value">{{ number_format($txnCount) }}</div>
            </td>
            <td class="stat-cell" style="width:25%">
                <div class="stat-label">Fee Transactions</div>
                <div class="stat-value">{{ number_format($feeCount) }}</div>
            </td>
            <td class="stat-cell" style="width:25%">
                <div class="stat-label">Fine Transactions</div>
                <div class="stat-value">{{ number_format($fineCount) }}</div>
            </td>
        </tr>
    </table>

    {{-- ── 3. PAYMENT BREAKDOWN ROW — Cash | GCash ────────────────────────────── --}}
    <div class="section-heading">Payment Breakdown</div>
    <table class="stat-table">
        <tr>
            <td class="stat-cell" style="width:50%">
                <div class="stat-label">Cash Collected</div>
                <div class="stat-value">&#8369;{{ number_format((float) $cashAmount, 2) }}</div>
            </td>
            <td class="stat-cell" style="width:50%">
                <div class="stat-label">GCash Collected</div>
                <div class="stat-value">&#8369;{{ number_format((float) $gcashAmount, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Unremitted note --}}
    @if($unremittedCount > 0)
    <p style="font-size:11px;color:#b45309;margin-bottom:12px;">
        &#9888; {{ number_format($unremittedCount) }} transaction(s) have not yet been remitted.
    </p>
    @endif

    {{-- ── 4. COLLECTION CHART ─────────────────────────────────────────────────── --}}
    <div class="section-heading">Monthly Collection Trend</div>
    <div class="chart-box">
        @if(!empty($charts['collection']))
            <img src="{{ $charts['collection'] }}" alt="Monthly Collection Chart">
        @else
            <div class="chart-placeholder">Chart not available</div>
        @endif
    </div>

    {{-- ── 5. PAYMENT METHOD CHART ─────────────────────────────────────────────── --}}
    <div class="section-heading">Payment Method Breakdown</div>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:25%"></td>
            <td style="width:50%;text-align:center;">
                <div class="chart-box">
                    @if(!empty($charts['payment']))
                        <img src="{{ $charts['payment'] }}" alt="Payment Method Chart">
                    @else
                        <div class="chart-placeholder">Chart not available</div>
                    @endif
                </div>
            </td>
            <td style="width:25%"></td>
        </tr>
    </table>

    {{-- ── 6. AI NARRATIVE SECTION ─────────────────────────────────────────────── --}}
    <div class="section-heading">AI Financial Narrative</div>
    <div class="narrative-box">
        <div class="narrative-label">AI Financial Narrative</div>
        <div class="narrative-text">{!! nl2br(e($aiInsight)) !!}</div>
    </div>

    {{-- ── 7. FOOTER ───────────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-name">
            Generated by: {{ $officer->name ?? $officer->username }} ({{ $officer->role }})
            &nbsp;·&nbsp; {{ $generatedAt }}
        </div>
        <div class="footer-disclaimer">
            This report was AI-assisted. Verify figures against official records.
        </div>
    </div>

</body>
</html>
