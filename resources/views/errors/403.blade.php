<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Access blocked') }} — {{ config('app.name', 'Asset Tracker') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-16">
        <p class="text-sm font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-400">{{ __('Forbidden') }}</p>
        <h1 class="mt-2 text-2xl font-bold">{{ __('Access blocked') }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
            {{ trim($exception->getMessage()) !== '' ? $exception->getMessage() : __('You do not have permission to access this page.') }}
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ __('Go back') }}
            </a>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                {{ __('Dashboard') }}
            </a>
        </div>
    </div>
</body>
</html>
