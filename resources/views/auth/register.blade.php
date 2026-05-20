<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-center">ثبت‌نام</h2>

        <p class="text-sm text-gray-500 mb-6 text-center">
            با شماره موبایل یا ایمیل ثبت‌نام کن. رمز خودکار ساخته می‌شود.
        </p>

        <div>
            <x-input-label for="name" value="نام" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" value="شماره موبایل" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" placeholder="09xxxxxxxxx" autocomplete="tel" dir="ltr" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4 text-center text-sm text-gray-400">— یا —</div>

        <div class="mt-4">
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" autocomplete="email" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                قبلاً ثبت‌نام کرده‌اید؟
            </a>

            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
                ثبت‌نام
            </button>
        </div>
    </form>
</x-guest-layout>
