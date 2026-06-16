@props(['title' => ''])

@php $rtl = app()->getLocale() === 'fa'; @endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/rangify-icon.png') }}">
    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name', 'Rangify') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-ink-50" x-data="{ mobileOpen: false }">

    {{-- ===== Sidebar ===== --}}
    <aside class="fixed top-0 z-30 flex h-screen w-64 flex-col bg-white shadow-sm transition-transform duration-200 max-lg:w-72 max-lg:shadow-xl
                  {{ $rtl ? 'right-0 border-l border-gray-100' : 'left-0 border-r border-gray-100' }}
                  {{ $rtl ? 'max-lg:translate-x-full' : 'max-lg:-translate-x-full' }}"
           :class="mobileOpen && '!translate-x-0'">
        <div class="flex h-16 items-center border-b border-gray-100 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <img src="{{ asset('images/rangify-logo.png') }}" alt="Rangify" class="h-8 w-auto">
            </a>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @php
                $navItem = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors duration-150';
                $navOn = 'bg-brand-50 font-medium text-brand-600';
                $navOff = 'text-ink-900/70 hover:bg-gray-50 hover:text-ink-900';
            @endphp

            <a href="{{ route('dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navOn : $navOff }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                <span>{{ __('Dashboard') }}</span>
            </a>

            @can('manage users')
                <a href="{{ route('admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.users.*') ? $navOn : $navOff }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    <span>{{ __('Users') }}</span>
                </a>
            @endcan

            @role('super-admin')
                <a href="{{ route('admin.tenants.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.tenants.*') ? $navOn : $navOff }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                    <span>{{ __('Tenants') }}</span>
                </a>
            @endrole

            <a href="{{ route('trial') }}" class="{{ $navItem }} {{ request()->routeIs('trial') ? $navOn : $navOff }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                <span>{{ __('Color Editor') }}</span>
            </a>

            <div class="my-2 border-t border-gray-100"></div>

            <a href="{{ route('profile.edit') }}" class="{{ $navItem }} {{ request()->routeIs('profile.*') ? $navOn : $navOff }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ __('Profile & Settings') }}</span>
            </a>

            <a href="{{ url('/') }}" class="{{ $navItem }} {{ $navOff }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.999 2.999 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
                <span>{{ __('Main Site') }}</span>
            </a>
        </nav>

        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-600">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-400">
                        @role('super-admin') {{ __('Super Admin') }} @endrole
                        @role('client-admin') {{ __('Client Admin') }} @endrole
                        @role('user') {{ __('User') }} @endrole
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="{{ __('Log Out') }}" aria-label="{{ __('Log Out') }}"
                            class="rounded-lg p-1.5 text-gray-400 transition-colors duration-150 hover:bg-red-50 hover:text-red-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- backdrop موبایل --}}
    <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="fixed inset-0 z-20 bg-black/30 lg:hidden"></div>

    {{-- ===== Main ===== --}}
    <div class="{{ $rtl ? 'lg:mr-64' : 'lg:ml-64' }}">
        <header class="sticky top-0 z-10 flex h-16 items-center gap-4 border-b border-gray-100 bg-white/80 px-6 backdrop-blur-sm">
            <button @click="mobileOpen = true" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="{{ __('Menu') }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h1 class="text-base font-semibold text-ink-900">{{ $title }}</h1>
            <div class="flex-1"></div>

            {{-- سوییچ زبان --}}
            <div class="flex items-center rounded-lg border border-gray-200 p-0.5 text-xs font-medium">
                <a href="{{ route('locale.switch', 'fa') }}"
                   class="rounded-md px-2.5 py-1 transition-colors {{ app()->getLocale() === 'fa' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-ink-900' }}">فا</a>
                <a href="{{ route('locale.switch', 'en') }}"
                   class="rounded-md px-2.5 py-1 transition-colors {{ app()->getLocale() === 'en' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-ink-900' }}">EN</a>
            </div>

            {{ $actions ?? '' }}
        </header>

        <main class="p-6">
            <x-partials.flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
