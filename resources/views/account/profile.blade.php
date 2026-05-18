@extends('layouts.app')
@section('title', 'User Profile')
@section('page-title', 'User Profile')

@section('content')
<div class="page-shell-narrow">
    <div class="page-header">
        <div>
            <div class="page-kicker">Account</div>
            <h1 class="page-title">User Profile</h1>
            <p class="page-subtitle">Read-only account and linked student information.</p>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">{{ $user->student?->full_name ?? $user->username }}</h2>
                <p class="panel-subtitle">{{ $user->username }}</p>
            </div>
            <span class="badge-green">{{ $user->role }}</span>
        </div>

        <div class="panel-body grid gap-4 md:grid-cols-2">
            <div>
                <div class="form-label">Student ID</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->student?->student_number ?? 'Not linked' }}</div>
            </div>
            <div>
                <div class="form-label">Email</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->student?->email ?? 'No email recorded' }}</div>
            </div>
            <div>
                <div class="form-label">Full Name</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->student?->full_name ?? 'Not linked' }}</div>
            </div>
            <div>
                <div class="form-label">Organization</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->organization?->name ?? 'No organization' }}</div>
            </div>
            <div>
                <div class="form-label">Account Status</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
            <div>
                <div class="form-label">Last Login</div>
                <div class="text-[14px] font-bold text-green-800">{{ $user->last_login?->format('M d, Y h:i A') ?? 'Never' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
