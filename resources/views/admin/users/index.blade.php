@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="page-shell" x-data="{ open: false }">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">User Management</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">All system users across all organizations</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>Add User
        </a>
    </div>

    @php $hasFilters = request()->hasAny(['filter_college','filter_dept','search']); @endphp

    {{-- Cascading hierarchy filter: College → Department --}}
    <div x-data="userCascadeFilter()" x-init="init()"
         class="bg-white rounded-xl border border-green-200 shadow-sm mb-4 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaf0ec] flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm2 4h12M7 12h10M9 16h6"/></svg>
            <span class="text-[13px] font-bold text-green-700">Filter by Hierarchy</span>
            @if($hasFilters)
            <a href="{{ route('admin.users.index') }}" class="ml-auto text-[12px] font-semibold text-red-400 hover:text-red-600 flex items-center gap-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Clear Filters
            </a>
            @endif
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach(request()->only(['search']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach

            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Filter by College</label>
                <select name="filter_college" x-model="selectedCollege" @change="onCollegeChange(); $el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                    <option value="">All Colleges</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ request('filter_college') == $college->id ? 'selected' : '' }}>
                            {{ $college->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-green-400 uppercase tracking-wider mb-1.5">Filter by Department</label>
                <select name="filter_dept" x-model="selectedDept" @change="$el.form.submit()"
                    class="w-full px-3 py-2 border-2 border-green-200 rounded-lg bg-white text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none disabled:bg-[#f0f3f1] disabled:cursor-not-allowed"
                    :disabled="!selectedCollege">
                    <option value="">All Departments</option>
                    <template x-for="dept in filteredDepts" :key="dept.id">
                        <option :value="dept.id" :selected="dept.id == {{ request('filter_dept', 0) }}" x-text="dept.name"></option>
                    </template>
                </select>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">All Users</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $users->total() }} total records</p>
            </div>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2.5">
                @foreach(request()->only(['filter_college','filter_dept']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="relative w-full md:w-[240px]">
                    <svg class="w-4 h-4 text-green-300 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search users..."
                           class="w-full pl-9 pr-3 py-2 border-2 border-green-200 rounded-lg text-[13px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Username</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Organization</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Last Login</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0 group">
                        <td class="px-6 py-4 text-[14px] font-bold text-green-800">{{ $user->full_name ?? $user->username }}</td>
                        <td class="px-6 py-4"><span class="font-mono text-[13px] font-bold text-green-600 bg-green-100 px-2 py-1 rounded-md">{{ $user->username }}</span></td>
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $user->organization?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-[13px] text-green-300">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-6 py-4">
                            @if($user->locked_until && $user->locked_until->isFuture())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Locked</span>
                            @elseif($user->requires_password_change)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Password Change</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#dcfce7] text-[#15803d] text-[11.5px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-[#16a34a]"></span> Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.users.edit', $user) }}" class="p-1.5 rounded-lg text-green-300 hover:bg-green-200 hover:text-green-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-confirm-title="Reset password?" data-confirm-message="A temporary password will be emailed to {{ $user->student?->email ?? 'the linked student email' }}." data-confirm-label="Reset" data-confirm-tone="danger">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-lg text-green-300 hover:bg-amber-50 hover:text-amber-600 transition-colors" title="Reset password">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m-3-3h6m-7.5 8.5a8.5 8.5 0 1110-13.7"/></svg>
                                    </button>
                                </form>
                                @if(auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm-message="Delete user {{ $user->username }}?" data-confirm-label="Delete" data-confirm-tone="danger">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-green-300 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">No users found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#eaf0ec] flex justify-between items-center bg-green-50">
            <span class="text-[12.5px] font-medium text-green-300">Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}</span>
            {{ $users->withQueryString()->links() }}
        </div>
    </div>

    {{-- Add User Modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>
        <div class="relative bg-white rounded-xl w-full max-w-2xl shadow-2xl z-10 flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#eaf0ec] shrink-0">
                <div><h2 class="text-[18px] font-bold text-green-800">Add New User</h2><p class="text-[13px] text-green-400 mt-0.5 font-medium">Create a system user account.</p></div>
                <button @click="open = false" class="text-green-300 hover:bg-[#f0f3f1] p-2 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="flex flex-col min-h-0">
                @csrf
                <div class="p-6 overflow-y-auto space-y-6">
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Basic Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Juan" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Dela Cruz" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. juan@cmu.edu.ph" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                    </div>
                    <div>
                        <h3 class="text-[14px] font-bold text-green-600 mb-4 pb-2 border-b border-green-200">Account &amp; Access</h3>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g. juan.delacruz" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-green-50 text-[14px] font-bold font-mono text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                        <div class="mb-4">
                            <label class="block text-[13px] font-semibold text-green-400 mb-2">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Organization <span class="text-red-500">*</span></label>
                                <select name="organization_id" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                    <option value="">— Select —</option>
                                    @foreach($organizations as $org)<option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[13px] font-semibold text-green-400 mb-2">Is Admin?</label>
                                <select name="is_admin" class="w-full px-4 py-2.5 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                                    <option value="0">No (Org User)</option>
                                    <option value="1">Yes (Super Admin)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#eaf0ec] bg-green-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">Save User</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function userCascadeFilter() {
        return {
            allDepts:        @json($allDepartments),
            selectedCollege: '{{ request('filter_college', '') }}',
            selectedDept:    '{{ request('filter_dept', '') }}',
            filteredDepts:   [],

            init() {
                this.filteredDepts = this.selectedCollege
                    ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                    : [];
            },

            onCollegeChange() {
                this.filteredDepts = this.selectedCollege
                    ? this.allDepts.filter(d => String(d.college_id) === String(this.selectedCollege))
                    : [];
                this.selectedDept = '';
            },
        };
    }
</script>
@endpush
@endsection
