@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div>
    <div style="margin-bottom:18px">
        <h1 style="font-size:19px;font-weight:700;color:#0f1f17">SSC Admin Dashboard</h1>
        <p style="font-size:12.5px;color:#4a6356;margin-top:2px">System-wide overview · All organizations</p>
    </div>

    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px">
        @php
            $stats = [
                ['label'=>'Total Organizations','value'=>\App\Models\Organization::count(),'sub'=>'Across all colleges','color'=>'#1a7a41','bg'=>'#e6f4ec'],
                ['label'=>'Total Students','value'=>\App\Models\Student::count(),'sub'=>'All semesters','color'=>'#2563eb','bg'=>'#eff6ff'],
                ['label'=>'Active Semester','value'=>\App\Models\AcademicYear::where('is_active',true)->first()?->name??'None','sub'=>'Currently active','color'=>'#d4a42a','bg'=>'#fdf7e3'],
                ['label'=>'System Users','value'=>\App\Models\User::count(),'sub'=>'All organizations','color'=>'#7c3aed','bg'=>'#f5f3ff'],
            ];
        @endphp
        @foreach($stats as $s)
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:18px 20px;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="float:right;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:{{ $s['bg'] }};color:{{ $s['color'] }}">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <div style="font-size:11.5px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;clear:both">{{ $s['label'] }}</div>
            <div style="font-size:22px;font-weight:700;line-height:1;color:#0f1f17">{{ $s['value'] }}</div>
            <div style="font-size:12px;color:#4a6356;margin-top:4px">{{ $s['sub'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Quick links --}}
    <div class="card">
        <div style="padding:13px 20px;border-bottom:1px solid #eaf0ec">
            <div style="font-size:14px;font-weight:700">Quick Navigation</div>
        </div>
        <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
            @php
                $links = [
                    ['label'=>'Manage Colleges','route'=>'admin.colleges.index','icon'=>'<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/>'],
                    ['label'=>'Manage Departments','route'=>'admin.departments.index','icon'=>'<path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2z"/>'],
                    ['label'=>'Manage Programs','route'=>'admin.programs.index','icon'=>'<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>'],
                    ['label'=>'Academic Years','route'=>'admin.academic-years.index','icon'=>'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="10" x2="21" y2="10"/>'],
                    ['label'=>'Organizations','route'=>'admin.organizations.index','icon'=>'<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 10-16 0"/>'],
                    ['label'=>'All Students','route'=>'admin.students.index','icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
                    ['label'=>'User Management','route'=>'admin.users.index','icon'=>'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
                    ['label'=>'Audit Logs','route'=>'admin.audit-logs.index','icon'=>'<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>'],
                ];
            @endphp
            @foreach($links as $link)
            <a href="{{ route($link['route']) }}" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid #dde8e1;border-radius:8px;text-decoration:none;transition:all .15s;color:#0f1f17" onmouseover="this.style.borderColor='#1a7a41';this.style.background='#e6f4ec'" onmouseout="this.style.borderColor='#dde8e1';this.style.background='white'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a7a41" stroke-width="2">{!! $link['icon'] !!}</svg>
                <span style="font-size:13px;font-weight:600">{{ $link['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
