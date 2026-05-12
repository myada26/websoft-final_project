@extends('layouts.app')
@section('title', 'Summary of Receipts')
@section('page-title', 'Summary of Receipts Report')

@section('content')
<style>
body{font-family:'DejaVu Sans','Arial',sans-serif}
.doc-header{text-align:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1.5px solid #111}
.doc-header .university{font-size:11px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;color:#555}
.doc-header .doc-title{font-size:17px;font-weight:700;letter-spacing:.02em;margin-bottom:2px;color:#111}
.doc-header .doc-subtitle{font-size:11px;color:#555}
.doc-org{margin-bottom:1rem;padding:10px 12px;border:0.5px solid #ccc;border-radius:4px}
.doc-org-label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#777;margin-bottom:2px}
.doc-org-name{font-size:13px;font-weight:700}
.doc-meta{display:flex;gap:2rem;margin-bottom:1.25rem;font-size:11px;color:#555;flex-wrap:wrap}
.doc-meta span b{color:#111}
.summary-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:1.5rem}
.sc{background:#f8f8f8;border-radius:6px;padding:12px;text-align:center}
.sc-label{font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#777;margin-bottom:4px}
.sc-val{font-size:18px;font-weight:700;color:#111}
.sc-sub{font-size:11px;color:#555;margin-top:2px}
.print-table{width:100%;border-collapse:collapse;font-size:11px;margin-bottom:1rem}
.print-table th{background:#111;color:#fff;padding:5px 8px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
.print-table th:nth-child(3),.print-table th:nth-child(4),.print-table td:nth-child(3),.print-table td:nth-child(4){text-align:right}
.print-table td{padding:5px 8px;border-bottom:0.5px solid #e5e5e5;vertical-align:top}
.print-table tr.subtotal td{background:#f5f5f5;font-weight:700;border-top:0.5px solid #bbb}
.print-table tr.grandtotal td{background:#111;color:#fff;font-weight:700;font-size:12px}
.print-sig{display:flex;justify-content:space-between;margin-top:2rem;padding-top:1rem;border-top:0.5px solid #ddd}
.ps-block{text-align:center}
.ps-line{font-size:12px;font-weight:600;margin-bottom:2px}
.ps-role{font-size:10px;color:#777}
.doc-footer{text-align:center;font-size:9px;color:#aaa;margin-top:1rem;padding-top:8px;border-top:0.5px solid #eee}
.type-pill{display:inline-block;padding:2px 6px;border-radius:3px;font-size:9px;font-weight:600;margin-right:4px}
.tp-r{background:#dbeafe;color:#1d4ed8}
.tp-e{background:#fef3c7;color:#b45309}
</style>
@php
    $semesterName = str_replace('2024-2025 ', '', $academicYear->name ?? 'N/A');
    $semesterLabel = ucfirst($semesterName);
    $orRange = $orMin !== 'N/A' && $orMax !== 'N/A' ? $orMin . ' – ' . $orMax : 'N/A';
@endphp

<div class="max-w-6xl mx-auto h-600 pb-10">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('org.dashboard') }}" class="p-2 rounded-lg text-green-300 hover:bg-white hover:text-green-600 border-2 border-transparent hover:border-green-200 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Summary of Receipts</h2>
            <p class="text-[13.5px] text-green-400 mt-0.5 font-medium">Financial collection report</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <button onclick="switchTab('ui',this)" class="px-4 py-2.5 rounded-lg text-[13px] font-bold flex items-center gap-2 bg-green-600 text-white border-2 border-green-600 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            On-screen report
        </button>
        <button onclick="switchTab('print',this)" class="px-4 py-2.5 rounded-lg text-[13px] font-bold flex items-center gap-2 bg-white text-green-600 border-2 border-green-200 hover:border-green-600 hover:text-green-600 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print document
        </button>
        <a href="{{ route('org.reports.sor.pdf') }}" class="ml-auto px-4 py-2.5 rounded-lg text-[13px] font-bold flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white border-2 border-transparent transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Export PDF
        </a>
    </div>

    <div id="ui" class="block">
        <div class="mb-6">
            <div class="text-lg font-medium text-green-800 mb-1">{{ $organization->name }}</div>
            <div class="text-sm text-green-400 flex flex-wrap gap-4">
                <span>{{ $semesterLabel }} · Academic Year {{ $academicYear->name ?? 'N/A' }}</span>
                <span>{{ $totalMembers }} total members</span>
                <span>OR range: {{ $orRange }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Receipts issued</div>
                <div class="text-xl font-semibold text-green-800">{{ $receiptCount }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Total collected</div>
                <div class="text-xl font-semibold text-green-600">₱{{ number_format($totalCollected, 0) }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Regular Students</div>
                <div class="text-xl font-semibold text-green-800">{{ $regularCount - $extendeeCount }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Extendees</div>
                <div class="text-xl font-semibold text-green-800">{{ $extendeeCount }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Fines collected</div>
                <div class="text-xl font-semibold text-green-800">{{ $fineCount }}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="text-xs text-green-500 uppercase tracking-wide mb-1">Cancelled / void</div>
                <div class="text-xl font-semibold text-green-800">{{ $voidCount }}</div>
            </div>
        </div>

        <div class="bg-white border border-green-200 rounded-lg p-5 mb-6">
            <div class="text-xs font-medium text-green-400 uppercase tracking-wide mb-3">Collection breakdown by type</div>
            <div class="space-y-2">
                <div class="flex items-center justify-between py-2 border-b border-green-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700">Regular</span>
                        <span class="text-sm text-green-600">₱{{ number_format($regularRate, 2) }} per member</span>
                    </div>
                    <div>
                        <span class="text-sm text-green-400">{{ $regularCount - $extendeeCount }} receipts</span>
                        <span class="ml-3 font-medium text-green-800">₱{{ number_format($regularPaidAmount, 2) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-green-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-blue-50 text-blue-700">Extendee</span>
                        <span class="text-sm text-green-600">₱{{ number_format($extendeeRate, 2) }} per member</span>
                    </div>
                    <div>
                        <span class="text-sm text-green-400">{{ $extendeeCount }} receipts</span>
                        <span class="ml-3 font-medium text-green-800">₱{{ number_format($extendeeAmount, 2) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-green-100">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-red-50 text-red-700">Fines</span>
                        <span class="text-sm text-green-400">—</span>
                    </div>
                    <div>
                        <span class="text-sm text-green-400">{{ $fineCount }} receipts</span>
                        <span class="ml-3 font-medium text-green-400">₱{{ number_format($fineAmount, 2) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">Cancelled</span>
                        <span class="text-sm text-green-400">—</span>
                    </div>
                    <div>
                        <span class="text-sm text-green-400">{{ $voidCount }} receipts</span>
                        <span class="ml-3 font-medium text-green-400">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-xs font-medium text-green-400 uppercase tracking-wide mb-3">Receipt batch summary</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b border-green-200">
                        <th class="text-left py-2 px-3 text-green-400 font-medium w-28">OR range</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium">Regular</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium">Extendee</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium">Fines</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium">Cancelled</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium w-20">Count</th>
                        <th class="text-right py-2 px-3 text-green-400 font-medium w-28">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr class="border-b border-green-100 hover:bg-green-50">
                        <td class="py-2 px-3 text-green-700 font-medium">{{ $batch['range'] }}</td>
                        <td class="py-2 px-3 text-right text-green-600">{{ $batch['regular'] }}</td>
                        <td class="py-2 px-3 text-right text-green-600">{{ $batch['extendee'] }}</td>
                        <td class="py-2 px-3 text-right text-green-600">{{ $batch['fine'] }}</td>
                        <td class="py-2 px-3 text-right text-green-600">{{ $batch['cancelled'] }}</td>
                        <td class="py-2 px-3 text-right text-green-800 font-medium">{{ $batch['count'] }}</td>
                        <td class="py-2 px-3 text-right text-green-800 font-medium">₱{{ number_format($batch['subtotal'], 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-green-400">No transactions recorded yet.</td>
                    </tr>
                    @endforelse
                    @if(count($batches) > 0)
                    <tr class="bg-green-50 font-semibold">
                        <td class="py-3 px-3 text-green-800">Total</td>
                        <td class="py-3 px-3 text-right text-green-800">{{ array_sum(array_column($batches, 'regular')) }}</td>
                        <td class="py-3 px-3 text-right text-green-800">{{ array_sum(array_column($batches, 'extendee')) }}</td>
                        <td class="py-3 px-3 text-right text-green-800">{{ array_sum(array_column($batches, 'fine')) }}</td>
                        <td class="py-3 px-3 text-right text-green-800">{{ array_sum(array_column($batches, 'cancelled')) }}</td>
                        <td class="py-3 px-3 text-right text-green-800">{{ $receiptCount }}</td>
                        <td class="py-3 px-3 text-right text-green-800">₱{{ number_format($totalCollected, 0) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex gap-8 flex-wrap">
            <div class="min-w-[160px]">
                <div class="text-xs text-green-400 uppercase tracking-wide mb-6">Prepared by</div>
                <div class="text-sm font-medium text-green-800 border-t border-green-300 pt-1">{{ auth()->user()->student?->full_name ?? auth()->user()->username }}</div>
                <div class="text-xs text-green-400">{{ auth()->user()->role }}, {{ $organization->name }}</div>
            </div>
            <div class="min-w-[160px]">
                <div class="text-xs text-green-400 uppercase tracking-wide mb-6">Checked by</div>
                <div class="text-sm font-medium text-green-300 border-t border-green-200 pt-1">— not yet signed —</div>
                <div class="text-xs text-green-400">Office of Student Affairs</div>
            </div>
        </div>
    </div>

<div id="print" class="hidden">
        <div class="bg-white border-2 border-green-200 rounded-xl shadow-sm p-6 mb-4">
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
                    <span><b>Date Prepared:</b> ____________</span>
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

                <div class="doc-footer">Generated by FCATS · Fee Collection and Tracking System · Printed on ____________ · This document is system-generated and valid without a wet signature if transmitted electronically to OSA.</div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
            <p class="text-sm text-green-600"><b>Print note:</b> The print view above is formatted for A4 paper. In FCATS, clicking "Export PDF" will generate this as a downloadable file using DomPDF, with the university letterhead and official OR series auto-filled from the active semester's data.</p>
        </div>
    </div>
</div>

<script>
function switchTab(id, btn){
  document.getElementById('ui').classList.add('hidden');
  document.getElementById('print').classList.add('hidden');
  document.getElementById(id).classList.remove('hidden');
}
</script>
@endsection