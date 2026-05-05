@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div class="max-w-6xl mx-auto pb-10">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Audit Logs</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Immutable system-wide activity trail (FR-0025) · Retained for 5 years</p>
        </div>
        <a href="{{ route('admin.audit-logs.index', ['export' => 'csv']) }}"
           class="px-4 py-2 rounded-xl text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-[#dde8e1] hover:border-[#1a7a41] hover:text-[#1a7a41] text-[#4a6356] transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export Logs
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-[#0f1f17]">Activity Records</h3>
                <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">{{ $logs->total() }} total entries</p>
            </div>
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative w-full md:w-[240px]">
                    <svg class="w-4 h-4 text-[#8aa89a] absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search logs..."
                           class="w-full pl-9 pr-3 py-2 border-2 border-[#dde8e1] rounded-xl text-[13px] font-medium text-[#0f1f17] outline-none focus:border-[#1a7a41] transition-colors">
                </div>
                <select name="action" onchange="this.form.submit()" class="border-2 border-[#dde8e1] rounded-xl py-2 px-3 text-[13px] font-medium text-[#4a6356] outline-none focus:border-[#1a7a41] bg-white cursor-pointer transition-colors">
                    <option value="">All Actions</option>
                    @foreach(['CREATE','UPDATE','DELETE','LOGIN','LOGOUT','APPROVE','REJECT','VOID'] as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-[#f8fbf9] border-b border-[#dde8e1]">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Timestamp</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">User</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Action</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Module</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-[#8aa89a] uppercase tracking-widest">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0">
                        <td class="px-6 py-4 text-[12.5px] text-[#8aa89a] font-medium whitespace-nowrap">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-[#1a7a41] bg-[#e6f4ec] px-2 py-1 rounded-md">{{ $log->user?->username ?? '—' }}</span></td>
                        <td class="px-6 py-4">
                            @php
                                $actionColors = [
                                    'CREATE' => 'bg-[#dcfce7] text-[#15803d]',
                                    'UPDATE' => 'bg-[#dbeafe] text-[#1d4ed8]',
                                    'DELETE' => 'bg-red-50 text-red-700',
                                    'APPROVE' => 'bg-[#dcfce7] text-[#15803d]',
                                    'REJECT' => 'bg-red-50 text-red-700',
                                    'VOID' => 'bg-[#fef9c3] text-[#ca8a04]',
                                    'LOGIN' => 'bg-[#f0f3f1] text-[#4a6356]',
                                    'LOGOUT' => 'bg-[#f3f4f6] text-[#4b5563]',
                                ];
                                $cls = $actionColors[$log->action] ?? 'bg-[#f3f4f6] text-[#4b5563]';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11.5px] font-bold {{ $cls }}">{{ $log->action }}</span>
                        </td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-[#4a6356]">{{ $log->entity_type ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13px] text-[#4a6356] font-medium max-w-xs truncate">{{ $log->description ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-14 text-center text-[14px] font-semibold text-[#4a6356]">No log entries found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-[#f8fbf9]">
            <span class="text-[12.5px] font-medium text-[#8aa89a]">Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}</span>
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
