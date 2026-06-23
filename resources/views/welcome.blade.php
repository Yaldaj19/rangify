<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rangify — رنگ‌آمیزی مجازی دیوار</title>
    <link rel="icon" type="image/png" href="{{ asset('images/rangify-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="bg-ink-50 text-ink-900 antialiased">

    <div class="rg-topbar h-1.5 w-full"></div>

    <header class="border-b border-gray-200 bg-white">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/rangify-logo.png') }}" alt="Rangify" width="120" height="36" class="h-9 w-auto">
                <span class="text-sm text-gray-500 hidden sm:inline">رنگ‌آمیزی مجازی</span>
            </a>

            @if (Route::has('login'))
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                            داشبورد
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                            ورود
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                                ثبت‌نام
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main>
        <section class="max-w-6xl mx-auto px-6 pt-10 pb-8 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold mb-4 text-ink-900">
                دیوار خانه‌ات را <span class="rg-gradient-text">قبل از رنگ زدن</span> ببین
            </h1>

            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-6 leading-relaxed">
                عکس از دیوار آپلود کن، رنگ‌های مختلف را امتحان کن، خروجی واقع‌گرایانه
                با حفظ بافت و سایه. به‌علاوه پیش‌نمایش سه‌بعدی فضا.
            </p>

            <div class="flex items-center justify-center gap-4 flex-wrap">
                <a href="{{ route('trial') }}"
                   class="rg-gradient rounded-lg px-8 py-3 text-base font-semibold text-white shadow-lg">
                    🎨 تست رایگان (بدون ثبت‌نام)
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="rounded-lg border border-brand-500 px-8 py-3 text-base font-semibold text-brand-600 hover:bg-brand-50 transition">
                        ثبت‌نام
                    </a>
                @endif
            </div>
        </section>

        <section id="features" class="max-w-6xl mx-auto px-6 pb-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="w-12 h-12 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center mb-4">
                        <span class="text-2xl">🎨</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">رنگ آزاد</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        از پالت رنگ‌های آماده یا انتخاب آزاد. هر رنگی که می‌خواهی روی دیوار.
                    </p>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="w-12 h-12 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center mb-4">
                        <span class="text-2xl">🪄</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">جادوگری انتخاب</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        با Magic Wand فقط دیوار را انتخاب کن. مبل، تابلو، در و پنجره دست‌نخورده.
                    </p>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="w-12 h-12 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center mb-4">
                        <span class="text-2xl">🌐</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">پیش‌نمایش سه‌بعدی</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        فضا را از زاویه‌های مختلف ببین. واقع‌گرایانه‌تر از یک عکس ثابت.
                    </p>
                </div>

            </div>
        </section>
    </main>

    <footer class="mt-12 border-t border-gray-200">
        <div class="rg-topbar h-1 w-full"></div>
        <div class="py-8 text-center text-sm text-gray-500">
            <img src="{{ asset('images/rangify-logo.png') }}" alt="Rangify" width="110" height="32" class="mx-auto mb-3 h-8 w-auto opacity-90">
            © 2026 Rangify — ساخته‌شده با ❤️
        </div>
    </footer>

</body>
</html>
