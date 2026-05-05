<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FCATS') — Fee Collection &amp; Tracking System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>

<body class="font-sans antialiased bg-[#f0f3f1] text-[#0f1f17]">

    <div class="flex h-screen w-full overflow-hidden">

        {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
        @auth
        @if(auth()->user()->isAdmin())
        @include('partials.sidebar-admin')
        @else
        @include('partials.sidebar-org')
        @endif
        @endauth

        {{-- ── Main column ─────────────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Topbar --}}
            @include('partials.header')

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">

                {{-- Flash messages --}}
                @if (session('success'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-[#dcfce7] border border-[#86efac] text-[#15803d] text-[13px] font-semibold">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
                @endif

                @if (session('error'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-[13px] font-semibold">
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
</body>

</html>
