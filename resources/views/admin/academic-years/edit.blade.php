@extends('layouts.app')
@section('title', 'Edit Academic Year')
@section('page-title', 'Edit Academic Year')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('admin.academic-years.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Academic Years
        </a>
    </div>

    <div style="max-width:520px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">Edit Academic Year</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">{{ $academicYear->name }}</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('admin.academic-years.update', $academicYear) }}">
                    @csrf
                    @method('PUT')

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Academic Year <span style="color:#dc2626">*</span></label>
                        <input type="text" name="year" value="{{ old('year', $academicYear->year) }}" placeholder="e.g. 2024-2025"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('year') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        @error('year')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Semester <span style="color:#dc2626">*</span></label>
                        <select name="semester" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('semester') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                            @foreach(['1st Semester', '2nd Semester', 'Midyear'] as $opt)
                                <option value="{{ $opt }}" @selected(old('semester', $academicYear->semester) === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('semester')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Start Date</label>
                            <input type="date" name="start_date" value="{{ old('start_date', $academicYear->start_date?->format('Y-m-d')) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('start_date') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box">
                            @error('start_date')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">End Date</label>
                            <input type="date" name="end_date" value="{{ old('end_date', $academicYear->end_date?->format('Y-m-d')) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('end_date') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box">
                            @error('end_date')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    @if($academicYear->is_active)
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#e6f4ec;border-radius:8px;margin-bottom:16px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1a7a41" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        <span style="font-size:12.5px;color:#1a7a41;font-weight:500">This is the currently active semester. Use "Set Active" on another year to change it.</span>
                    </div>
                    @endif

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Update Academic Year
                        </button>
                        <a href="{{ route('admin.academic-years.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
