@extends('layouts.app')
@section('title', 'Add Fee Profile')
@section('page-title', 'Add Fee Profile')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('org.fee-profiles.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Fee Profiles
        </a>
    </div>

    <div style="max-width:520px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">New Fee Profile</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">Define the collection amount for a student category (FR-0013)</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('org.fee-profiles.store') }}">
                    @csrf

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Profile Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. SSC Fee - Regular"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        @error('name')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Student Category <span style="color:#dc2626">*</span></label>
                        <select name="category" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('category') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                            <option value="">— Select Category —</option>
                            <option value="REGULAR" {{ old('category') === 'REGULAR' ? 'selected' : '' }}>Regular</option>
                            <option value="IRREGULAR" {{ old('category') === 'IRREGULAR' ? 'selected' : '' }}>Irregular</option>
                            <option value="EXTENDEE" {{ old('category') === 'EXTENDEE' ? 'selected' : '' }}>Extendee</option>
                            <option value="EXEMPTED" {{ old('category') === 'EXEMPTED' ? 'selected' : '' }}>Exempted</option>
                        </select>
                        @error('category')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Amount (₱) <span style="color:#dc2626">*</span></label>
                        <div style="position:relative">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13.5px;color:#8aa89a;font-weight:600">₱</span>
                            <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="0" placeholder="0.00"
                                style="width:100%;padding:9px 12px 9px 26px;border:1.5px solid {{ $errors->has('amount') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        </div>
                        @error('amount')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="is_active" value="1" checked style="width:15px;height:15px;accent-color:#27a05a">
                            <span style="font-size:13px;font-weight:600;color:#0f1f17">Active — visible in POS</span>
                        </label>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Save Fee Profile
                        </button>
                        <a href="{{ route('org.fee-profiles.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection