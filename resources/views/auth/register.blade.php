<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <h2 class="mb-4 text-center text-xl font-bold">{{ __('Register') }}</h2>

        <p class="mb-6 text-center text-sm text-gray-500">
            {{ __('Sign up with your phone or email. A password will be generated automatically.') }}
        </p>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone number')" />
            <x-text-input id="phone" class="mt-1 block w-full" type="tel" name="phone" :value="old('phone')" placeholder="09xxxxxxxxx" autocomplete="tel" dir="ltr" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4 text-center text-sm text-gray-400">{{ __('— or —') }}</div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" autocomplete="email" dir="ltr" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                {{ __('Register') }}
            </button>
        </div>
    </form>
</x-guest-layout>
