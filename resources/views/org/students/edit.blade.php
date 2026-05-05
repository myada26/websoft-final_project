@extends('layouts.app')
@section('title', 'Edit Student')
@section('page-title', 'Edit Student')

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
                <div style="font-size:15px;font-weight:700">Edit Student Enrollment</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">
                    <span style="font-family:monospace;background:#e6f4ec;color:#1a7a41;padding:1px 6px;border-radius:4px;font-size:11px">{{ $student->student_number }}</span>
                    {{ $student->full_name }}
                </div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('org.students.update', $student->student_number) }}">
                    @csrf
                    @method('PUT')

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Last Name <span style="color:#dc2626">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('last_name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">First Name <span style="color:#dc2626">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('first_name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}"
                                style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Program <span style="color:#dc2626">*</span></label>
                            <select name="program_id" style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                                @foreach($programs as $prog)
                                <option value="{{ $prog->id }}" {{ old('program_id', $enrollment->program_id ?? '') == $prog->id ? 'selected' : '' }}>{{ $prog->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Category <span style="color:#dc2626">*</span></label>
                            <select name="category" style="width:100%;padding:9px 12px;border:1.5px solid #dde8e1;border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                                @foreach(['REGULAR','IRREGULAR','EXTENDEE','EXEMPTED'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', $enrollment->category ?? 'REGULAR') === $cat ? 'selected' : '' }}>{{ ucfirst(strtolower($cat)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Save Changes
                        </button>
                        <a href="{{ route('org.students.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection