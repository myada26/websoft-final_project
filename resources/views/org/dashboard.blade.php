@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $organization = auth()->user()->organization;
    $chartLabels = collect($chartLabels ?? []);
    $chartData = collect($chartData ?? [])->map(fn ($value) => round((float) $value, 2));
    $recentRows = collect($recentTransactions ?? []);
    $paymentTotal = max((float) ($cashAmount ?? 0) + (float) ($gcashAmount ?? 0), 1);
    $cashShare = min(100, ((float) ($cashAmount ?? 0) / $paymentTotal) * 100);
    $gcashShare = min(100, ((float) ($gcashAmount ?? 0) / $paymentTotal) * 100);
    $collectionPerStudent = ($enrolledCount ?? 0) > 0 ? (float) ($totalCollected ?? 0) / (int) $enrolledCount : 0;
    $transactionMix = [
        ['label' => 'Fees', 'value' => $feeCount ?? 0, 'color' => '#1a7a41', 'soft' => 'bg-green-50 text-green-700'],
        ['label' => 'Fines', 'value' => $fineCount ?? 0, 'color' => '#d4a42a', 'soft' => 'bg-amber-50 text-amber-700'],
        ['label' => 'Unremitted', 'value' => $unremittedCount ?? 0, 'color' => '#be185d', 'soft' => 'bg-rose-50 text-rose-700'],
    ];
    $mixMax = max((int) collect($transactionMix)->max('value'), 1);
    $statusLabel = ($pendingVoidCount ?? 0) > 0 ? 'Needs Review' : 'Operational';
    $statusClass = ($pendingVoidCount ?? 0) > 0
        ? 'bg-amber-50 text-amber-700 border-amber-200'
        : 'bg-green-50 text-green-700 border-green-200';
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-bold leading-tight text-[#0f1f17]">{{ $organization->name ?? 'Organization' }} Dashboard</h1>
                <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <p class="mt-1 text-[12.5px] font-medium text-[#4a6356]">
                Financial overview
                <span class="text-[#9ca3af]">/</span>
                {{ $activeSemester?->name ?? 'No active semester' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-[12px]">
            <span class="rounded-lg border border-[#dde8e1] bg-white px-3 py-2 font-semibold text-[#4a6356] shadow-sm">
                {{ now()->format('l, F j, Y') }}
            </span>
            @if(auth()->user()->canCreateTransactions())
                <a href="{{ route('org.transactions.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#1a7a41] px-3 py-2 font-bold text-white shadow-sm transition hover:bg-[#14532d]">
                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    New Transaction
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Transactions Today', 'value' => number_format($todayCount ?? 0), 'sub' => 'Completed receipts', 'icon' => 'receipt', 'color' => '#1a7a41', 'bg' => '#e6f4ec'],
                ['label' => 'Total Collected', 'value' => 'PHP ' . number_format((float) ($totalCollected ?? 0), 2), 'sub' => 'This semester', 'icon' => 'credit-card', 'color' => '#d4a42a', 'bg' => '#fdf7e3'],
                ['label' => 'Enrolled Students', 'value' => number_format($enrolledCount ?? 0), 'sub' => 'In active semester', 'icon' => 'users', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                ['label' => 'Pending Voids', 'value' => number_format($pendingVoidCount ?? 0), 'sub' => 'Awaiting approval', 'icon' => 'x-circle', 'color' => '#dc2626', 'bg' => '#fef2f2'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="rounded-xl border border-[#dde8e1] bg-white p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">{{ $card['label'] }}</div>
                        <div class="mt-2 text-[22px] font-bold leading-none text-[#0f1f17]">{{ $card['value'] }}</div>
                    </div>
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" style="background:{{ $card['bg'] }};color:{{ $card['color'] }}">
                        @include('partials.ui-icon', ['name' => $card['icon'], 'class' => 'w-4 h-4'])
                    </div>
                </div>
                <div class="mt-2 text-[11.5px] font-medium text-[#6b7280]">{{ $card['sub'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
        <section class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-8">
            <div class="flex flex-col gap-3 border-b border-[#eaf0ec] px-4 py-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-[14px] font-bold text-[#0f1f17]">Collection Trend</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Last 12 months of non-void collections</div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-right">
                    <div class="rounded-lg bg-[#f8fbf9] px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a]">Per Student</div>
                        <div class="text-[15px] font-bold text-[#0f1f17]">PHP {{ number_format($collectionPerStudent, 2) }}</div>
                    </div>
                    <div class="rounded-lg bg-[#e6f4ec] px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1a7a41]">Semester Total</div>
                        <div class="text-[15px] font-bold text-[#14532d]">PHP {{ number_format((float) ($totalCollected ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 p-4 xl:grid-cols-[1fr_260px]">
                <div class="relative h-[285px] min-h-[285px]">
                    <canvas id="collectionChart"></canvas>
                </div>
                <div class="space-y-3">
                    <div class="rounded-lg border border-[#eaf0ec] bg-[#fbfdfc] p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <div>
                                <div class="text-[12.5px] font-bold text-[#0f1f17]">Transaction Mix</div>
                                <div class="text-[11px] font-medium text-[#8aa89a]">Active semester counts</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            @foreach($transactionMix as $item)
                                @php $width = min(100, ((int) $item['value'] / $mixMax) * 100); @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <span class="text-[11.5px] font-semibold text-[#374151]">{{ $item['label'] }}</span>
                                        <span class="rounded px-1.5 py-0.5 text-[10.5px] font-bold {{ $item['soft'] }}">{{ number_format($item['value']) }}</span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                        <div class="h-full rounded-full" style="width: {{ $width }}%; background: {{ $item['color'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="rounded-lg border border-[#eaf0ec] bg-[#fbfdfc] p-3">
                        <div class="text-[12.5px] font-bold text-[#0f1f17]">Payment Split</div>
                        <div class="mt-3 space-y-3">
                            <div>
                                <div class="mb-1 flex items-center justify-between text-[11.5px]">
                                    <span class="font-semibold text-[#374151]">Cash</span>
                                    <span class="font-bold text-[#0f1f17]">PHP {{ number_format((float) ($cashAmount ?? 0), 2) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                    <div class="h-full rounded-full bg-[#1a7a41]" style="width: {{ $cashShare }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between text-[11.5px]">
                                    <span class="font-semibold text-[#374151]">GCash</span>
                                    <span class="font-bold text-[#0f1f17]">PHP {{ number_format((float) ($gcashAmount ?? 0), 2) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                    <div class="h-full rounded-full bg-[#2563eb]" style="width: {{ $gcashShare }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-4">
            <div class="border-b border-[#eaf0ec] px-4 py-3">
                <div class="text-[14px] font-bold text-[#0f1f17]">Payment Methods</div>
                <div class="text-[11.5px] font-medium text-[#8aa89a]">Cash vs GCash this semester</div>
            </div>
            <div class="grid gap-3 p-4 sm:grid-cols-[180px_1fr] 2xl:grid-cols-1">
                <div class="relative h-[190px] min-h-[190px]">
                    <canvas id="paymentChart"></canvas>
                </div>
                <div class="grid content-center gap-2">
                    <div class="rounded-lg border border-[#eaf0ec] bg-[#fbfdfc] p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a]">Cash</div>
                        <div class="mt-1 text-[17px] font-bold text-[#0f1f17]">PHP {{ number_format((float) ($cashAmount ?? 0), 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-[#eaf0ec] bg-[#fbfdfc] p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a]">GCash</div>
                        <div class="mt-1 text-[17px] font-bold text-[#0f1f17]">PHP {{ number_format((float) ($gcashAmount ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    @include('partials._ai_insights', ['aiInsight' => $aiInsight ?? null])

    <section class="rounded-xl border border-[#dde8e1] bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#eaf0ec] px-4 py-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-[14px] font-bold text-[#0f1f17]">Recent Transactions</div>
                <div class="text-[11.5px] font-medium text-[#8aa89a]">Latest 10 receipts processed by this organization</div>
            </div>
            <div class="text-[11.5px] font-bold uppercase tracking-wider text-[#8aa89a]">{{ $recentRows->count() }} shown</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-[#dde8e1] bg-[#f8fbf9]">
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">OR No.</th>
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Student</th>
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Type</th>
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Amount</th>
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Method</th>
                        <th class="px-4 py-3 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eaf0ec]">
                    @forelse($recentRows as $tx)
                        <tr class="transition hover:bg-[#f8fbf9]">
                            <td class="px-4 py-3">
                                <span class="rounded bg-[#e6f4ec] px-2 py-1 font-mono text-[11.5px] font-bold text-[#1a7a41]">{{ $tx->or_number }}</span>
                            </td>
                            <td class="px-4 py-3 text-[12.5px] font-semibold text-[#0f1f17]">{{ $tx->student?->full_name ?? 'Unknown student' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $tx->transaction_type === 'FEE' ? 'bg-green-50 text-green-700' : 'bg-rose-50 text-rose-700' }}">{{ $tx->transaction_type }}</span>
                            </td>
                            <td class="px-4 py-3 text-[12.5px] font-bold text-[#0f1f17]">PHP {{ number_format((float) ($tx->amount ?? $tx->amount_paid), 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $tx->payment_method === 'GCASH' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-700' }}">{{ $tx->payment_method }}</span>
                            </td>
                            <td class="px-4 py-3 text-[12px] font-medium text-[#8aa89a]">{{ $tx->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-9 text-center text-[13px] font-medium text-[#8aa89a]">No transactions yet this semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Chart === 'undefined') return;

        Chart.defaults.font.family = "'Outfit', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#4a6356';

        const moneyTick = (value) => 'PHP ' + Number(value || 0).toLocaleString();

        const collectionCanvas = document.getElementById('collectionChart');
        if (collectionCanvas) {
            new Chart(collectionCanvas, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Collected',
                        data: @json($chartData),
                        backgroundColor: '#1a7a41',
                        borderRadius: 7,
                        maxBarThickness: 30,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => moneyTick(context.parsed.y),
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#edf2ef' },
                            ticks: { callback: moneyTick },
                        },
                    },
                },
            });
        }

        const paymentCanvas = document.getElementById('paymentChart');
        if (paymentCanvas) {
            new Chart(paymentCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Cash', 'GCash'],
                    datasets: [{
                        data: @json([(float) ($cashAmount ?? 0), (float) ($gcashAmount ?? 0)]),
                        backgroundColor: ['#1a7a41', '#2563eb'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                    }],
                },
                options: {
                    cutout: '66%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, boxHeight: 10, padding: 12 },
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.label + ': ' + moneyTick(context.parsed),
                            },
                        },
                    },
                },
            });
        }
    });
</script>
@endpush
