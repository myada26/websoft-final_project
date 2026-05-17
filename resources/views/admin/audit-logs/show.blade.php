@extends('layouts.app')
@section('title', 'Audit Log Detail')
@section('page-title', 'Audit Log Detail')

@section('content')
<div class="page-shell">

    {{-- Back + header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.audit-logs.index') }}"
           class="flex items-center gap-1.5 text-[13px] font-semibold text-green-600 hover:text-green-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Audit Logs
        </a>
        <span class="text-green-200 font-bold">/</span>
        <span class="text-[13px] font-semibold text-green-400">Entry #{{ $auditLog->id }}</span>
    </div>

    {{-- Meta card --}}
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden mb-5">
        <div class="px-6 py-4 border-b border-[#eaf0ec] flex flex-wrap items-center gap-3">
            @php
                $actionColors = [
                    'CREATE'  => 'bg-[#dcfce7] text-[#15803d]',
                    'UPDATE'  => 'bg-[#dbeafe] text-[#1d4ed8]',
                    'DELETE'  => 'bg-red-50 text-red-700',
                    'APPROVE' => 'bg-[#dcfce7] text-[#15803d]',
                    'REJECT'  => 'bg-red-50 text-red-700',
                    'VOID'    => 'bg-[#fef9c3] text-[#ca8a04]',
                    'LOGIN'   => 'bg-[#f0f3f1] text-green-400',
                    'LOGOUT'  => 'bg-[#f3f4f6] text-[#4b5563]',
                ];
                $actionKey = collect(array_keys($actionColors))->first(fn($k) => str_contains($auditLog->action, $k));
                $cls = $actionColors[$actionKey] ?? 'bg-[#f3f4f6] text-[#4b5563]';
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[12px] font-bold {{ $cls }}">{{ $auditLog->action }}</span>
            @if($auditLog->entity_type)
                <span class="text-[13px] font-semibold text-green-600">{{ $auditLog->entity_type }}
                    @if($auditLog->entity_id) <span class="text-green-300">#{{ $auditLog->entity_id }}</span>@endif
                </span>
            @endif
        </div>
        <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-5 text-[12.5px]">
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-1">Timestamp</div>
                <div class="font-semibold text-[#0f1f17]">{{ $auditLog->timestamp?->format('M d, Y') }}</div>
                <div class="text-[#6b7280]">{{ $auditLog->timestamp?->format('H:i:s') }}</div>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-1">Performed By</div>
                @if($auditLog->user)
                    <div class="font-mono font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded inline-block">{{ $auditLog->user->username }}</div>
                @else
                    <div class="text-[#6b7280]">System</div>
                @endif
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-1">IP Address</div>
                <div class="font-mono text-[#374151]">{{ $auditLog->ip_address ?? '—' }}</div>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-1">Log ID</div>
                <div class="font-mono text-[#374151]">#{{ $auditLog->id }}</div>
            </div>
        </div>
    </div>

    {{-- Failure report download — only for import-related audit entries with failed rows --}}
    @if($relatedImportLog && $relatedImportLog->failures_count > 0 && !empty($relatedImportLog->failure_details))
    <div class="bg-white rounded-xl border border-amber-300 shadow-sm overflow-hidden mb-5">
        <div class="px-6 py-4 border-b border-amber-200 bg-amber-50/60 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[13.5px] font-bold text-amber-900">Failure Report Available</div>
                    <div class="text-[12px] text-amber-800 mt-0.5">
                        <strong>{{ $relatedImportLog->failures_count }}</strong> of <strong>{{ $relatedImportLog->rows_processed + $relatedImportLog->failures_count }}</strong> row(s) in
                        <span class="font-mono">{{ $relatedImportLog->filename }}</span> were rejected.
                        Download the report to see which rows failed and why.
                    </div>
                </div>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('admin.imports.failure-report', $relatedImportLog) }}"
                   class="px-4 py-2 rounded-lg text-[12.5px] font-bold bg-amber-600 hover:bg-amber-700 text-white transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Excel
                </a>
                <a href="{{ route('admin.imports.failure-report', [$relatedImportLog, 'format' => 'csv']) }}"
                   class="px-4 py-2 rounded-lg text-[12.5px] font-bold bg-white hover:bg-amber-50 text-amber-700 border-2 border-amber-300 hover:border-amber-500 transition-all flex items-center gap-2">
                    CSV
                </a>
            </div>
        </div>
        <div class="px-6 py-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-[12px]">
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-0.5">Import Log ID</div>
                <div class="font-mono font-bold text-[#374151]">#{{ $relatedImportLog->id }}</div>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-0.5">Status</div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                    {{ $relatedImportLog->status === 'SUCCESS' ? 'bg-green-100 text-green-700' :
                       ($relatedImportLog->status === 'PARTIAL' ? 'bg-amber-100 text-amber-800' :
                       ($relatedImportLog->status === 'FAILED' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')) }}">
                    {{ $relatedImportLog->status }}
                </span>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-0.5">Rows Processed</div>
                <div class="font-bold text-green-700">{{ $relatedImportLog->rows_processed }}</div>
            </div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-0.5">Failures</div>
                <div class="font-bold text-red-600">{{ $relatedImportLog->failures_count }}</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Diff view — shown when changed_fields / original_values are present --}}
    @if($oldValues !== null && $newValues !== null)
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden mb-5">
        <div class="px-6 py-3.5 border-b border-[#eaf0ec]">
            <div class="text-[13.5px] font-bold text-[#0f1f17]">Field Changes</div>
            <div class="text-[11.5px] text-[#8aa89a] mt-0.5">Side-by-side before / after comparison</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#eaf0ec]">
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-widest text-[#8aa89a] w-1/4">Field</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-widest text-red-400 w-[37.5%]">Before</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-widest text-green-600 w-[37.5%]">After</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($newValues as $field => $newVal)
                    @php $oldVal = $oldValues[$field] ?? null; @endphp
                    <tr class="border-b border-[#eaf0ec] last:border-b-0">
                        <td class="px-6 py-3.5 text-[12.5px] font-bold text-[#374151]">{{ str_replace('_', ' ', $field) }}</td>
                        <td class="px-6 py-3.5">
                            <span class="font-mono text-[12.5px] px-2 py-0.5 rounded bg-red-50 text-red-700 break-all">
                                {{ $oldVal !== null ? (is_array($oldVal) ? json_encode($oldVal) : $oldVal) : '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="font-mono text-[12.5px] px-2 py-0.5 rounded bg-green-50 text-green-700 break-all">
                                {{ $newVal !== null ? (is_array($newVal) ? json_encode($newVal) : $newVal) : '—' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Full details JSON --}}
    @if(!empty($details))
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="px-6 py-3.5 border-b border-[#eaf0ec]">
            <div class="text-[13.5px] font-bold text-[#0f1f17]">Full Details</div>
        </div>
        <div class="p-6">
            @if($oldValues === null && $newValues === null)
                {{-- No diff: show as key-value pairs --}}
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                    @foreach($details as $key => $val)
                    <div>
                        <dt class="text-[10.5px] font-bold uppercase tracking-widest text-[#8aa89a] mb-0.5">{{ str_replace('_', ' ', $key) }}</dt>
                        <dd class="font-mono text-[12.5px] text-[#374151] break-all">
                            {{ is_array($val) ? json_encode($val, JSON_PRETTY_PRINT) : ($val ?? '—') }}
                        </dd>
                    </div>
                    @endforeach
                </dl>
            @else
                {{-- Already shown in diff; display raw JSON for reference --}}
                <pre class="text-[11.5px] font-mono text-[#374151] bg-[#f8fbf9] rounded-lg p-4 overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
