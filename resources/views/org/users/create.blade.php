@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('org.users.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Users
        </a>
    </div>

    <div style="max-width:600px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">New Officer Account</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">Create a login for an officer in {{ auth()->user()->organization->name ?? 'your organization' }}</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('org.users.store') }}">
                    @csrf

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Username <span style="color:#dc2626">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g. jdelacruz"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('username') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                            @error('username')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Password <span style="color:#dc2626">*</span></label>
                            <input type="password" name="password" placeholder="Min. 8 characters"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('password') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                            @error('password')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Role <span style="color:#dc2626">*</span></label>
                        <select name="role" style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                            <option value="CHAIRPERSON" {{ old('role') === 'CHAIRPERSON' ? 'selected' : '' }}>Chairperson</option>
                            <option value="TREASURER" {{ old('role') === 'TREASURER' ? 'selected' : '' }}>Treasurer</option>
                            <option value="COLLECTOR" {{ old('role') === 'COLLECTOR' ? 'selected' : '' }}>Collector</option>
                            <option value="AUDITOR" {{ old('role') === 'AUDITOR' ? 'selected' : '' }}>Auditor</option>
                        </select>
                    </div>

                    {{-- Permissions --}}
                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:8px">Permissions</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                            @php
                            $perms = [
                            'students:view' => 'View Enrolled Students',
                            'students:enroll' => 'Enroll Students',
                            'pos:create' => 'Process Transactions (POS)',
                            'transactions:view' => 'View Transaction History',
                            'void:request' => 'Request Void',
                            'void:approve' => 'Approve Void',
                            'void:review' => 'Review Void Requests',
                            'remit:view' => 'View Remittances',
                            'remit:create' => 'Create Remittance',
                            'remit:verify' => 'Verify Remittance',
                            'remit:accept' => 'Accept Remittance',
                            'reports:view' => 'View Reports',
                            ];
                            @endphp
                            @foreach($perms as $slug => $label)
                            <label style="display:flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid #dde8e1;border-radius:7px;cursor:pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $slug }}"
                                    {{ in_array($slug, old('permissions', [])) ? 'checked' : '' }}
                                    style="width:14px;height:14px;accent-color:#27a05a">
                                <span style="font-size:12.5px;color:#0f1f17">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Create User
                        </button>
                        <a href="{{ route('org.users.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
