@extends('layouts.app')
@section('title', 'Events')
@section('page-title', 'Events')

@section('content')
<div class="page-shell">
    <div class="page-header">
        <div>
            <h1 class="page-title">Attendance Events</h1>
            <p class="page-subtitle">Manage events and track member attendance.</p>
        </div>
        @if(auth()->user()->hasRole('CHAIRPERSON'))
            <div class="page-actions">
                <a href="{{ route('org.events.create') }}" class="btn-green">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Event
                </a>
            </div>
        @endif
    </div>

    <div class="space-y-3">
        @if($pendingAuditorCount > 0)
            <div class="alert-warning">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ $pendingAuditorCount }} event(s) awaiting your auditor review
            </div>
        @endif

        @if($pendingChairpersonCount > 0)
            <div class="alert-warning">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ $pendingChairpersonCount }} event(s) awaiting your final confirmation
            </div>
        @endif

        @if(session('warning'))
            <div class="alert-warning">{{ session('warning') }}</div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">All Events</h2>
                <p class="panel-subtitle">{{ $events->total() }} event{{ $events->total() === 1 ? '' : 's' }} found</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table min-w-[820px]">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        @php
                            $statusMap = [
                                'DRAFT' => ['cls' => 'badge-gray', 'label' => 'Draft'],
                                'PENDING_APPROVAL' => ['cls' => 'badge-gold', 'label' => 'Pending Auditor'],
                                'PENDING_CHAIRPERSON' => ['cls' => 'bg-blue-50 text-blue-700', 'label' => 'Pending Chairperson'],
                                'APPROVED' => ['cls' => 'badge-green', 'label' => 'Approved'],
                                'REJECTED' => ['cls' => 'badge-red', 'label' => 'Rejected'],
                            ];
                            $status = $statusMap[$event->status] ?? ['cls' => 'badge-gray', 'label' => $event->status];
                            $typeClass = $event->time_type === 'HALF_DAY' ? 'bg-sky-50 text-sky-700' : 'bg-rose-50 text-rose-700';
                        @endphp
                        <tr>
                            <td>
                                <div class="font-bold text-[#0f1f17]">{{ $event->name }}</div>
                                @if($event->venue)
                                    <div class="mt-0.5 text-[11.5px] font-medium text-green-300">{{ $event->venue }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap font-semibold text-green-400">{{ $event->date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge {{ $typeClass }}">
                                    {{ $event->time_type === 'HALF_DAY' ? 'Half Day' : 'Full Day' }}
                                </span>
                            </td>
                            <td class="font-medium text-green-400">{{ $event->academicYear?->name }}</td>
                            <td>
                                <span class="badge {{ $status['cls'] }}">{{ $status['label'] }}</span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('org.events.show', $event) }}" class="btn-outline !min-h-8 !px-3 !py-1.5 !text-[12px]">View</a>
                                    @if(auth()->user()->hasRole('SECRETARY') && $event->status === 'DRAFT')
                                        <a href="{{ route('org.attendance.sheet', $event) }}" class="btn-green !min-h-8 !px-3 !py-1.5 !text-[12px]">Open Sheet</a>
                                    @elseif(auth()->user()->hasRole('AUDITOR') && $event->status === 'PENDING_APPROVAL')
                                        <a href="{{ route('org.attendance.sheet', $event) }}" class="btn-outline !min-h-8 !px-3 !py-1.5 !text-[12px]">Review Sheet</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="font-bold text-green-400">No events yet</div>
                                @if(auth()->user()->hasRole('CHAIRPERSON'))
                                    <p class="mt-1 text-[12.5px]">Create the first event using the button above.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel-footer justify-end">
            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection
