@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $todayCollectionRows = collect($todayCollections ?? []);
    $semesterRows = collect($semesterByOrg ?? []);
    $todayTransactionCount = (int) $todayCollectionRows->sum('cnt');
    $todayCollectionLabels = $todayCollectionRows
        ->map(fn ($row) => $row->transaction_type . ' / ' . $row->payment_method)
        ->values();
    $todayCollectionData = $todayCollectionRows
        ->map(fn ($row) => round((float) $row->total, 2))
        ->values();
    $semesterOrgLabels = $semesterRows
        ->map(fn ($row) => Str::limit($row->org_name, 24))
        ->values();
    $semesterOrgData = $semesterRows
        ->map(fn ($row) => round((float) $row->total, 2))
        ->values();
    $semesterMax = max((float) $semesterRows->max('total'), 1);
    $topOrg = $semesterRows->first();
    $orgTypeMeta = [
        'UNIVERSITY_WIDE' => ['label' => 'University-Wide', 'color' => '#1a7a41', 'soft' => 'bg-green-50 text-green-700'],
        'COLLEGE_COUNCIL' => ['label' => 'College Council', 'color' => '#2563eb', 'soft' => 'bg-blue-50 text-blue-700'],
        'CLASS_ORG' => ['label' => 'Class Organization', 'color' => '#7c3aed', 'soft' => 'bg-violet-50 text-violet-700'],
        'RESERVED' => ['label' => 'Reserved', 'color' => '#6b7280', 'soft' => 'bg-gray-100 text-gray-600'],
    ];
    $orgTypeLabels = collect($orgTypeMeta)->map(fn ($meta) => $meta['label'])->values();
    $orgTypeData = collect($orgTypeMeta)->keys()
        ->map(fn ($type) => (int) $orgBreakdown->get($type, 0))
        ->values();
    $orgTotal = max((int) $orgBreakdown->sum(), 1);
    $structureItems = [
        ['label' => 'Colleges', 'value' => $stats['colleges'], 'color' => 'bg-green-500'],
        ['label' => 'Departments', 'value' => $stats['departments'], 'color' => 'bg-sky-500'],
        ['label' => 'Programs', 'value' => $stats['programs'], 'color' => 'bg-violet-500'],
    ];
    $structureMax = max((int) collect($structureItems)->max('value'), 1);
    $healthFlags = collect([
        $failedBackupCount > 0,
        $failedJobs > 0,
        $pendingJobs > 10,
        $criticalAuditToday > 0,
    ])->filter()->count();
    $healthLabel = $healthFlags === 0 ? 'Stable' : ($healthFlags === 1 ? 'Watch' : 'Attention');
    $healthClass = $healthFlags === 0
        ? 'bg-green-50 text-green-700 border-green-200'
        : ($healthFlags === 1 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200');
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-bold leading-tight text-[#0f1f17]">SSC Admin Dashboard</h1>
                <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $healthClass }}">{{ $healthLabel }}</span>
            </div>
            <p class="mt-1 text-[12.5px] font-medium text-[#4a6356]">
                System-wide overview
                <span class="text-[#9ca3af]">/</span>
                @if($activeSemester)
                    Active semester: <strong>{{ $activeSemester->school_year ?? $activeSemester->name }}</strong>
                @else
                    <span class="font-semibold text-amber-600">No active semester set</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-[12px]">
            <span class="rounded-lg border border-[#dde8e1] bg-white px-3 py-2 font-semibold text-[#4a6356] shadow-sm">
                {{ now()->format('l, F j, Y') }}
            </span>
            <form method="POST" action="{{ route('admin.backup.trigger') }}" onsubmit="this.querySelector('button').disabled=true">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-[#b7dfc7] bg-[#e6f4ec] px-3 py-2 font-bold text-[#1a7a41] transition hover:border-[#1a7a41] hover:bg-[#1a7a41] hover:text-white">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Trigger Backup
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6">
        @php
            $cards = [
                ['label' => 'Colleges', 'value' => $stats['colleges'], 'sub' => 'Academic units', 'color' => '#1a7a41', 'bg' => '#e6f4ec', 'icon' => 'building2'],
                ['label' => 'Departments', 'value' => $stats['departments'], 'sub' => 'Program owners', 'color' => '#0369a1', 'bg' => '#e0f2fe', 'icon' => 'building'],
                ['label' => 'Programs', 'value' => $stats['programs'], 'sub' => 'Curricula tracked', 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'layers'],
                ['label' => 'Organizations', 'value' => $stats['organizations'], 'sub' => 'Active org scopes', 'color' => '#b45309', 'bg' => '#fef3c7', 'icon' => 'users'],
                ['label' => 'Students', 'value' => number_format($stats['students']), 'sub' => 'Student records', 'color' => '#0f766e', 'bg' => '#ccfbf1', 'icon' => 'users'],
                ['label' => 'System Users', 'value' => $stats['users'], 'sub' => 'Login accounts', 'color' => '#be185d', 'bg' => '#fce7f3', 'icon' => 'settings'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="rounded-xl border border-[#dde8e1] bg-white p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="truncate text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">{{ $card['label'] }}</div>
                        <div class="mt-2 text-[24px] font-bold leading-none text-[#0f1f17]">{{ $card['value'] }}</div>
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
                    <div class="text-[14px] font-bold text-[#0f1f17]">Collection Pulse</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Today by transaction type and payment method</div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-right">
                    <div class="rounded-lg bg-[#f8fbf9] px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a]">Receipts</div>
                        <div class="text-[16px] font-bold text-[#0f1f17]">{{ number_format($todayTransactionCount) }}</div>
                    </div>
                    <div class="rounded-lg bg-[#e6f4ec] px-3 py-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1a7a41]">Total</div>
                        <div class="text-[16px] font-bold text-[#14532d]">PHP {{ number_format((float) $todayTotal, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 p-4 lg:grid-cols-[260px_1fr]">
                <div class="flex min-h-[220px] items-center justify-center">
                    @if($todayCollectionRows->isEmpty())
                        <div class="text-center text-[13px] font-medium text-[#8aa89a]">No transactions recorded today.</div>
                    @else
                        <div class="relative h-[220px] w-full">
                            <canvas id="adminTodayCollectionsChart"></canvas>
                        </div>
                    @endif
                </div>
                <div class="grid content-start gap-2 sm:grid-cols-2">
                    @forelse($todayCollectionRows as $row)
                        @php
                            $share = $todayTotal > 0 ? min(100, ((float) $row->total / (float) $todayTotal) * 100) : 0;
                            $tone = $row->transaction_type === 'FEE' ? 'bg-green-500' : 'bg-amber-500';
                        @endphp
                        <div class="rounded-lg border border-[#eaf0ec] bg-[#fbfdfc] p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $row->transaction_type === 'FEE' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $row->transaction_type }}</span>
                                        <span class="text-[10.5px] font-bold text-[#6b7280]">{{ $row->payment_method }}</span>
                                    </div>
                                    <div class="mt-1 text-[11.5px] font-medium text-[#8aa89a]">{{ $row->cnt }} transaction{{ $row->cnt != 1 ? 's' : '' }}</div>
                                </div>
                                <div class="text-right text-[13px] font-bold text-[#0f1f17]">PHP {{ number_format((float) $row->total, 2) }}</div>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                <div class="h-full rounded-full {{ $tone }}" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-[#dde8e1] bg-[#fbfdfc] p-5 text-center text-[13px] font-medium text-[#8aa89a] sm:col-span-2">Collection entries will appear here once receipts are recorded.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-4">
            <div class="border-b border-[#eaf0ec] px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[14px] font-bold text-[#0f1f17]">System Health</div>
                        <div class="text-[11.5px] font-medium text-[#8aa89a]">Operations and queue status</div>
                    </div>
                    <span class="rounded-full border px-2 py-1 text-[10.5px] font-bold {{ $healthClass }}">{{ $healthLabel }}</span>
                </div>
            </div>
            <div class="divide-y divide-[#eaf0ec]">
                @php
                    $healthItems = [
                        [
                            'label' => 'Last Backup',
                            'value' => $lastBackup ? $lastBackup->executed_at->diffForHumans() : 'Never',
                            'sub' => $lastBackup ? $lastBackup->formattedSize() . ' / ' . $lastBackup->disk : 'No backup on record',
                            'state' => $lastBackup && $lastBackup->executed_at->gt(now()->subDays(2)) ? 'ok' : 'warn',
                        ],
                        [
                            'label' => 'Backup Failures',
                            'value' => $failedBackupCount,
                            'sub' => 'Last 7 days',
                            'state' => $failedBackupCount === 0 ? 'ok' : 'warn',
                        ],
                        [
                            'label' => 'Failed Queue Jobs',
                            'value' => $failedJobs,
                            'sub' => $failedJobs ? 'Retry queue jobs' : 'No failed jobs',
                            'state' => $failedJobs === 0 ? 'ok' : 'warn',
                        ],
                        [
                            'label' => 'Pending Queue Jobs',
                            'value' => $pendingJobs,
                            'sub' => 'Waiting for workers',
                            'state' => $pendingJobs > 10 ? 'warn' : 'ok',
                        ],
                        [
                            'label' => 'Critical Actions',
                            'value' => $criticalAuditToday,
                            'sub' => 'Recorded today',
                            'state' => $criticalAuditToday === 0 ? 'ok' : 'warn',
                        ],
                    ];
                @endphp
                @foreach($healthItems as $item)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <div class="h-2.5 w-2.5 rounded-full {{ $item['state'] === 'ok' ? 'bg-green-500' : 'bg-amber-400' }}"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-[12px] font-semibold text-[#374151]">{{ $item['label'] }}</span>
                                <span class="shrink-0 text-[12px] font-bold {{ $item['state'] === 'ok' ? 'text-[#0f1f17]' : 'text-amber-600' }}">{{ $item['value'] }}</span>
                            </div>
                            <div class="truncate text-[11px] font-medium text-[#9ca3af]">{{ $item['sub'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($lastImport)
                <div class="border-t border-[#eaf0ec] bg-[#fbfdfc] px-4 py-3">
                    <div class="text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Last Import</div>
                    <div class="mt-1 truncate text-[12px] font-semibold text-[#0f1f17]">{{ $lastImport->filename }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold
                            {{ $lastImport->status === 'SUCCESS' ? 'bg-green-100 text-green-700' : ($lastImport->status === 'PARTIAL' ? 'bg-amber-100 text-amber-700' : ($lastImport->status === 'FAILED' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600')) }}">
                            {{ $lastImport->status }}
                        </span>
                        <span class="text-[11px] font-medium text-[#6b7280]">{{ $lastImport->rows_processed }} rows / {{ $lastImport->failures_count }} failures</span>
                    </div>
                </div>
            @endif
        </aside>
    </div>

    <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
        <section class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-7">
            <div class="flex items-center justify-between gap-3 border-b border-[#eaf0ec] px-4 py-3">
                <div>
                    <div class="text-[14px] font-bold text-[#0f1f17]">Semester Collections by Organization</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">
                        @if($activeSemester)
                            {{ $activeSemester->school_year ?? $activeSemester->name }}
                        @else
                            No active semester
                        @endif
                    </div>
                </div>
                @if($topOrg)
                    <div class="hidden text-right sm:block">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#8aa89a]">Top Org</div>
                        <div class="max-w-[180px] truncate text-[12px] font-bold text-[#0f1f17]">{{ $topOrg->org_name }}</div>
                    </div>
                @endif
            </div>
            <div class="grid gap-4 p-4 xl:grid-cols-[1fr_260px]">
                <div class="min-h-[250px]">
                    @if($semesterRows->isEmpty())
                        <div class="flex min-h-[250px] items-center justify-center rounded-lg border border-dashed border-[#dde8e1] text-[13px] font-medium text-[#8aa89a]">No transactions recorded this semester.</div>
                    @else
                        <div class="relative h-[250px] w-full">
                            <canvas id="adminSemesterOrgChart"></canvas>
                        </div>
                    @endif
                </div>
                <div class="space-y-2">
                    @forelse($semesterRows as $row)
                        @php $share = min(100, ((float) $row->total / $semesterMax) * 100); @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <span class="truncate text-[11.5px] font-semibold text-[#374151]">{{ $row->org_name }}</span>
                                <span class="shrink-0 text-[11.5px] font-bold text-[#0f1f17]">PHP {{ number_format((float) $row->total, 0) }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                <div class="h-full rounded-full bg-[#1a7a41]" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-[#dde8e1] p-4 text-center text-[13px] font-medium text-[#8aa89a]">Organization collection rankings will appear here.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-4 2xl:col-span-5">
            <div class="rounded-xl border border-[#dde8e1] bg-white shadow-sm">
                <div class="border-b border-[#eaf0ec] px-4 py-3">
                    <div class="text-[14px] font-bold text-[#0f1f17]">Academic Structure</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Colleges, departments, and programs</div>
                </div>
                <div class="space-y-3 p-4">
                    @foreach($structureItems as $item)
                        @php $width = min(100, ((int) $item['value'] / $structureMax) * 100); @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-[12px] font-semibold text-[#374151]">{{ $item['label'] }}</span>
                                <span class="text-[12px] font-bold text-[#0f1f17]">{{ number_format($item['value']) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[#edf2ef]">
                                <div class="h-full rounded-full {{ $item['color'] }}" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-[#dde8e1] bg-white shadow-sm">
                <div class="border-b border-[#eaf0ec] px-4 py-3">
                    <div class="text-[14px] font-bold text-[#0f1f17]">Organization Mix</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Active organizations by scope</div>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-[150px_1fr]">
                    <div class="flex min-h-[150px] items-center justify-center">
                        <div class="relative h-[150px] w-full">
                            <canvas id="adminOrgTypeChart"></canvas>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @foreach($orgTypeMeta as $type => $meta)
                            @php
                                $count = (int) $orgBreakdown->get($type, 0);
                                $share = min(100, ($count / $orgTotal) * 100);
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2">
                                    <span class="truncate text-[11.5px] font-semibold text-[#374151]">{{ $meta['label'] }}</span>
                                    <span class="rounded px-1.5 py-0.5 text-[10.5px] font-bold {{ $meta['soft'] }}">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-[#edf2ef]">
                                    <div class="h-full rounded-full" style="width: {{ $share }}%; background: {{ $meta['color'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
        <section class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-7">
            <div class="flex items-center justify-between gap-3 border-b border-[#eaf0ec] px-4 py-3">
                <div>
                    <div class="text-[14px] font-bold text-[#0f1f17]">Recent Audit Events</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Latest system actions and exceptions</div>
                </div>
                <a href="{{ route('admin.audit-logs.index') }}" class="text-[12px] font-bold text-[#1a7a41] hover:underline">View all</a>
            </div>
            <div class="divide-y divide-[#eaf0ec]">
                @forelse($recentAuditLogs as $log)
                    @php
                        $actionColor = match(true) {
                            str_contains($log->action, 'VOID') || str_contains($log->action, 'FAILED') => 'bg-red-50 text-red-600',
                            str_contains($log->action, 'CREATED') || str_contains($log->action, 'COMPLETED') => 'bg-green-50 text-green-700',
                            str_contains($log->action, 'UPDATED') || str_contains($log->action, 'VERIFIED') => 'bg-blue-50 text-blue-700',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <div class="flex items-start gap-3 px-4 py-2.5">
                        <span class="mt-0.5 shrink-0 rounded px-1.5 py-0.5 text-[9.5px] font-bold leading-tight {{ $actionColor }}">
                            {{ Str::limit($log->action, 24) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[12px] text-[#374151]">
                                <span class="font-semibold">{{ $log->user?->username ?? 'System' }}</span>
                                @if($log->entity_type)
                                    <span class="text-[#9ca3af]">/</span> {{ $log->entity_type }} #{{ $log->entity_id }}
                                @endif
                            </div>
                            <div class="text-[11px] font-medium text-[#9ca3af]">{{ $log->timestamp?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-[13px] font-medium text-[#8aa89a]">No audit events recorded yet.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-[#dde8e1] bg-white shadow-sm 2xl:col-span-5">
            <div class="border-b border-[#eaf0ec] px-4 py-3">
                <div class="text-[14px] font-bold text-[#0f1f17]">Quick Actions</div>
                <div class="text-[11.5px] font-medium text-[#8aa89a]">Administrative shortcuts</div>
            </div>
            <div class="grid grid-cols-2 gap-2 p-4 lg:grid-cols-3 2xl:grid-cols-2">
                @php
                    $actions = [
                        ['label' => 'Colleges', 'route' => 'admin.colleges.index', 'icon' => 'building2', 'color' => '#1a7a41', 'bg' => '#e6f4ec'],
                        ['label' => 'Departments', 'route' => 'admin.departments.index', 'icon' => 'building', 'color' => '#0369a1', 'bg' => '#e0f2fe'],
                        ['label' => 'Programs', 'route' => 'admin.programs.index', 'icon' => 'layers', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                        ['label' => 'Academic Years', 'route' => 'admin.academic-years.index', 'icon' => 'calendar', 'color' => '#b45309', 'bg' => '#fef3c7'],
                        ['label' => 'Organizations', 'route' => 'admin.organizations.index', 'icon' => 'users', 'color' => '#0f766e', 'bg' => '#ccfbf1'],
                        ['label' => 'Students', 'route' => 'admin.students.index', 'icon' => 'users', 'color' => '#be185d', 'bg' => '#fce7f3'],
                        ['label' => 'Fee Profiles', 'route' => 'admin.fee-profiles.index', 'icon' => 'receipt', 'color' => '#1a7a41', 'bg' => '#e6f4ec'],
                        ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'settings', 'color' => '#374151', 'bg' => '#f9fafb'],
                        ['label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'icon' => 'file-clock', 'color' => '#b45309', 'bg' => '#fef3c7'],
                    ];
                @endphp
                @foreach($actions as $action)
                    <a href="{{ route($action['route']) }}" class="group flex min-h-[54px] items-center gap-2 rounded-lg border border-[#eaf0ec] bg-white px-3 py-2 transition hover:border-[#1a7a41] hover:bg-[#f0fdf4]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" style="background:{{ $action['bg'] }};color:{{ $action['color'] }}">
                            @include('partials.ui-icon', ['name' => $action['icon'], 'class' => 'w-4 h-4'])
                        </span>
                        <span class="text-[12px] font-bold leading-tight text-[#374151] group-hover:text-[#1a7a41]">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Chart === 'undefined') return;

        Chart.defaults.font.family = "'Outfit', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#4a6356';

        const moneyTick = (value) => 'PHP ' + Number(value || 0).toLocaleString();
        const chartColors = ['#1a7a41', '#2563eb', '#d4a42a', '#7c3aed', '#be185d', '#0f766e'];

        const todayCanvas = document.getElementById('adminTodayCollectionsChart');
        if (todayCanvas) {
            new Chart(todayCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json($todayCollectionLabels),
                    datasets: [{
                        data: @json($todayCollectionData),
                        backgroundColor: chartColors,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                    }],
                },
                options: {
                    cutout: '68%',
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

        const semesterCanvas = document.getElementById('adminSemesterOrgChart');
        if (semesterCanvas) {
            new Chart(semesterCanvas, {
                type: 'bar',
                data: {
                    labels: @json($semesterOrgLabels),
                    datasets: [{
                        label: 'Collected',
                        data: @json($semesterOrgData),
                        backgroundColor: '#1a7a41',
                        borderRadius: 7,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => moneyTick(context.parsed.x),
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#edf2ef' },
                            ticks: { callback: moneyTick },
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } },
                        },
                    },
                },
            });
        }

        const orgTypeCanvas = document.getElementById('adminOrgTypeChart');
        if (orgTypeCanvas) {
            new Chart(orgTypeCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json($orgTypeLabels),
                    datasets: [{
                        data: @json($orgTypeData),
                        backgroundColor: ['#1a7a41', '#2563eb', '#7c3aed', '#6b7280'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                    }],
                },
                options: {
                    cutout: '64%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                },
            });
        }
    });
</script>
@endpush
