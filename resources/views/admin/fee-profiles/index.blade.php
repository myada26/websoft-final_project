@extends('layouts.app')
@section('title', 'Fee Configuration')
@section('page-title', 'Fee Configuration')

@section('content')
<div class="page-shell" x-data="{
    confirmOpen: false,
    pendingFormId: null,
    pendingOrgName: '',
    requestConfirmation(formId, orgName) {
        this.pendingFormId = formId;
        this.pendingOrgName = orgName;
        this.confirmOpen = true;
    },
    confirmSave() {
        if (this.pendingFormId) {
            document.getElementById(this.pendingFormId)?.submit();
        }
    }
}">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Fee Configuration Grid</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">
                SSC-administered semester fee setup for Regular, Extendee, and Irregular categories.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.fee-profiles.index') }}" class="flex items-center gap-2">
            <label for="organization_type" class="text-[12px] font-black uppercase tracking-widest text-green-400 whitespace-nowrap">
                Organization Type
            </label>
            <select id="organization_type"
                    name="organization_type"
                    onchange="this.form.submit()"
                    class="min-w-[220px] rounded-lg border-2 border-green-200 bg-white px-3 py-2 text-[13.5px] font-semibold text-green-800 outline-none transition-colors focus:border-green-600">
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-[#eaf0ec] flex items-center justify-between gap-4">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">Organizations</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $organizations->count() }} configurable organization(s)</p>
            </div>
            <span class="hidden sm:inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-[11.5px] font-black text-green-700">
                FR-0011 / FR-0012
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left border-collapse">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Organization Name</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Regular Fee</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Extendee Fee</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">Irregular Fee</th>
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $organization)
                        @php
                            $formId = 'fee-grid-row-'.$organization->id;
                            $profiles = $organization->feeProfilesByCategory;
                            $regular = old('regular_fee', $profiles->get('REGULAR')?->amount ?? '0.00');
                            $extendee = old('extendee_fee', $profiles->get('EXTENDEE')?->amount ?? '0.00');
                            $irregular = old('irregular_fee', $profiles->get('IRREGULAR')?->amount ?? '0.00');
                        @endphp

                        <tr class="border-b border-[#eaf0ec] last:border-b-0 hover:bg-[#f0f3f1]/50 transition-colors">
                            <td class="px-6 py-4 align-middle">
                                <div class="font-bold text-green-800 text-[14px]">{{ $organization->name }}</div>
                                <div class="text-[12px] text-green-400 font-semibold mt-0.5">
                                    {{ $typeOptions[$organization->type] ?? $organization->type }}
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[12px] font-bold text-green-400">PHP</span>
                                    <input form="{{ $formId }}" name="regular_fee" type="number" min="0" step="0.01" value="{{ $regular }}"
                                           class="w-full rounded-lg border-2 border-green-200 bg-white py-2 pl-12 pr-3 text-[13.5px] font-bold text-green-800 outline-none focus:border-green-600">
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[12px] font-bold text-green-400">PHP</span>
                                    <input form="{{ $formId }}" name="extendee_fee" type="number" min="0" step="0.01" value="{{ $extendee }}"
                                           class="w-full rounded-lg border-2 border-green-200 bg-white py-2 pl-12 pr-3 text-[13.5px] font-bold text-green-800 outline-none focus:border-green-600">
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[12px] font-bold text-green-400">PHP</span>
                                    <input form="{{ $formId }}" name="irregular_fee" type="number" min="0" step="0.01" value="{{ $irregular }}"
                                           class="w-full rounded-lg border-2 border-green-200 bg-white py-2 pl-12 pr-3 text-[13.5px] font-bold text-green-800 outline-none focus:border-green-600">
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.fee-profiles.grid-row.update') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                                    <input type="hidden" name="organization_type" value="{{ $selectedType }}">
                                </form>
                                <button type="button"
                                        @click="requestConfirmation('{{ $formId }}', @js($organization->name))"
                                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-[13px] font-bold text-white shadow-sm transition-colors hover:bg-green-500">
                                    Save Changes
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center text-[14px] font-semibold text-green-400">
                                No organizations match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="confirmOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="confirmOpen = false"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl border border-red-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-red-100 bg-red-50">
                <h3 class="text-[17px] font-black text-red-800">Confirm Fee Changes</h3>
                <p class="text-[13px] font-semibold text-red-600 mt-1">This will update the active fee profiles used by POS transactions.</p>
            </div>
            <div class="p-6">
                <p class="text-[14px] font-semibold text-green-800 leading-6">
                    Are you sure you want to save these fee changes for
                    <span class="font-black" x-text="pendingOrgName"></span>?
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                            @click="confirmOpen = false"
                            class="rounded-lg border-2 border-green-200 bg-white px-4 py-2 text-[13.5px] font-bold text-green-500 hover:border-green-600 hover:text-green-700">
                        Cancel
                    </button>
                    <button type="button"
                            @click="confirmSave()"
                            class="rounded-lg bg-red-600 px-4 py-2 text-[13.5px] font-bold text-white hover:bg-red-500">
                        Yes, Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
