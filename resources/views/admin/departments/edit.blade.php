@extends('layouts.app')
@section('title', 'Edit Department')
@section('page-title', 'Edit Department')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('admin.departments.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Departments
        </a>
    </div>

    <div style="max-width:560px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">Edit Department</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">Update department details</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('admin.departments.update', $department) }}">
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
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Parent College <span style="color:#dc2626">*</span></label>
                        <select name="college_id" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('college_id') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                            <option value="">— Select College —</option>
                            @foreach($colleges as $college)
                                <option value="{{ $college->id }}" {{ old('college_id', $department->college_id) == $college->id ? 'selected' : '' }}>{{ $college->code }} — {{ $college->name }}</option>
                            @endforeach
                        </select>
                        @error('college_id')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Department Code <span style="color:#dc2626">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $department->code) }}" maxlength="20"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('code') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;text-transform:uppercase" required>
                        @error('code')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Department Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $department->name) }}"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        @error('name')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Update Department
                        </button>
                        <a href="{{ route('admin.departments.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
