<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FCATS') — Fee Collection &amp; Tracking System</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23166534'/><path d='M50 20 L72 32 L72 56 C72 70 62 80 50 84 C38 80 28 70 28 56 L28 32 Z' fill='%234ade80' opacity='0.9'/><rect x='42' y='44' width='16' height='22' rx='3' fill='%23166534'/><circle cx='50' cy='38' r='8' fill='none' stroke='%23166534' stroke-width='4'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>

<body class="font-sans antialiased bg-[#f0f3f1] text-[#0f1f17]">

    <div class="app-shell">

        {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
        @auth
        @if(auth()->user()->isAdmin())
        @include('partials.sidebar-admin')
        @else
        @include('partials.sidebar-org')
        @endif
        @endauth

        {{-- ── Main column ─────────────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Topbar --}}
            @include('partials.header')

            {{-- Page content --}}
            <main class="app-main">

                {{-- Flash messages --}}
                @if (session('success'))
                <div class="alert-success mb-5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
                @endif

                @if (session('error'))
                <div class="alert-error mb-5">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ session('error') }}
                </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>
