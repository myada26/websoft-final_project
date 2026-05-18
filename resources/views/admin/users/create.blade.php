@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')

@section('content')
<div class="page-shell-narrow" x-data="studentUserForm('{{ route('admin.users.lookup-student') }}')" x-init="initFromOld()">
    <div>
        <a href="{{ route('admin.users.index') }}" class="btn-ghost px-0">
            <span aria-hidden="true">&lt;</span>
            Back to Users
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">New System User</h2>
                <p class="panel-subtitle">Create an account from an existing student profile.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="panel-body space-y-5">
            @csrf

            @if($errors->any())
                <div class="alert-error">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="student_number" class="form-label">Student ID</label>
                    <input id="student_number"
                           name="student_number"
                           value="{{ old('student_number') }}"
                           x-model.debounce.500ms="studentNumber"
                           @input.debounce.500ms="lookupStudent()"
                           class="form-control"
                           placeholder="e.g. 2024000001"
                           autocomplete="off"
                           required>
                    <p class="form-help" x-text="lookupMessage || 'Name fields are filled from the student record.'"></p>
                    @error('student_number')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="username" class="form-label">Username</label>
                    <input id="username"
                           name="username"
                           value="{{ old('username') }}"
                           class="form-control font-mono"
                           placeholder="Auto-generated if left blank">
                    @error('username')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="first_name" class="form-label">First Name</label>
                    <input id="first_name" x-model="student.first_name" class="form-control bg-green-50" readonly>
                </div>
                <div>
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input id="middle_name" x-model="student.middle_name" class="form-control bg-green-50" readonly>
                </div>
                <div>
                    <label for="last_name" class="form-label">Last Name</label>
                    <input id="last_name" x-model="student.last_name" class="form-control bg-green-50" readonly>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="SSC_ADMIN" @selected(old('role') === 'SSC_ADMIN')>SSC Admin</option>
                        <option value="CHAIRPERSON" @selected(old('role') === 'CHAIRPERSON')>Chairperson</option>
                        <option value="TREASURER" @selected(old('role') === 'TREASURER')>Treasurer</option>
                        <option value="COLLECTOR" @selected(old('role') === 'COLLECTOR')>Collector</option>
                        <option value="AUDITOR" @selected(old('role') === 'AUDITOR')>Auditor</option>
                        <option value="SECRETARY" @selected(old('role') === 'SECRETARY')>Secretary</option>
                    </select>
                    @error('role')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="organization_id" class="form-label">Organization</label>
                    <select id="organization_id" name="organization_id" class="form-control" required>
                        <option value="">Select Organization</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>{{ $org->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="password" class="form-label">Temporary Password</label>
                <input id="password"
                       type="password"
                       name="password"
                       class="form-control"
                       placeholder="Leave blank to generate and email a temporary password"
                       autocomplete="new-password">
                <p class="form-help">New accounts are always required to change this password after login.</p>
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <label class="inline-flex items-center gap-2 text-[13px] font-bold text-green-800">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 accent-green-600">
                Active account
            </label>

            <div class="panel-footer -mx-5 -mb-5">
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-green">Create User</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function studentUserForm(lookupUrl) {
    return {
        studentNumber: @json(old('student_number', '')),
        lookupMessage: '',
        student: {
            first_name: '',
            middle_name: '',
            last_name: '',
        },

        initFromOld() {
            if (this.studentNumber) {
                this.lookupStudent();
            }
        },

        async lookupStudent() {
            this.student = { first_name: '', middle_name: '', last_name: '' };
            this.lookupMessage = '';

            if (!this.studentNumber || this.studentNumber.length < 3) {
                return;
            }

            try {
                const url = new URL(lookupUrl, window.location.origin);
                url.searchParams.set('student_number', this.studentNumber);

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    this.lookupMessage = 'No matching student found.';
                    return;
                }

                const data = await response.json();
                this.student = {
                    first_name: data.first_name || '',
                    middle_name: data.middle_name || '',
                    last_name: data.last_name || '',
                };
                this.lookupMessage = data.email
                    ? 'Student profile found.'
                    : 'Student profile found. Add a manual password because no email is recorded.';
            } catch (error) {
                this.lookupMessage = 'Student lookup is temporarily unavailable.';
            }
        },
    };
}
</script>
@endpush
