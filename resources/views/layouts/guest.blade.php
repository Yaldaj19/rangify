<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rangify') }}</title>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    </head>
    <body class="font-sans text-ink-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-ink-50">
            <div>
                <a href="/" class="flex items-center gap-2 text-brand-600">
                    <span class="text-3xl font-bold">Rangify</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
