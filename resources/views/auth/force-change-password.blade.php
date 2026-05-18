@extends('layouts.app')
@section('title', 'Change Password')
@section('page-title', 'Password Required')

@section('content')
<div class="page-shell-narrow">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h1 class="panel-title">Set a New Password</h1>
                <p class="panel-subtitle">Your account is using a temporary password. Update it before continuing.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.force-change.update') }}" class="panel-body space-y-5" data-no-confirm>
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert-error">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div>
                <label for="password" class="form-label">New Password</label>
                <input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required>
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
            </div>

            <div class="panel-footer -mx-5 -mb-5">
                <span class="text-[12.5px] font-medium text-green-300">This change unlocks the rest of FCATS.</span>
                <button type="submit" class="btn-green">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
