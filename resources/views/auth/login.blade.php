@extends('layouts.auth')
@section('title', 'Sign In')

@push('styles')
<style>
    .login-shell {
        min-height: 100vh;
        width: 100%;
        display: flex;
        overflow: hidden;
        background: #fff;
    }

    .login-panel {
        width: 100%;
        min-height: 100vh;
        background: #0d4a1e;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 25px 50px rgba(0, 0, 0, .25);
        z-index: 10;
    }

    .login-panel::before,
    .login-panel::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 255, 255, .18);
        filter: blur(48px);
        pointer-events: none;
    }

    .login-panel::before {
        width: 384px;
        height: 384px;
        top: -96px;
        left: -96px;
    }

    .login-panel::after {
        width: 256px;
        height: 256px;
        right: -48px;
        bottom: 48px;
    }

    .login-card {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, .25);
        position: relative;
        z-index: 1;
    }

    .brand-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 32px;
    }

    .brand-mark {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #d4a42a;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d4a1e;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
        flex-shrink: 0;
    }

    .brand-title {
        display: block;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.1;
        color: #0f1f17;
    }

    .brand-subtitle {
        display: block;
        font-size: 12px;
        color: #8aa89a;
        line-height: 1.2;
    }

    .login-title {
        font-size: 24px;
        font-weight: 800;
        margin: 0 0 4px;
        color: #0f1f17;
    }

    .login-copy {
        font-size: 14px;
        color: #4a6356;
        margin: 0 0 32px;
    }

    .alert-box {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        margin-bottom: 20px;
        color: #dc2626;
        font-size: 13px;
        font-weight: 600;
    }

    .field {
        margin-bottom: 20px;
    }

    .field.password {
        margin-bottom: 32px;
    }

    .field label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #4a6356;
        margin-bottom: 8px;
    }

    .input-wrap {
        position: relative;
    }

    .field input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #dde8e1;
        border-radius: 12px;
        background: #f8fbf9;
        color: #0f1f17;
        font-size: 14px;
        font-weight: 600;
        outline: none;
        box-sizing: border-box;
        transition: border-color .15s, background-color .15s;
    }

    .field input:focus {
        border-color: #1a7a41;
        background: #fff;
    }

    .field input.has-error {
        border-color: #fca5a5;
        background: #fef2f2;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #8aa89a;
        padding: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .field-error {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        font-size: 12px;
        color: #dc2626;
        font-weight: 600;
    }

    .submit-btn {
        width: 100%;
        padding: 14px 16px;
        border: 0;
        border-radius: 12px;
        background: #1a7a41;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(26, 122, 65, .2);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color .15s, transform .15s;
    }

    .submit-btn:hover {
        background: #27a05a;
        transform: translateY(-1px);
    }

    .info-panel {
        display: none;
        flex: 1;
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fbf9 0%, #eaf0ec 100%);
        position: relative;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px;
        overflow: hidden;
    }

    .info-panel::before,
    .info-panel::after {
        content: "";
        position: absolute;
        width: 600px;
        height: 600px;
        border-radius: 999px;
        pointer-events: none;
        filter: blur(48px);
    }

    .info-panel::before {
        top: 0;
        right: 0;
        background: linear-gradient(135deg, rgba(26, 122, 65, .06), transparent);
    }

    .info-panel::after {
        left: -100px;
        bottom: -100px;
        background: linear-gradient(45deg, rgba(212, 164, 42, .07), transparent);
    }

    .info-content {
        width: 100%;
        max-width: 672px;
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .info-title {
        font-size: 40px;
        line-height: 1.15;
        font-weight: 900;
        color: #0f1f17;
        margin: 0 0 16px;
    }

    .info-title span {
        color: #1a7a41;
    }

    .kicker {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #8aa89a;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        margin-bottom: 48px;
    }

    .kicker::before,
    .kicker::after {
        content: "";
        width: 64px;
        height: 1px;
        background: linear-gradient(90deg, transparent, #8aa89a);
    }

    .kicker::after {
        background: linear-gradient(90deg, #8aa89a, transparent);
    }

    .profiles {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 32px;
        margin-bottom: 56px;
        position: relative;
    }

    .profile-row {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        gap: 32px;
        width: 100%;
        position: relative;
    }

    .profile-row::before {
        content: "";
        position: absolute;
        top: 38px;
        left: 5%;
        right: 5%;
        height: 1px;
        background: linear-gradient(90deg, transparent, #dde8e1, transparent);
        z-index: -1;
    }

    .profile {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .profile-avatar {
        width: 76px;
        height: 76px;
        border-radius: 999px;
        background: linear-gradient(180deg, #fff, #f0f3f1);
        box-shadow: 0 4px 20px rgba(26, 122, 65, .08);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1a7a41;
        margin-bottom: 12px;
        ring: 4px solid #fff;
    }

    .profile-name {
        font-size: 14.5px;
        font-weight: 800;
        color: #0f1f17;
        white-space: nowrap;
    }

    .profile-role {
        font-size: 11px;
        color: #8aa89a;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-top: 2px;
        white-space: nowrap;
    }

    .profile-divider {
        width: 1px;
        height: 64px;
        background: linear-gradient(180deg, transparent, #dde8e1, transparent);
        flex-shrink: 0;
        margin: 0 4px;
    }

    .quote {
        max-width: 540px;
        margin: 0 auto;
        position: relative;
        color: #4a6356;
        font-size: 14.5px;
        line-height: 1.65;
        font-weight: 600;
    }

    .quote::before,
    .quote::after {
        position: absolute;
        color: rgba(26, 122, 65, .1);
        font-family: Georgia, serif;
        font-size: 80px;
        line-height: 1;
    }

    .quote::before {
        content: '"';
        top: -30px;
        left: -24px;
    }

    .quote::after {
        content: '"';
        right: -24px;
        bottom: -56px;
    }

    @media (min-width: 1024px) {
        .login-panel {
            width: 45%;
        }

        .info-panel {
            display: flex;
        }
    }

    @media (min-width: 1280px) {
        .login-panel {
            width: 40%;
        }
    }

    @media (max-width: 640px) {
        .login-panel {
            padding: 20px;
        }

        .login-card {
            padding: 28px;
        }
    }
</style>
@endpush

@section('content')
<div class="login-shell">
    <section class="login-panel" aria-label="Login panel">
        <div class="login-card">
            <div class="brand-row">
                <div class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div>
                    <strong class="brand-title">FCATS</strong>
                    <span class="brand-subtitle">Secure Access</span>
                </div>
            </div>

            <h1 class="login-title">Welcome back</h1>
            <p class="login-copy">Enter your credentials to access the system.</p>

            @if(session('error') || $errors->has('session') || $errors->has('locked'))
                <div class="alert-box">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') ?? $errors->first('session') ?? $errors->first('locked') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="username">Username or Email</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="e.g. admin.ssc"
                        autocomplete="username"
                        autofocus
                        class="{{ $errors->has('username') ? 'has-error' : '' }}">
                    @error('username')
                        <div class="field-error">
                            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <div class="field password">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            class="{{ $errors->has('password') ? 'has-error' : '' }}">
                        <button type="button" class="toggle-password" aria-label="Show password" onclick="const field=document.getElementById('password'); field.type = field.type === 'password' ? 'text' : 'password'; this.setAttribute('aria-label', field.type === 'password' ? 'Show password' : 'Hide password');">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">
                            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <button type="submit" class="submit-btn">
                    <span>Sign In</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </button>
            </form>
        </div>
    </section>

    <section class="info-panel" aria-label="FCATS information">
        <div class="info-content">
            <h2 class="info-title">Fee Collection <span>&amp;</span><br>Tracking System</h2>
            <div class="kicker">FCATS Platform</div>

            <div class="profiles">
                <div class="profile">
                    <div class="profile-avatar" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-8 0v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="profile-name">John Doe</div>
                    <div class="profile-role">Proponent / Developer</div>
                </div>

                <div class="profile-row">
                    @foreach([
                        ['name' => 'Jane Smith', 'role' => 'Proponent'],
                        ['name' => 'Mark Wilson', 'role' => 'Proponent'],
                    ] as $person)
                        <div class="profile">
                            <div class="profile-avatar" aria-hidden="true">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="profile-name">{{ $person['name'] }}</div>
                            <div class="profile-role">{{ $person['role'] }}</div>
                        </div>
                    @endforeach

                    <div class="profile-divider" aria-hidden="true"></div>

                    @foreach([
                        ['name' => 'Alex Johnson', 'role' => 'Developer'],
                        ['name' => 'Sarah Davis', 'role' => 'Developer'],
                    ] as $person)
                        <div class="profile">
                            <div class="profile-avatar" aria-hidden="true">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="profile-name">{{ $person['name'] }}</div>
                            <div class="profile-role">{{ $person['role'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="quote">
                FCATS is a comprehensive platform designed to streamline and secure the fee collection
                processes within the institution. Built with a focus on transparency, efficiency, and
                accountability, this system enables student organizations and administrators to seamlessly
                track transactions, generate reports, and manage financial records in real-time.
            </p>
        </div>
    </section>
</div>
@endsection
