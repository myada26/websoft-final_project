{{-- [AI Narrator] AI Financial Insights panel --}}
<div class="rounded-xl border border-[#dde8e1] bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-[#eaf0ec] px-4 py-3 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#e6f4ec] text-[#1a7a41]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v3"/>
                        <path d="M12 19v3"/>
                        <path d="m4.93 4.93 2.12 2.12"/>
                        <path d="m16.95 16.95 2.12 2.12"/>
                        <path d="M2 12h3"/>
                        <path d="M19 12h3"/>
                        <path d="m4.93 19.07 2.12-2.12"/>
                        <path d="m16.95 7.05 2.12-2.12"/>
                        <circle cx="12" cy="12" r="4"/>
                    </svg>
                </span>
                <div>
                    <div class="text-[14px] font-bold text-[#0f1f17]">AI Financial Insights</div>
                    <div class="text-[11.5px] font-medium text-[#8aa89a]">Auto-generated narrative powered by Claude</div>
                </div>
            </div>
        </div>

        <button id="export-ai-btn"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#b7dfc7] bg-[#e6f4ec] px-3 py-2 text-[12px] font-bold text-[#1a7a41] transition hover:border-[#1a7a41] hover:bg-[#1a7a41] hover:text-white disabled:cursor-not-allowed disabled:opacity-60">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span id="export-ai-label">Export PDF</span>
        </button>
    </div>

    <div class="p-4">
        @if($aiInsight && !str_contains($aiInsight, 'AI narrative unavailable'))
            <div class="rounded-lg border border-[#b7dfc7] bg-[#f0faf4] px-4 py-3">
                <p class="whitespace-pre-wrap text-[13px] leading-relaxed text-[#1f3b2d]">{{ $aiInsight }}</p>
            </div>
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-[13px] italic leading-relaxed text-amber-700">
                    {{ $aiInsight ?: 'AI narrative unavailable at this time. Please check your collection summary above.' }}
                </p>
            </div>
        @endif
    </div>

    <div class="border-t border-[#eaf0ec] px-4 py-3"
         x-data="{ question: '', answer: '', loading: false, error: '' }">
        <p class="mb-2 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">Ask about your collections</p>

        <div class="flex flex-col gap-2 sm:flex-row">
            <input
                type="text"
                x-model="question"
                x-on:input="answer = ''; error = '';"
                placeholder="Ask a question about your collections..."
                maxlength="200"
                class="min-w-0 flex-1 rounded-lg border border-[#dde8e1] px-3 py-2 text-[13px] font-medium text-[#0f1f17] outline-none transition placeholder:text-[#9ca3af] focus:border-[#1a7a41] focus:ring-1 focus:ring-[#1a7a41]"
                x-on:keydown.enter="if (!loading && question.trim()) $dispatch('ask-submit')"
            />
            <button
                x-on:click="
                    if (!question.trim() || loading) return;
                    loading = true; answer = ''; error = '';
                    fetch('{{ route('org.ai.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content
                        },
                        body: JSON.stringify({ question: question })
                    })
                    .then(r => r.ok ? r.json() : Promise.reject(r.status))
                    .then(data => { answer = data.answer ?? '' })
                    .catch(() => { error = 'Could not get an answer. Please try again.' })
                    .finally(() => { loading = false })
                "
                :disabled="loading || !question.trim()"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-[#1a7a41] px-4 py-2 text-[12px] font-bold text-white transition hover:bg-[#14532d] disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="loading" class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span x-text="loading ? 'Asking...' : 'Ask'"></span>
            </button>
        </div>

        <div x-show="answer" x-cloak class="mt-3 rounded-lg border border-[#dde8e1] bg-[#fbfdfc] px-4 py-3">
            <p class="mb-1 text-[10.5px] font-bold uppercase tracking-wider text-[#8aa89a]">AI Answer</p>
            <p class="text-[13px] leading-relaxed text-[#0f1f17]" x-text="answer"></p>
        </div>

        <div x-show="error" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2">
            <p class="text-[13px] text-red-600" x-text="error"></p>
        </div>
    </div>
</div>

{{-- Export PDF - vanilla JS chart capture --}}
<script>
(function () {
const exportAiButton = document.getElementById('export-ai-btn');

if (exportAiButton) {
    exportAiButton.addEventListener('click', function () {
        const btn = this;
        const label = document.getElementById('export-ai-label');

        btn.disabled = true;
        label.textContent = 'Generating...';

        const collectionCanvas = document.getElementById('collectionChart');
        const paymentCanvas = document.getElementById('paymentChart');

        const charts = {
            collection: collectionCanvas ? collectionCanvas.toDataURL('image/png') : null,
            payment: paymentCanvas ? paymentCanvas.toDataURL('image/png') : null,
        };

        fetch('{{ route('org.reports.ai.export') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ charts }),
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Export failed: ' + res.status);
            return res.blob();
        })
        .then(function (blob) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'fcats-ai-report.pdf';
            link.click();
            URL.revokeObjectURL(url);
        })
        .catch(function (err) {
            alert('PDF export failed. Please try again.\n' + err.message);
        })
        .finally(function () {
            btn.disabled = false;
            label.textContent = 'Export PDF';
        });
    });
}
})();
</script>
