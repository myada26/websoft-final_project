@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div class="page-shell">

    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Financial Reports</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">Generate and export fee collection reports for {{ auth()->user()->organization?->name ?? 'your organization' }}</p>
        </div>
    </div>

    {{-- Report Generator Card --}}
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-[#eaf0ec]">
            <h3 class="text-[15px] font-bold text-green-800">Report Configuration</h3>
            <p class="text-[12.5px] text-green-300 font-medium mt-0.5">Select parameters and export format below</p>
        </div>
        <form method="GET" action="{{ route('org.reports.index') }}" class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[13px] font-semibold text-green-400 mb-2">Report Type <span class="text-red-500">*</span></label>
                    <select name="type" class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                        <option value="collection_summary" {{ request('type') === 'collection_summary' ? 'selected' : '' }}>Collection Summary</option>
                        <option value="per_student" {{ request('type') === 'per_student' ? 'selected' : '' }}>Per-Student Breakdown</option>
                        <option value="remittance" {{ request('type') === 'remittance' ? 'selected' : '' }}>Remittance Report</option>
                        <option value="void_log" {{ request('type') === 'void_log' ? 'selected' : '' }}>Void Transaction Log</option>
                        <option value="payment_methods" {{ request('type') === 'payment_methods' ? 'selected' : '' }}>Payment Methods</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-green-400 mb-2">Semester</label>
                    <select name="semester_id" class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-800 outline-none focus:border-green-600 transition-colors cursor-pointer appearance-none">
                        <option value="">Current Semester</option>
                        @foreach($semesters as $sem)
                        <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>{{ $sem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-green-400 mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-400 outline-none focus:border-green-600 transition-colors">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-green-400 mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-3 border-2 border-green-200 rounded-lg bg-white text-[14px] font-medium text-green-400 outline-none focus:border-green-600 transition-colors">
                </div>
            </div>

            <div class="pt-2 flex flex-wrap gap-3">
                <button type="submit" name="export" value="preview"
                    class="px-5 py-2.5 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
                </button>
                <button type="submit" name="export" value="pdf"
                    class="px-5 py-2.5 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white border-2 border-transparent transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Export PDF
                </button>
                <button type="submit" name="export" value="csv"
                    class="px-5 py-2.5 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </form>
    </div>

    {{-- Preview pane --}}
    @if(isset($reportData))
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-[#eaf0ec]">
            <div>
                <h3 class="text-[15px] font-bold text-green-800">Report Preview</h3>
                <p class="text-[12.5px] text-green-300 font-medium mt-0.5">{{ $reportTitle ?? 'Collection Summary' }}</p>
            </div>
            <span class="text-[12px] font-bold text-green-300 uppercase tracking-widest">{{ $reportPeriod ?? '' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-green-50 border-b border-green-200">
                        @foreach($reportColumns ?? [] as $col)
                        <th class="px-6 py-3.5 text-[11.5px] font-bold text-green-300 uppercase tracking-widest">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $row)
                    <tr class="border-b border-[#eaf0ec] hover:bg-[#f0f3f1]/50 transition-colors last:border-b-0">
                        @foreach($row as $cell)
                        <td class="px-6 py-4 text-[13.5px] font-semibold text-green-400">{{ $cell }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-14 text-center">
        <svg class="w-14 h-14 text-green-300 opacity-30 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-[15px] font-bold text-green-400 mb-1.5">No report generated yet</h3>
        <p class="text-[13px] text-green-300 font-medium">Configure the parameters above and click <strong class="text-green-600">Preview</strong> or export directly.</p>
    </div>
    @endif

</div>
@endsection