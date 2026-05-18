@extends('layouts.app')
@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="page-shell-narrow">
    <div class="page-header">
        <div>
            <div class="page-kicker">Account</div>
            <h1 class="page-title">Change Password</h1>
            <p class="page-subtitle">Update your account credentials.</p>
        </div>
    </div>

    <div class="panel">
        <form method="POST" action="{{ route('account.password.update') }}" class="panel-body space-y-5" data-no-confirm>
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert-error">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div>
                <label for="current_password" class="form-label">Current Password</label>
                <input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

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
                <a href="{{ route('account.profile') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-green">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
