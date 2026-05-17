@extends('layouts.app')
@section('title', 'Add Organization')
@section('page-title', 'Add Organization')

@section('content')
<div>
    <div style="margin-bottom:16px">
        <a href="{{ route('admin.organizations.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#4a6356;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Organizations
        </a>
    </div>

    <div style="max-width:600px">
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="padding:16px 20px;border-bottom:1px solid #eaf0ec">
                <div style="font-size:15px;font-weight:700">New Organization</div>
                <div style="font-size:12px;color:#8aa89a;margin-top:2px">Each organization is scoped to SSC, College Council, or Department Society</div>
            </div>
            <div style="padding:24px">
                <form method="POST" action="{{ route('admin.organizations.store') }}">
                    @csrf

                    @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                        <ul style="margin:0;padding-left:18px;font-size:13px;color:#dc2626">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Organization Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. CEIT Supreme Student Council"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('name') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box" required>
                        @error('name')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Organization Type <span style="color:#dc2626">*</span></label>
                        <select name="type" id="org-type" onchange="updateScopeFields(this.value)"
                            style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('type') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white" required>
                            <option value="">— Select Type —</option>
                            <option value="UNIVERSITY_WIDE" {{ old('type') === 'UNIVERSITY_WIDE' ? 'selected' : '' }}>University-Wide — All students across the university</option>
                            <option value="COLLEGE_COUNCIL" {{ old('type') === 'COLLEGE_COUNCIL' ? 'selected' : '' }}>College Council — Scoped to a specific College</option>
                            <option value="CLASS_ORG" {{ old('type') === 'CLASS_ORG' ? 'selected' : '' }}>Class Organization — Scoped to a specific Department</option>
                            <option value="RESERVED" {{ old('type') === 'RESERVED' ? 'selected' : '' }}>Reserved — Future use</option>
                        </select>
                        @error('type')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div id="college-field" style="margin-bottom:16px;display:{{ old('type') === 'COLLEGE_COUNCIL' ? 'block' : 'none' }}">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Linked College <span style="color:#dc2626">*</span></label>
                        <select name="college_id" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('college_id') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white">
                            <option value="">— Select College —</option>
                            @foreach($colleges as $college)
                                <option value="{{ $college->id }}" {{ old('college_id') == $college->id ? 'selected' : '' }}>{{ $college->code }} — {{ $college->name }}</option>
                            @endforeach
                        </select>
                        @error('college_id')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div id="dept-field" style="margin-bottom:16px;display:{{ old('type') === 'CLASS_ORG' ? 'block' : 'none' }}">
                        <label style="display:block;font-size:12.5px;font-weight:600;color:#0f1f17;margin-bottom:5px">Linked Department <span style="color:#dc2626">*</span></label>
                        <select name="department_id" style="width:100%;padding:9px 12px;border:1.5px solid {{ $errors->has('department_id') ? '#fca5a5' : '#dde8e1' }};border-radius:8px;font-size:13.5px;outline:none;box-sizing:border-box;background:white">
                            <option value="">— Select Department —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->code }} — {{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div style="font-size:11.5px;color:#dc2626;margin-top:4px">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding-top:8px;border-top:1px solid #eaf0ec;margin-top:8px">
                        <button type="submit" class="btn-green" style="padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;border:none;cursor:pointer">
                            Save Organization
                        </button>
                        <a href="{{ route('admin.organizations.index') }}" class="btn-ghost" style="padding:8px 14px;border-radius:8px;font-size:13.5px;text-decoration:none">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function updateScopeFields(type) {
    document.getElementById('college-field').style.display = type === 'COLLEGE_COUNCIL' ? 'block' : 'none';
    document.getElementById('dept-field').style.display = type === 'CLASS_ORG' ? 'block' : 'none';
}
</script>
@endpush
@endsection
