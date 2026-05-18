<header class="relative z-10 flex h-[64px] shrink-0 items-center border-b border-[#dde8e1] bg-white/95 px-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] backdrop-blur sm:px-6 lg:px-8">
    @auth
        <button
            type="button"
            @click="$store.sidebar.toggle()"
            class="mr-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-green-200 bg-white text-[18px] font-bold leading-none text-green-600 shadow-sm transition hover:border-green-600 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-200"
            :title="$store.sidebar.open ? 'Hide sidebar' : 'Show sidebar'"
            :aria-label="$store.sidebar.open ? 'Hide sidebar' : 'Show sidebar'">
            <span x-text="$store.sidebar.open ? '←' : '☰'"></span>
        </button>
    @endauth

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

        <div class="relative" x-data="notificationBell()" x-init="init()">
            <button type="button"
                @click="toggle()"
                :class="open ? 'border-green-600 text-green-600 bg-green-100' : 'border-green-200 bg-white text-green-400 hover:text-green-600 hover:border-green-600 hover:bg-[#f0f3f1]'"
                class="relative flex h-9 w-9 items-center justify-center rounded-lg border transition-all"
                title="Notifications">
                @include('partials.ui-icon', ['name' => 'bell', 'class' => 'w-4 h-4'])
                <span x-show="unreadCount > 0" x-cloak
                      class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-bold text-white">
                    <span x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                </span>
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 z-40" @click="open = false"></div>
            <div x-show="open" x-cloak x-transition class="absolute right-0 top-[calc(100%+10px)] z-50 w-[min(380px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-[#dde8e1] bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-[#eaf0ec] bg-[#f8fbf9] px-4 py-3">
                    <div>
                        <h3 class="text-[14px] font-bold text-green-800">Notifications</h3>
                        <p class="text-[11.5px] font-medium text-green-300">
                            <span x-text="unreadCount"></span> unread
                        </p>
                    </div>
                    <button type="button" @click="markAllAsRead()" :disabled="unreadCount === 0"
                            class="text-[11.5px] font-bold text-green-600 hover:text-green-500 disabled:cursor-not-allowed disabled:text-green-200">
                        Mark all read
                    </button>
                </div>
                <div class="max-h-[380px] overflow-y-auto">
                    <template x-if="loading && items.length === 0">
                        <div class="px-4 py-6 text-center text-[12px] text-green-300">Loading…</div>
                    </template>
                    <template x-if="!loading && items.length === 0">
                        <div class="px-4 py-8 text-center text-[12.5px] text-green-300">No notifications yet.</div>
                    </template>
                    <template x-for="n in items" :key="n.id">
                        <div @click="openNotification(n)"
                             :class="n.read_at ? 'bg-white' : 'bg-blue-50/40'"
                             class="group flex cursor-pointer gap-3 border-b border-[#eaf0ec] p-4 transition-colors last:border-b-0 hover:bg-[#f8fbf9]">
                            <div :class="{
                                'bg-red-50 text-red-600':    n.tone === 'red',
                                'bg-green-100 text-green-600': n.tone === 'green',
                                'bg-[#f0f3f1] text-green-400': n.tone !== 'red' && n.tone !== 'green',
                            }" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <template x-if="n.icon === 'x-circle'">
                                        <g><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></g>
                                    </template>
                                    <template x-if="n.icon === 'check-circle'">
                                        <g><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></g>
                                    </template>
                                    <template x-if="n.icon === 'file-text' || (!['x-circle','check-circle'].includes(n.icon))">
                                        <g><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></g>
                                    </template>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="mb-0.5 flex items-start justify-between gap-2">
                                    <span class="truncate text-[13px] font-bold text-green-800" x-text="n.title"></span>
                                    <span class="mt-0.5 shrink-0 whitespace-nowrap text-[10.5px] font-bold text-green-300" x-text="n.time_human"></span>
                                </div>
                                <p class="text-[12.5px] font-medium leading-snug text-green-400" x-text="n.message"></p>
                            </div>
                            <span x-show="!n.read_at" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            function notificationBell() {
                return {
                    open: false,
                    loading: false,
                    unreadCount: 0,
                    items: [],
                    pollHandle: null,

                    init() {
                        this.fetch();
                        // [perf] 60s setInterval removed — was contending with Windows
                        // file-session locks and stalling Livewire requests. Bell still
                        // refreshes when the user clicks it open (see toggle() below).
                    },

                    toggle() {
                        this.open = !this.open;
                        if (this.open) this.fetch();
                    },

                    async fetch() {
                        if (this.loading) return;
                        this.loading = true;
                        try {
                            const res = await fetch('{{ route('notifications.index') }}?per_page=5', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) return;
                            const json = await res.json();
                            this.unreadCount = json.unread_count ?? 0;
                            const merged = [...(json.unread?.data ?? []), ...(json.read?.data ?? [])];
                            this.items = merged.slice(0, 5);
                        } catch (e) {
                            console.error('notification fetch failed', e);
                        } finally {
                            this.loading = false;
                        }
                    },

                    async openNotification(n) {
                        if (!n.read_at) await this.markAsRead(n.id);
                        if (n.action_url) window.location.href = n.action_url;
                    },

                    async markAsRead(id) {
                        try {
                            const res = await fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                credentials: 'same-origin',
                            });
                            if (res.ok) {
                                const item = this.items.find(i => i.id === id);
                                if (item && !item.read_at) {
                                    item.read_at = new Date().toISOString();
                                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                                }
                            }
                        } catch (e) { console.error(e); }
                    },

                    async markAllAsRead() {
                        try {
                            const res = await fetch('{{ route('notifications.read-all') }}', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                credentials: 'same-origin',
                            });
                            if (res.ok) {
                                this.unreadCount = 0;
                                this.items.forEach(i => { if (!i.read_at) i.read_at = new Date().toISOString(); });
                            }
                        } catch (e) { console.error(e); }
                    },
                };
            }
        </script>
        @endpush

    </div>
</header>
