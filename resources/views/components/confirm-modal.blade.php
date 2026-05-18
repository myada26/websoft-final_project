{{-- Reusable confirmation modal. Mounted once in layouts/app.blade.php.
     Intercepts every form submit with method != GET unless the form has
     `data-no-confirm`. Optional per-form override: `data-confirm-message="..."`
     and `data-confirm-tone="danger|primary"`. --}}
<div x-data="globalConfirmModal()" x-init="init()" x-cloak>
    <div x-show="open"
         x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog"
         aria-modal="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]" @click="cancel()"></div>

        <div x-show="open" x-transition
             class="relative z-10 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">

            <div class="flex items-start gap-4 px-6 py-5 border-b border-[#eaf0ec]">
                <div :class="tone === 'danger'
                        ? 'bg-red-50 text-red-600'
                        : 'bg-green-100 text-green-600'"
                     class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                    <template x-if="tone === 'danger'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </template>
                    <template x-if="tone !== 'danger'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l3 3 6-6m-.75-9A9 9 0 119 12.75z"/>
                        </svg>
                    </template>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-[16px] font-bold text-green-800" x-text="title"></h3>
                    <p class="mt-1 text-[13px] text-green-400 font-medium" x-text="message"></p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-[#f8fbf9] rounded-b-xl">
                <button type="button"
                        @click="cancel()"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-white border-2 border-green-200 hover:border-green-600 hover:text-green-600 text-green-400 transition-all">
                    Cancel
                </button>
                <button type="button"
                        @click="confirm()"
                        :class="tone === 'danger'
                            ? 'bg-red-600 hover:bg-red-500 border-red-600 shadow-red-600/20'
                            : 'bg-green-600 hover:bg-green-500 border-green-600 shadow-green-600/20'"
                        class="px-4 py-2 rounded-lg text-[13.5px] font-bold text-white border-2 transition-all shadow-sm"
                        x-text="confirmLabel">
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function globalConfirmModal() {
        return {
            open: false,
            title: 'Confirm action',
            message: '',
            confirmLabel: 'Confirm',
            tone: 'primary',
            targetForm: null,

            init() {
                document.addEventListener('submit', (e) => this.intercept(e), true);
            },

            intercept(e) {
                const form = e.target;
                if (!form || form.tagName !== 'FORM') return;
                if (form.hasAttribute('data-no-confirm')) return;
                if (form.dataset.confirmed === 'true') return;

                const declared = (form.getAttribute('method') || 'GET').toUpperCase();
                const override = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
                const method   = override || declared;

                if (!['POST', 'PATCH', 'PUT', 'DELETE'].includes(method)) return;

                e.preventDefault();
                e.stopImmediatePropagation();

                const presets = this.presetFor(method);
                this.title        = form.dataset.confirmTitle   || presets.title;
                this.message      = form.dataset.confirmMessage || presets.message;
                this.confirmLabel = form.dataset.confirmLabel   || presets.confirmLabel;
                this.tone         = form.dataset.confirmTone    || presets.tone;
                this.targetForm   = form;
                this.open         = true;
            },

            presetFor(method) {
                switch (method) {
                    case 'DELETE':
                        return {
                            title: 'Delete this item?',
                            message: 'This action cannot be undone.',
                            confirmLabel: 'Delete',
                            tone: 'danger',
                        };
                    case 'PATCH':
                    case 'PUT':
                        return {
                            title: 'Save changes?',
                            message: 'Please review your changes before saving.',
                            confirmLabel: 'Save',
                            tone: 'primary',
                        };
                    default:
                        return {
                            title: 'Submit this action?',
                            message: 'Please confirm to proceed.',
                            confirmLabel: 'Confirm',
                            tone: 'primary',
                        };
                }
            },

            confirm() {
                const form = this.targetForm;
                this.open = false;
                if (!form) return;
                form.dataset.confirmed = 'true';
                // Click the original submit button so its name/value gets posted (some
                // forms branch on which button was pressed); fallback to submit().
                const btn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (btn) {
                    btn.click();
                } else {
                    form.submit();
                }
            },

            cancel() {
                this.open = false;
                this.targetForm = null;
            },
        };
    }
</script>
@endpush
