<aside class="h-full w-[260px] min-h-screen bg-green-900 flex flex-col shrink-0 shadow-xl z-20" x-data="{ showLogoutConfirm: false }">
    <div class="px-6 py-5 flex items-center gap-3.5 border-b border-white/10 shrink-0">
        <div class="w-9 h-9 bg-gold-500 rounded-lg flex items-center justify-center shrink-0 shadow-inner">
            <svg class="w-5 h-5 text-green-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 3 9 5-9 5-9-5 9-5Z"></path>
                <path d="m3 13 9 5 9-5"></path>
                <path d="m3 18 9 5 9-5"></path>
            </svg>
        </div>
        <div>
            <strong class="block text-[15px] font-bold text-white leading-tight">FCATS</strong>
            <span class="text-[11px] text-[#b7dfc7] font-medium leading-tight">Admin Panel</span>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3">

        {{-- Dashboard — pinned at top, always visible to SSC_ADMIN --}}
        @php $dashActive = request()->routeIs('admin.dashboard'); @endphp
        <div class="mb-3">
            <a href="{{ route('admin.dashboard') }}"
               class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13.5px] font-medium transition-all {{ $dashActive ? 'bg-green-600 text-white shadow-sm' : 'text-[#b7dfc7] hover:bg-white/5 hover:text-white' }}">
                @include('partials.ui-icon', ['name' => 'dashboard', 'class' => 'w-4 h-4 shrink-0 '.($dashActive ? 'opacity-100' : 'opacity-70')])
                <span class="truncate">Dashboard</span>
            </a>
        </div>

        @php
            $user = auth()->user();
            $allowed = fn (array $item) => $user?->hasRole($item['roles'] ?? []);
            $sections = [
                'System Config' => [
                    ['route' => 'admin.colleges.index', 'active' => 'admin.colleges.*', 'label' => 'Manage Colleges', 'icon' => 'building2', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.departments.index', 'active' => 'admin.departments.*', 'label' => 'Manage Departments', 'icon' => 'building', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.programs.index', 'active' => 'admin.programs.*', 'label' => 'Manage Programs', 'icon' => 'layers', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.academic-years.index', 'active' => 'admin.academic-years.*', 'label' => 'Academic Years', 'icon' => 'calendar', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.organizations.index', 'active' => 'admin.organizations.*', 'label' => 'Manage Organizations', 'icon' => 'users', 'roles' => ['SSC_ADMIN']],
                ],
                'Students' => [
                    ['route' => 'admin.students.index', 'active' => 'admin.students.*', 'label' => 'Enrolled Students', 'icon' => 'users', 'roles' => ['SSC_ADMIN']],
                ],
                'System' => [
                    ['route' => 'admin.fee-profiles.index', 'active' => 'admin.fee-profiles.*', 'label' => 'Fee Profiles', 'icon' => 'receipt', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'User Management', 'icon' => 'settings', 'roles' => ['SSC_ADMIN']],
                    ['route' => 'admin.audit-logs.index', 'active' => 'admin.audit-logs.*', 'label' => 'Audit Logs', 'icon' => 'file-clock', 'roles' => ['SSC_ADMIN']],
                ],
            ];
        @endphp

        @foreach($sections as $section => $items)
            @php $items = array_values(array_filter($items, $allowed)); @endphp
            @continue(empty($items))
            <div class="mb-4">
                <div class="text-[10px] font-bold tracking-widest uppercase text-green-300 px-3 mb-2">{{ $section }}</div>
                <div class="space-y-0.5">
                    @foreach($items as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13.5px] font-medium transition-all {{ $isActive ? 'bg-green-600 text-white shadow-sm' : 'text-[#b7dfc7] hover:bg-white/5 hover:text-white' }}">
                            @include('partials.ui-icon', ['name' => $item['icon'], 'class' => 'w-4 h-4 shrink-0 '.($isActive ? 'opacity-100' : 'opacity-70')])
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="relative mt-auto p-4 border-t border-white/10 shrink-0 bg-[#0a3816]/30">
        <div class="flex items-center gap-3 w-full p-2 -mx-2 rounded-lg text-left">
            <div class="w-9 h-9 bg-green-600 rounded-full flex items-center justify-center text-[13px] font-bold text-white shrink-0">
                {{ strtoupper(substr(auth()->user()->username ?? 'SA', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13.5px] font-bold text-white truncate">{{ auth()->user()->username ?? 'SSC Admin' }}</div>
                <div class="text-[11px] text-[#b7dfc7] font-medium truncate">Super Administrator</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm">
                @csrf
                <button type="button" @click="showLogoutConfirm = true" title="Sign Out" class="p-1.5 rounded-lg text-white/50 hover:bg-white/5 hover:text-white transition-colors">
                    @include('partials.ui-icon', ['name' => 'logout', 'class' => 'w-4 h-4'])
                </button>
            </form>
        </div>
    </div>

    <div x-show="showLogoutConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="showLogoutConfirm = false"></div>
        <div x-show="showLogoutConfirm"
             x-transition
             role="dialog"
             aria-modal="true"
             aria-labelledby="logout-modal-title-admin"
             class="relative w-full max-w-sm rounded-xl border border-[#dde8e1] bg-white shadow-2xl">
            <div class="flex items-start gap-3 border-b border-[#eaf0ec] px-5 py-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    @include('partials.ui-icon', ['name' => 'logout', 'class' => 'w-5 h-5'])
                </div>
                <div class="min-w-0">
                    <h2 id="logout-modal-title-admin" class="text-[15px] font-bold text-[#0f1f17]">Sign out?</h2>
                    <p class="mt-1 text-[13px] leading-relaxed text-[#4a6356]">Are you sure you want to sign out of FCATS?</p>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" @click="showLogoutConfirm = false" class="btn-ghost">Cancel</button>
                <button type="button" @click="$refs.logoutForm.submit()" class="btn-danger">Yes, Sign Out</button>
            </div>
        </div>
    </div>
</aside>
