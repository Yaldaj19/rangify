<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            داشبورد
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('generated_password'))
                <div class="rounded-2xl bg-brand-50 border border-brand-200 p-6"
                     x-data="{ shown: false, copied: false }">
                    <div class="flex items-start gap-4">
                        <div class="text-3xl">🔐</div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-brand-900 mb-2">
                                @if (session('generated_password_source') === 'phone')
                                    رمز شما = شماره موبایل
                                @else
                                    رمز خودکار ساخته شد
                                @endif
                            </h3>
                            <p class="text-sm text-brand-800 mb-4">
                                این رمز را یادداشت کنید — یک‌بار نمایش داده می‌شود:
                            </p>
                            <div class="flex items-center gap-3">
                                <code class="flex-1 rounded-lg bg-white px-4 py-3 font-mono text-lg text-ink-900 select-all"
                                      dir="ltr"
                                      x-text="shown ? '{{ session('generated_password') }}' : '••••••••'"></code>
                                <button type="button"
                                        x-on:click="shown = !shown"
                                        class="rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600 transition">
                                    <span x-text="shown ? 'مخفی' : 'نمایش'"></span>
                                </button>
                                <button type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ session('generated_password') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="rounded-lg border border-brand-300 px-4 py-3 text-sm font-medium text-brand-700 hover:bg-brand-100 transition">
                                    <span x-text="copied ? '✓ کپی شد' : 'کپی'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl">
                <div class="p-6 text-gray-900">
                    <h3 class="text-xl font-bold mb-2">سلام، {{ auth()->user()->name }} 👋</h3>
                    <p class="text-gray-600">
                        خوش آمدی به Rangify. اینجا می‌تونی پروژه‌های رنگ‌آمیزی دیوارت رو مدیریت کنی.
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
