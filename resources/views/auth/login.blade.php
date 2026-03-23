<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Sign in to your account</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome back — enter your credentials below.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500 dark:focus:ring-blue-500 dark:bg-gray-700 dark:focus:ring-offset-gray-800" name="remember">
            <label for="remember_me" class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</label>
        </div>

        <x-primary-button class="w-full justify-center py-2.5">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>
</x-guest-layout>
