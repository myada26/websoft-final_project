<header class="relative z-10 flex h-[64px] shrink-0 items-center border-b border-[#dde8e1] bg-white/95 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] backdrop-blur sm:px-6 lg:px-8">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 text-[13px] font-medium text-green-300">
            <span class="shrink-0">FCATS</span>
            <span class="text-[11px] text-[#b7c8bd]">/</span>
            <span class="truncate font-bold text-green-800">@yield('page-title', 'System View')</span>
        </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        {{-- [perf] $activeSem is provided by AppServiceProvider view composer (cached 10 min). --}}
        <div class="hidden max-w-[260px] items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-[12px] font-bold text-green-500 sm:flex">
            <span class="h-2 w-2 shrink-0 rounded-full bg-[#16a34a] shadow-[0_0_0_2px_rgba(22,163,74,0.2)]"></span>
            <span class="truncate">{{ $activeSem?->name ?? 'No Active Semester' }}</span>
        </div>

        <div class="relative" x-data="{ open: false }">
            <button type="button"
                @click="open = !open"
                :class="open ? 'border-green-600 text-green-600 bg-green-100' : 'border-green-200 bg-white text-green-400 hover:text-green-600 hover:border-green-600 hover:bg-[#f0f3f1]'"
                class="relative flex h-9 w-9 items-center justify-center rounded-lg border transition-all"
                title="Notifications">
                @include('partials.ui-icon', ['name' => 'bell', 'class' => 'w-4 h-4'])
                <span class="absolute right-2 top-2 h-2 w-2 rounded-full border-2 border-white bg-red-500"></span>
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 z-40" @click="open = false"></div>
            <div x-show="open" x-cloak x-transition class="absolute right-0 top-[calc(100%+10px)] z-50 w-[min(360px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-[#dde8e1] bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-[#eaf0ec] bg-[#f8fbf9] px-4 py-3">
                    <div>
                        <h3 class="text-[14px] font-bold text-green-800">Notifications</h3>
                        <p class="text-[11.5px] font-medium text-green-300">Recent system activity</p>
                    </div>
                    <button type="button" class="text-[11.5px] font-bold text-green-600 hover:text-green-500">Mark read</button>
                </div>
                <div class="max-h-[380px] overflow-y-auto">
                    @foreach([
                        ['title' => 'New Void Request', 'text' => 'A void request is waiting for review.', 'time' => '10 mins ago', 'tone' => 'red'],
                        ['title' => 'Remittance Approved', 'text' => 'A remittance batch was approved.', 'time' => '1 hour ago', 'tone' => 'green'],
                        ['title' => 'System Maintenance', 'text' => 'FCATS maintenance notice is available.', 'time' => '5 hours ago', 'tone' => 'gray'],
                    ] as $notice)
                        <div class="flex cursor-pointer gap-3 border-b border-[#eaf0ec] p-4 transition-colors last:border-b-0 hover:bg-[#f8fbf9]">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $notice['tone'] === 'red' ? 'bg-red-50 text-red-600' : ($notice['tone'] === 'green' ? 'bg-green-100 text-green-600' : 'bg-[#f0f3f1] text-green-400') }}">
                                @include('partials.ui-icon', ['name' => $notice['tone'] === 'red' ? 'x-circle' : ($notice['tone'] === 'green' ? 'file-text' : 'settings'), 'class' => 'w-4 h-4'])
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="mb-0.5 flex items-start justify-between gap-2">
                                    <span class="truncate text-[13px] font-bold text-green-800">{{ $notice['title'] }}</span>
                                    <span class="mt-0.5 shrink-0 whitespace-nowrap text-[10.5px] font-bold text-green-300">{{ $notice['time'] }}</span>
                                </div>
                                <p class="text-[12.5px] font-medium leading-snug text-green-400">{{ $notice['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-[#eaf0ec] bg-white p-3">
                    <button type="button" class="w-full rounded-lg py-2 text-[12.5px] font-bold text-green-600 transition-colors hover:bg-[#f0f3f1] hover:text-green-500">View All Notifications</button>
                </div>
            </div>
        </div>

        @auth
            <div class="flex min-w-0 items-center gap-2 border-l border-[#eaf0ec] pl-2">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-600 text-[13px] font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 2)) }}
                </div>
                <div class="hidden min-w-0 md:block">
                    <p class="truncate text-[13px] font-bold leading-tight text-green-800">{{ auth()->user()->username }}</p>
                    <p class="truncate text-[11px] font-medium text-green-300">{{ auth()->user()->isAdmin() ? 'Super Administrator' : (auth()->user()->role ?? 'Org User') }}</p>
                </div>
            </div>
        @endauth
    </div>
</header>
