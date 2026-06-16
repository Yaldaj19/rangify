{{-- پیام‌های موفقیت / خطا / رمز ساخته‌شده‌ی یک‌بار مصرف --}}

@if (session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
         x-data="{ show: true }" x-show="show">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="flex-1">{{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">✕</button>
    </div>
@endif

@if (session('error'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
         x-data="{ show: true }" x-show="show">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <span class="flex-1">{{ session('error') }}</span>
        <button @click="show = false" class="text-red-400 hover:text-red-600">✕</button>
    </div>
@endif

@if (session('generated_password'))
    <div class="mb-5 rounded-xl border border-brand-200 bg-brand-50 p-5"
         x-data="{ shown: false, copied: false }">
        <div class="flex items-start gap-4">
            <div class="text-2xl">🔐</div>
            <div class="flex-1">
                <h3 class="mb-1 font-semibold text-brand-900">
                    {{ session('generated_password_for')
                        ? __('Password for :name created', ['name' => session('generated_password_for')])
                        : __('Password created') }}
                </h3>
                <p class="mb-3 text-sm text-brand-800">{{ __('Note this password — it is shown only once:') }}</p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 select-all rounded-lg bg-white px-4 py-2.5 font-mono text-lg text-ink-900" dir="ltr"
                          x-text="shown ? '{{ session('generated_password') }}' : '••••••••••'"></code>
                    <button type="button" @click="shown = !shown"
                            class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-600">
                        <span x-text="shown ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                    </button>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ session('generated_password') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="rounded-lg border border-brand-300 px-4 py-2.5 text-sm font-medium text-brand-700 transition-colors hover:bg-brand-100">
                        <span x-text="copied ? '✓ {{ __('Copied') }}' : '{{ __('Copy') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
