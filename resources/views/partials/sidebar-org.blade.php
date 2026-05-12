<aside class="w-[260px] min-h-screen bg-green-900 flex flex-col shrink-0 shadow-xl z-20">
    <div class="px-6 py-5 flex items-center gap-3.5 border-b border-white/10 shrink-0">
        <div class="w-9 h-9 bg-gold-500 rounded-lg flex items-center justify-center shrink-0 shadow-inner">
            @include('partials.ui-icon', ['name' => 'layers', 'class' => 'w-5 h-5 text-green-900'])
        </div>
        <div class="min-w-0">
            <strong class="block text-[15px] font-bold text-white leading-tight truncate">
                {{ auth()->user()->organization->name ?? 'COE Council' }}
            </strong>
            <span class="text-[11px] text-[#b7dfc7] font-medium leading-tight">Organization Panel</span>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3">
        @php
            $pendingVoids = isset($pendingVoidCount) ? $pendingVoidCount : 0;
            $user = auth()->user();
            $allowed = fn (array $item) => $user->hasRole($item['roles'] ?? []);
            $sections = [
                'Overview' => [
                    ['route' => 'org.dashboard', 'active' => 'org.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'roles' => ['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR', 'SECRETARY']],
                ],
                'Attendance' => [
                    ['route' => 'org.events.index',  'active' => 'org.events.*',      'label' => 'Events',     'icon' => 'calendar',  'roles' => ['CHAIRPERSON', 'AUDITOR', 'SECRETARY']],
                    ['route' => 'org.events.create', 'active' => 'org.events.create', 'label' => 'New Event',  'icon' => 'user-plus', 'roles' => ['CHAIRPERSON']],
                ],
                'Operations' => [
                    ['route' => 'org.students.index', 'active' => 'org.students.*', 'label' => 'Enrolled Students', 'icon' => 'users', 'roles' => ['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR']],
                    ['route' => 'org.students.index', 'active' => 'org.students.create', 'label' => 'Enroll Students', 'icon' => 'user-plus', 'roles' => ['CHAIRPERSON']],
                    ['route' => 'org.transactions.create', 'active' => 'org.transactions.create', 'label' => 'Create Transaction', 'icon' => 'credit-card', 'roles' => ['TREASURER', 'COLLECTOR']],
                    ['route' => 'org.transactions.index', 'active' => 'org.transactions.index', 'label' => 'Transaction History', 'icon' => 'receipt', 'roles' => ['TREASURER', 'AUDITOR']],
                    ['route' => 'org.void-requests.index', 'active' => 'org.void-requests.*', 'label' => 'Void Requests', 'icon' => 'x-circle', 'badge' => $pendingVoids, 'roles' => ['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR']],
                    ['route' => 'org.remittances.index', 'active' => 'org.remittances.*', 'label' => 'Remittances', 'icon' => 'file-text', 'roles' => ['TREASURER', 'AUDITOR']],
                ],
                'Management' => [
                    ['route' => 'org.fee-profiles.index', 'active' => 'org.fee-profiles.*', 'label' => 'Fee Profiles', 'icon' => 'layers', 'roles' => ['CHAIRPERSON']],
                    ['route' => 'org.users.index', 'active' => 'org.users.*', 'label' => 'User Management', 'icon' => 'settings', 'roles' => ['CHAIRPERSON']],
                    ['route' => 'org.documentation', 'active' => 'org.documentation', 'label' => 'Documentation', 'icon' => 'book-open', 'roles' => ['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR', 'SECRETARY']],
                    ['route' => 'org.audit-logs.index', 'active' => 'org.audit-logs.*', 'label' => 'Audit Logs', 'icon' => 'file-clock', 'roles' => ['CHAIRPERSON', 'AUDITOR']],
                    ['route' => 'org.reports.sor', 'active' => 'org.reports.sor', 'label' => 'Summary of Receipts', 'icon' => 'chart-bar', 'roles' => ['CHAIRPERSON']],
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
                            @if(($item['badge'] ?? 0) > 0)
                                <span class="ml-auto bg-gold-500 text-green-900 text-[10px] font-bold px-2 py-0.5 rounded-full min-w-5 text-center shadow-sm">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="relative mt-auto p-4 border-t border-white/10 shrink-0 bg-[#0a3816]/30">
        <div class="flex items-center gap-3 w-full p-2 -mx-2 rounded-lg text-left">
            <div class="w-9 h-9 bg-green-600 rounded-full flex items-center justify-center text-[13px] font-bold text-white shrink-0">
                {{ strtoupper(substr(auth()->user()->username ?? 'JD', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13.5px] font-bold text-white truncate">{{ auth()->user()->username ?? 'Juan dela Cruz' }}</div>
                <div class="text-[11px] text-[#b7dfc7] font-medium truncate">{{ auth()->user()->role ?? 'Chairperson' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sign Out" class="p-1.5 rounded-lg text-white/50 hover:bg-white/5 hover:text-white transition-colors">
                    @include('partials.ui-icon', ['name' => 'logout', 'class' => 'w-4 h-4'])
                </button>
            </form>
        </div>
    </div>
</aside>
