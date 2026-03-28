<x-app-layout>
    <div class="py-8 lg:py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('← Back to users') }}</a>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create user') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('New accounts are created here by the primary administrator. Users can sign in with the email and password you set.') }}</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Full name')" />
                        <x-text-input id="name" class="block mt-1.5 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email address')" />
                        <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="new-password" />
                        <x-password-requirements-hint />
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                        <x-text-input id="password_confirmation" class="block mt-1.5 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-primary-button type="submit">{{ __('Create user') }}</x-primary-button>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
