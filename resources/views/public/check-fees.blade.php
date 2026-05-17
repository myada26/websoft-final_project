<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fee Status Check — FCATS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-400: #4ade80;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            --text-main: #111827;
            --text-sub:  #6b7280;
            --text-dim:  #9ca3af;
            --radius-card: 1.5rem;
            --radius-sm:   0.75rem;
        }

        html, body {
            min-height: 100vh;
            font-family: 'Outfit', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Background ── */
        body {
            background: #f0f7f2;
            position: relative;
            overflow-x: hidden;
        }

        /* Full-page background image */
        .bg-image {
            position: fixed;
            inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            pointer-events: none;
            z-index: 0;
        }

        /* Subtle white wash (no blur) */
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.08);
            pointer-events: none;
            z-index: 0;
        }

        /* Mix-blend light leaks */
        .bg-light-leak {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            mix-blend-mode: overlay;
            z-index: 0;
        }
        .bg-light-leak::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-34deg);
            width: 1100px; height: 260px;
            background: rgba(255,255,255,0.50);
            filter: blur(55px);
            border-radius: 999px;
        }
        .bg-light-leak::after {
            content: '';
            position: absolute;
            top: 5%; left: -10%;
            width: 700px; height: 180px;
            background: rgba(187,247,208,0.25);
            filter: blur(75px);
            border-radius: 999px;
            transform: rotate(-45deg);
        }

        /* ── Layout ── */
        .page-wrap {
            position: relative;
            z-index: 1;
            max-width: 760px;
            margin: 0 auto;
            padding: 48px 20px 64px;
        }

        /* ── Page header ── */
        .page-header {
            text-align: center;
            margin-bottom: 36px;
        }
        .page-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.55);
            border: 1.5px solid rgba(255,255,255,0.65);
            backdrop-filter: blur(12px);
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--green-700);
            margin-bottom: 16px;
        }
        .page-header-badge .badge-dot {
            width: 6px; height: 6px;
            border-radius: 999px;
            background: var(--green-500);
        }
        .page-header h1 {
            font-size: 30px;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-main);
            line-height: 1.2;
        }
        .page-header p {
            margin-top: 10px;
            font-size: 14.5px;
            color: var(--text-sub);
            font-weight: 400;
        }

        /* ── Glass card ── */
        .glass-card {
            background: linear-gradient(165deg, rgba(255,255,255,0.72), rgba(255,255,255,0.52));
            backdrop-filter: blur(24px) saturate(1.3);
            -webkit-backdrop-filter: blur(24px) saturate(1.3);
            border-radius: var(--radius-card);
            border: 1.5px solid rgba(255,255,255,0.68);
            box-shadow:
                0 16px 48px -12px rgba(34,197,94,0.10),
                0 6px 20px rgba(0,0,0,0.05),
                inset 0 1px 0 rgba(255,255,255,0.90);
            overflow: hidden;
        }
        .glass-card + .glass-card { margin-top: 16px; }

        /* ── Search card ── */
        .search-card {
            padding: 28px 32px;
            margin-bottom: 20px;
        }
        .search-card-inner {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .search-field {
            flex: 1;
            min-width: 200px;
        }
        .search-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: #374151;
            margin-bottom: 8px;
        }
        .search-input-wrap {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.45);
            border: 1.5px solid rgba(255,255,255,0.65);
            border-radius: 0.875rem;
            transition: background .25s, border-color .25s, box-shadow .25s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .search-input-wrap:focus-within {
            background: rgba(255,255,255,0.70);
            border-color: rgba(74,222,128,0.55);
            box-shadow: 0 0 0 3px rgba(74,222,128,0.14);
        }
        .search-input-icon {
            padding-left: 14px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            flex-shrink: 0;
            transition: color .2s;
        }
        .search-input-wrap:focus-within .search-input-icon { color: var(--green-500); }
        .search-input-wrap input {
            flex: 1;
            border: 0;
            outline: none;
            background: transparent;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 400;
            color: var(--text-main);
            min-width: 0;
        }
        .search-input-wrap input::placeholder { color: #9ca3af; font-weight: 300; }
        .search-btn {
            padding: 12px 26px;
            border: 0;
            border-radius: 0.875rem;
            background: linear-gradient(135deg, var(--green-500), var(--green-600));
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 6px 18px -5px rgba(34,197,94,0.48);
            transition: transform .2s, box-shadow .2s, background .2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .search-btn:hover {
            background: linear-gradient(135deg, var(--green-600), var(--green-700));
            transform: translateY(-1px);
            box-shadow: 0 8px 22px -5px rgba(34,197,94,0.55);
        }
        .search-btn:active { transform: translateY(0); }

        /* ── Alert banners ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 16px;
            border: 1.5px solid;
        }
        .alert-error {
            background: rgba(254,242,242,0.80);
            border-color: rgba(252,165,165,0.60);
            color: #dc2626;
            backdrop-filter: blur(12px);
        }
        .alert-success {
            background: rgba(220,252,231,0.80);
            border-color: rgba(134,239,172,0.60);
            color: #15803d;
            backdrop-filter: blur(12px);
        }
        .alert svg { flex-shrink: 0; margin-top: 1px; }

        /* ── Student info card ── */
        .student-card-body { padding: 24px 28px; }
        .student-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .student-meta {
            font-size: 13px;
            color: var(--text-sub);
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .student-meta-sep {
            width: 3px; height: 3px;
            border-radius: 999px;
            background: var(--text-dim);
            flex-shrink: 0;
        }
        .fee-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .fee-badge-paid {
            background: rgba(220,252,231,0.80);
            color: #15803d;
            border: 1px solid rgba(134,239,172,0.50);
        }
        .fee-badge-unpaid {
            background: rgba(254,226,226,0.80);
            color: #dc2626;
            border: 1px solid rgba(252,165,165,0.50);
        }
        .fee-badge-dot { width: 5px; height: 5px; border-radius: 999px; background: currentColor; }

        /* ── Table card ── */
        .table-header {
            padding: 20px 28px 16px;
            border-bottom: 1.5px solid rgba(255,255,255,0.50);
        }
        .table-title {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-main);
        }
        .table-sub {
            font-size: 12.5px;
            color: var(--text-sub);
            margin-top: 2px;
        }
        .table-scroll { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }
        thead tr {
            background: rgba(240,249,244,0.60);
            border-bottom: 1.5px solid rgba(255,255,255,0.55);
        }
        th {
            padding: 11px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6b7280;
            white-space: nowrap;
        }
        td {
            padding: 13px 20px;
            font-size: 13.5px;
            border-bottom: 1px solid rgba(255,255,255,0.45);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,0.35); }
        .td-event { font-weight: 600; color: var(--text-main); }
        .td-muted  { color: var(--text-sub); }
        .td-amount { font-weight: 700; color: var(--text-main); text-align: right; }
        .td-or     { font-size: 11px; color: var(--text-dim); margin-top: 3px; }

        /* Table footer row */
        .tfoot-row td {
            background: rgba(240,249,244,0.50);
            border-top: 1.5px solid rgba(255,255,255,0.55);
            font-weight: 700;
            color: var(--text-main);
            padding: 12px 20px;
        }
        .tfoot-total { color: #dc2626; font-size: 15px; text-align: right; }

        /* ── Outstanding balance banner ── */
        .balance-banner {
            margin-top: 16px;
            padding: 16px 22px;
            border-radius: var(--radius-sm);
            background: rgba(254,243,199,0.80);
            border: 1.5px solid rgba(252,211,77,0.50);
            color: #92400e;
            backdrop-filter: blur(12px);
            font-size: 13.5px;
        }
        .balance-banner strong { font-weight: 700; display: block; margin-bottom: 4px; font-size: 14px; }

        /* ── Footer ── */
        .page-footer {
            text-align: center;
            padding: 28px 0 8px;
            font-size: 12px;
            color: var(--text-dim);
        }
        .page-footer a {
            color: var(--green-600);
            text-decoration: none;
            font-weight: 500;
        }
        .page-footer a:hover { color: var(--green-700); }

        @media (max-width: 520px) {
            .page-wrap { padding: 32px 16px 48px; }
            .search-card { padding: 22px 20px; }
            .student-card-body, .table-header { padding: 18px 20px; }
            th, td { padding: 10px 14px; }
            .page-header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
<img src="/img/auth-bg.png" alt="" class="bg-image" aria-hidden="true">
<div class="bg-overlay"     aria-hidden="true"></div>
<div class="bg-light-leak"  aria-hidden="true"></div>

<div class="page-wrap">

    {{-- Page header --}}
    <div class="page-header">
        <div class="page-header-badge">
            <span class="badge-dot"></span>
            FCATS
        </div>
        <h1>Student Fee Accountability</h1>
        <p>Enter your student ID number to view your fee status and attendance fines.</p>
    </div>

    {{-- Search form --}}
    <div class="glass-card search-card">
        <form method="GET" action="{{ route('public.check-fees') }}">
            <div class="search-card-inner">
                <div class="search-field">
                    <label class="search-label" for="student_number">Student ID Number</label>
                    <div class="search-input-wrap">
                        <span class="search-input-icon" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="student_number"
                            name="student_number"
                            value="{{ $studentNumber }}"
                            placeholder="e.g. 2024-000042"
                            autocomplete="off"
                            autofocus
                            required />
                    </div>
                </div>
                <button type="submit" class="search-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Not found --}}
    @if($notFound)
    <div class="alert alert-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
        </svg>
        <span>No student found with ID <strong>{{ $studentNumber }}</strong>. Please double-check your student number and try again.</span>
    </div>
    @endif

    @if($student)

    {{-- Student identity card --}}
    <div class="glass-card">
        <div class="student-card-body">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <div class="student-name">{{ $student->full_name }}</div>
                    <div class="student-meta">
                        <span>{{ $student->student_number }}</span>
                        @if($student->latestEnrollment)
                            <span class="student-meta-sep"></span>
                            <span>{{ $student->latestEnrollment->program?->name ?? '' }}</span>
                            @if($student->latestEnrollment->academicYear)
                                <span class="student-meta-sep"></span>
                                <span>{{ $student->latestEnrollment->academicYear->name }}</span>
                            @endif
                        @endif
                    </div>
                </div>
                @if($feeStatus)
                <div>
                    @if($feeStatus === 'PAID')
                    <span class="fee-badge fee-badge-paid">
                        <span class="fee-badge-dot"></span> Membership Paid
                    </span>
                    @else
                    <span class="fee-badge fee-badge-unpaid">
                        <span class="fee-badge-dot"></span> Membership Unpaid
                    </span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Fines section --}}
    @php
        $unpaidTotal = $fines->where('status','UNPAID')->sum('fine_amount');
    @endphp

    @if($fines->isEmpty())
    <div class="alert alert-success" role="status" style="margin-top:16px">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
        </svg>
        <span>No attendance fines on record. You're all clear!</span>
    </div>
    @else
    <div class="glass-card" style="margin-top:16px">
        <div class="table-header">
            <div class="table-title">Attendance Fines Breakdown</div>
            <div class="table-sub">{{ $fines->count() }} record(s) found</div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Organization</th>
                        <th>Date</th>
                        <th style="text-align:center">Slots Missed</th>
                        <th style="text-align:right">Fine Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fines as $fine)
                    <tr>
                        <td class="td-event">{{ $fine->event?->name ?? '—' }}</td>
                        <td class="td-muted">{{ $fine->organization?->name ?? '—' }}</td>
                        <td class="td-muted" style="white-space:nowrap">{{ $fine->event?->date?->format('M d, Y') ?? '—' }}</td>
                        <td style="text-align:center;color:#6b7280">
                            {{ $fine->slots_missed }} / {{ $fine->event ? count((new \App\Models\Event(['time_type' => $fine->event->time_type ?? 'FULL_DAY']))->slots()) : '?' }}
                        </td>
                        <td class="td-amount">₱{{ number_format($fine->fine_amount, 2) }}</td>
                        <td>
                            @if($fine->isPaid())
                            <span class="fee-badge fee-badge-paid">
                                <span class="fee-badge-dot"></span> Paid
                            </span>
                            @if($fine->transaction)
                            <div class="td-or">OR: {{ $fine->transaction->or_number }}</div>
                            @endif
                            @else
                            <span class="fee-badge fee-badge-unpaid">
                                <span class="fee-badge-dot"></span> Unpaid
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($unpaidTotal > 0)
                <tfoot>
                    <tr class="tfoot-row">
                        <td colspan="4" style="text-align:right;color:#6b7280;font-weight:600">Total Outstanding Balance</td>
                        <td class="tfoot-total">₱{{ number_format($unpaidTotal, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @if($unpaidTotal > 0)
    <div class="balance-banner">
        <strong>Outstanding Balance: ₱{{ number_format($unpaidTotal, 2) }}</strong>
        Please approach your organization treasurer to settle your attendance fines.
    </div>
    @endif

    @endif

    @endif

</div>

<div class="page-footer">
    FCATS — Fee Collection &amp; Tracking System &nbsp;·&nbsp; This page is read-only and for inquiry purposes only.
    &nbsp;·&nbsp; <a href="{{ route('login') }}">Officer Login</a>
</div>

</body>
</html>
