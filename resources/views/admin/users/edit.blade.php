@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('admin.users.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Users
        </a>
    </div>

    <div style="max-width:600px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">Edit User</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">{{ $user->username }}</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

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
                            <input type="text" name="username" value="{{ old('username', $user->username) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('username') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                            @error('username')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('password') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box">
                            @error('password')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Role <span style="color:#dc2626">*</span></label>
                        <select name="role" style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                            <option value="SSC_ADMIN" {{ old('role', $user->role) === 'SSC_ADMIN' ? 'selected' : '' }}>SSC Admin</option>
                            <option value="CHAIRPERSON" {{ old('role', $user->role) === 'CHAIRPERSON' ? 'selected' : '' }}>Chairperson</option>
                            <option value="TREASURER" {{ old('role', $user->role) === 'TREASURER' ? 'selected' : '' }}>Treasurer</option>
                            <option value="COLLECTOR" {{ old('role', $user->role) === 'COLLECTOR' ? 'selected' : '' }}>Collector</option>
                            <option value="AUDITOR" {{ old('role', $user->role) === 'AUDITOR' ? 'selected' : '' }}>Auditor</option>
                            <option value="SECRETARY" {{ old('role', $user->role) === 'SECRETARY' ? 'selected' : '' }}>Secretary</option>
                        </select>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Organization</label>
                        <select name="organization_id" style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white">
                            <option value="">— None (SSC Admin) —</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id', $user->organization_id) == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} style="width:15px;height:15px;accent-color:#27a05a">
                            <span style="font-size:13px;font-weight:600;color:#0f1f17">Active account</span>
                        </label>
                    </div>

                    @if($user->isLocked())
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:16px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span style="font-size:12.5px;color:#dc2626">Account locked until {{ $user->locked_until->format('M d, Y H:i') }}</span>
                    </div>
                    @endif

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Update User
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
