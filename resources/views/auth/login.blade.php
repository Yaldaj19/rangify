<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-center">ورود</h2>

        <div>
            <x-input-label for="login" value="ایمیل یا شماره موبایل" />
            <x-text-input id="login" class="block mt-1 w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password" dir="ltr" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-500 shadow-sm focus:ring-brand-400" name="remember">
                <span class="ms-2 text-sm text-gray-600">مرا به خاطر بسپار</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                    رمز را فراموش کرده‌اید؟
                </a>
            @endif

            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
                ورود
            </button>
        </div>
    </form>
</x-guest-layout>
