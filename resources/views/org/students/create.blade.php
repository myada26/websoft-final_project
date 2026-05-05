@extends('layouts.app')
@section('title', 'Enroll Student')
@section('page-title', 'Enroll Student')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('org.students.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
            </svg>
            Back to Students
        </a>
    </div>

    <div style="max-width:600px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">Enroll Student</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">
                    Manually enroll a student for {{ $activeSemester?->name ?? 'the active semester' }}
                </div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('org.students.store') }}">
                    @csrf

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Student ID / Number <span style="color:#dc2626">*</span></label>
                        <input type="text" name="student_number" value="{{ old('student_number') }}" placeholder="e.g. 2021-00123"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('student_number') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        @error('student_number')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
                        <div style="grid-column:span 1">
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Last Name <span style="color:#dc2626">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Dela Cruz"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('last_name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                            @error('last_name')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">First Name <span style="color:#dc2626">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Juan"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('first_name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="Santos"
                                style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Program <span style="color:#dc2626">*</span></label>
                            <select name="program_id" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('program_id') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                                <option value="">— Select Program —</option>
                                @foreach($programs as $prog)
                                <option value="{{ $prog->id }}" {{ old('program_id') == $prog->id ? 'selected' : '' }}>{{ $prog->code }}</option>
                                @endforeach
                            </select>
                            @error('program_id')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Category <span style="color:#dc2626">*</span></label>
                            <select name="category" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('category') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                                <option value="REGULAR" {{ old('category', 'REGULAR') === 'REGULAR' ? 'selected' : '' }}>Regular</option>
                                <option value="IRREGULAR" {{ old('category') === 'IRREGULAR' ? 'selected' : '' }}>Irregular</option>
                                <option value="EXTENDEE" {{ old('category') === 'EXTENDEE' ? 'selected' : '' }}>Extendee</option>
                                <option value="EXEMPTED" {{ old('category') === 'EXEMPTED' ? 'selected' : '' }}>Exempted</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Enroll Student
                        </button>
                        <a href="{{ route('org.students.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection