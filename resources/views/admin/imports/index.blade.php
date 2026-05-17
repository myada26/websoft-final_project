@extends('layouts.app')
@section('title', 'Import Logs')
@section('page-title', 'Import Logs')

@section('content')
<div class="page-shell" x-data="{ open: false }">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Bulk Import Logs</h2>
            <p class="text-[13.5px] text-green-400 mt-1 font-medium">Track Excel/CSV imports · Download failure reports for rows that failed</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('admin.imports.template') }}"
               class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Template
            </a>
            <button @click="open = true"
                    class="px-4 py-2 rounded-lg text-[13.5px] font-bold flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white border-2 border-transparent transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload & Import
            </button>
        </div>
    </div>

    {{-- ── Flash ────────────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-300 text-green-800 text-[13.5px] font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-red-800 text-[13.5px] font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Import Log Table ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-green-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaf0ec] flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-[13px] font-bold text-green-700">Import History</span>
            <span class="ml-auto text-[12px] text-green-400 font-medium">{{ $logs->total() }} total</span>
        </div>

        @if($logs->isEmpty())
            <div class="px-5 py-12 text-center text-[13.5px] text-green-400">
                No imports yet. Upload an Excel file to get started.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="bg-[#f3f8f5] border-b border-[#e4ede7]">
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-green-600">File</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-green-600">Semester</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-green-600">Uploaded By</th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-green-600">Processed</th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-green-600">Failures</th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-green-600">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-green-600">Started</th>
                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-green-600">Report</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eaf0ec]">
                        @foreach($logs as $log)
                        <tr class="hover:bg-[#f9fbfa] transition-colors">
                            {{-- File name --}}
                            <td class="px-4 py-3 font-medium text-green-900 max-w-[200px]">
                                <span class="block truncate" title="{{ $log->filename }}">{{ $log->filename }}</span>
                                <span class="text-[11px] text-green-400 uppercase">{{ $log->type }}</span>
                            </td>

                            {{-- Semester --}}
                            <td class="px-4 py-3 text-green-700">
                                {{ $log->academicYear?->name ?? '—' }}
                            </td>

                            {{-- Uploader --}}
                            <td class="px-4 py-3 text-green-700">
                                {{ $log->uploadedBy?->username ?? '—' }}
                            </td>

                            {{-- Rows processed --}}
                            <td class="px-4 py-3 text-center font-semibold text-green-800">
                                {{ number_format($log->rows_processed) }}
                            </td>

                            {{-- Failures --}}
                            <td class="px-4 py-3 text-center font-semibold {{ $log->failures_count > 0 ? 'text-red-600' : 'text-green-400' }}">
                                {{ number_format($log->failures_count) }}
                            </td>

                            {{-- Status badge --}}
                            <td class="px-4 py-3 text-center">
                                @php
                                    $badge = match($log->status) {
                                        'SUCCESS' => ['bg-green-100 text-green-800 border-green-200', 'Completed'],
                                        'PARTIAL' => ['bg-amber-100 text-amber-800 border-amber-200', 'Partial'],
                                        'FAILED'  => ['bg-red-100   text-red-800   border-red-200',   'Failed'],
                                        'PENDING' => ['bg-blue-100  text-blue-800  border-blue-200',  'Pending'],
                                        default   => ['bg-gray-100  text-gray-700  border-gray-200',  $log->status],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badge[0] }}">
                                    {{ $badge[1] }}
                                </span>
                            </td>

                            {{-- Started at --}}
                            <td class="px-4 py-3 text-green-600 text-[12px]">
                                {{ $log->started_at?->format('M j, Y g:i A') ?? '—' }}
                            </td>

                            {{-- Download failure report --}}
                            <td class="px-4 py-3 text-center">
                                @if($log->failures_count > 0 && !empty($log->failure_details))
                                    <a href="{{ route('admin.imports.failure-report', $log) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 hover:border-red-400 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Failures ({{ number_format($log->failures_count) }})
                                    </a>
                                @elseif(in_array($log->status, ['PENDING']))
                                    <span class="text-[12px] text-blue-400 italic">Processing…</span>
                                @else
                                    <span class="text-[12px] text-green-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($logs->hasPages())
                <div class="px-5 py-4 border-t border-[#eaf0ec]">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ── Upload Modal ─────────────────────────────────────────────────────── --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-7 z-10"
             x-data="bulkImportUploader()" @click.outside="open = false">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-[17px] font-bold text-green-800">Upload Enrollment File</h3>
                <button @click="open = false" class="text-green-300 hover:text-green-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" id="importLogsForm">
                @csrf

                {{-- Drop zone --}}
                <div class="mb-5 border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
                     :class="dragging ? 'border-green-500 bg-green-50' : 'border-green-200 bg-[#f9fbfa] hover:border-green-400'"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)"
                     @click="openPicker()">

                    <input type="file" name="file" accept=".xlsx,.xls,.csv"
                           class="hidden" x-ref="fileInput" @change="handleChange($event)">

                    <template x-if="!file">
                        <div>
                            <svg class="w-9 h-9 mx-auto mb-2 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-[13px] font-semibold text-green-700">Drop your file here or click to browse</p>
                            <p class="text-[12px] text-green-400 mt-1">Accepts .xlsx, .xls, .csv · Max 20 MB</p>
                        </div>
                    </template>
                    <template x-if="file">
                        <div class="flex items-center justify-between gap-3 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="text-left min-w-0">
                                    <p class="text-[13px] font-semibold text-green-800 truncate" x-text="file.name"></p>
                                    <p class="text-[11px] text-green-500" x-text="formatSize(file.size)"></p>
                                </div>
                            </div>
                            <button type="button" @click.stop="clear()"
                                    class="flex-shrink-0 text-green-400 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <template x-if="error">
                    <p class="mb-3 text-[12.5px] text-red-600 font-medium" x-text="error"></p>
                </template>

                <p class="text-[12px] text-green-400 mb-5">
                    The import runs in the background. Refresh this page after a moment to see results.
                    <a href="{{ route('admin.imports.template') }}" class="text-green-600 font-semibold hover:underline">Download template</a>
                </p>

                <div class="flex gap-3">
                    <button type="button" @click="open = false"
                            class="flex-1 py-2.5 rounded-xl border-2 border-green-200 text-[13.5px] font-bold text-green-600 hover:bg-green-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="!file"
                            class="flex-1 py-2.5 rounded-xl text-[13.5px] font-bold text-white transition-colors"
                            :class="file ? 'bg-green-600 hover:bg-green-500 cursor-pointer' : 'bg-green-200 cursor-not-allowed'">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function bulkImportUploader() {
    return {
        dragging: false, file: null, error: null,
        handleDrop(e) {
            this.dragging = false;
            const f = e.dataTransfer?.files[0];
            if (f) this.setFile(f, e.dataTransfer);
        },
        handleChange(e) {
            const f = e.target.files[0];
            if (f) this.setFile(f, null);
        },
        setFile(f, dt) {
            const ext = f.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                this.error = 'Only .xlsx, .xls, or .csv files are accepted.';
                this.file = null;
                this.$refs.fileInput.value = '';
                return;
            }
            if (f.size > 20 * 1024 * 1024) {
                this.error = 'File size exceeds the 20 MB limit.';
                this.file = null;
                this.$refs.fileInput.value = '';
                return;
            }
            this.error = null;
            this.file = { name: f.name, size: f.size };
            if (dt) {
                const transfer = new DataTransfer();
                transfer.items.add(f);
                this.$refs.fileInput.files = transfer.files;
            }
        },
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },
        clear() { this.file = null; this.error = null; if (this.$refs.fileInput) this.$refs.fileInput.value = ''; },
        openPicker() { this.$refs.fileInput.click(); },
    };
}
</script>
@endpush
