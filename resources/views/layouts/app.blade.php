<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Asset Tracker') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            @include('layouts.navigation')

            @if (session('2fa_reminder') && Auth::check() && ! Auth::user()->hasFullyEnabledTwoFactor())
                <div class="bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 px-4 py-3">
                    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm text-amber-900 dark:text-amber-100">
                        <p class="font-medium">{{ session('2fa_reminder') }}</p>
                        <a href="{{ route('two-factor.setup') }}" class="inline-flex items-center justify-center font-semibold text-amber-900 dark:text-amber-50 underline decoration-2 underline-offset-2 shrink-0">
                            {{ __('Set up two-factor authentication') }}
                        </a>
                    </div>
                </div>
            @endif

            @isset($header)
                <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
