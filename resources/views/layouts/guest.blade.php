@php $rtl = app()->getLocale() === 'fa'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="256x256" href="{{ asset('images/favicon-256.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

        <title>{{ config('app.name', 'Rangify') }}</title>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
        <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    </head>
    <body class="font-sans text-ink-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 bg-ink-50">

            {{-- سوییچ زبان --}}
            <div class="absolute top-4 {{ $rtl ? 'left-4' : 'right-4' }} flex items-center rounded-lg border border-gray-200 bg-white p-0.5 text-xs font-medium">
                <a href="{{ route('locale.switch', 'fa') }}"
                   class="rounded-md px-2.5 py-1 transition-colors {{ app()->getLocale() === 'fa' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-ink-900' }}">فا</a>
                <a href="{{ route('locale.switch', 'en') }}"
                   class="rounded-md px-2.5 py-1 transition-colors {{ app()->getLocale() === 'en' ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-ink-900' }}">EN</a>
            </div>

            <div>
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/rangify-logo.png') }}" alt="Rangify" class="h-12 w-auto">
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden bg-white shadow-md sm:max-w-md sm:rounded-lg">
                <div class="rg-topbar h-1.5 w-full"></div>
                <div class="px-6 py-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
