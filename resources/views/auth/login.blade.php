<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <h2 class="mb-4 text-center text-xl font-bold">{{ __('Log in') }}</h2>

        <div>
            <x-input-label for="login" :value="__('Email or phone number')" />
            <x-text-input id="login" class="mt-1 block w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" dir="ltr" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" dir="ltr" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-500 shadow-sm focus:ring-brand-400" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</x-guest-layout>
