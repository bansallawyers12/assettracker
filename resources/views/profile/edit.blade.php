@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');
@endphp

<x-app-layout>
    <div class="profile-page py-8 lg:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Account settings') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Manage your profile information, password, and security preferences.') }}
                </p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200">
                    @switch(session('status'))
                        @case('profile-updated')
                            {{ __('Profile information saved.') }}
                            @break
                        @case('password-updated')
                            {{ __('Password updated successfully.') }}
                            @break
                        @case('two-factor-enabled')
                            {{ __('Two-factor authentication enabled.') }}
                            @break
                        @case('two-factor-disabled')
                            {{ __('Two-factor authentication disabled.') }}
                            @break
                        @default
                            {{ session('status') }}
                    @endswitch
                </div>
            @endif

            @if ($errors->any() && ! $errors->updatePassword->any() && ! $errors->userDeletion->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
                <aside class="lg:col-span-4 xl:col-span-3">
                    <div class="profile-page-card p-5 lg:sticky lg:top-6">
                        <div class="flex items-start gap-4">
                            <div class="profile-summary-avatar shrink-0" aria-hidden="true">
                                {{ $initials ?: '?' }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 space-y-3 border-t border-gray-100 pt-5 text-sm dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Account') }}</dt>
                                <dd>
                                    @if ($user->isAccountActive())
                                        <span class="profile-badge profile-badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="profile-badge profile-badge-muted">{{ __('Inactive') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Two-factor') }}</dt>
                                <dd>
                                    @if ($user->hasFullyEnabledTwoFactor())
                                        <span class="profile-badge profile-badge-success">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="profile-badge profile-badge-warning">{{ __('Off') }}</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($user->last_login_at)
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Last login') }}</dt>
                                    <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $user->last_login_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                                    </dd>
                                </div>
                            @endif
                            @if ($user->created_at)
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Member since') }}</dt>
                                    <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $user->created_at->timezone(config('app.timezone'))->format('M Y') }}
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <nav class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700" aria-label="{{ __('Profile sections') }}">
                            <ul class="space-y-1 text-sm">
                                <li>
                                    <a href="#profile-information" class="profile-nav-link">{{ __('Profile') }}</a>
                                </li>
                                <li>
                                    <a href="#update-password" class="profile-nav-link">{{ __('Password') }}</a>
                                </li>
                                <li>
                                    <a href="#two-factor" class="profile-nav-link">{{ __('Security') }}</a>
                                </li>
                                <li>
                                    <a href="#delete-account" class="profile-nav-link profile-nav-link-danger">{{ __('Delete account') }}</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </aside>

                <div class="lg:col-span-8 xl:col-span-9 space-y-6">
                    <section id="profile-information" class="profile-page-card scroll-mt-6">
                        @include('profile.partials.update-profile-information-form')
                    </section>

                    <section id="update-password" class="profile-page-card scroll-mt-6">
                        @include('profile.partials.update-password-form')
                    </section>

                    <section id="two-factor" class="profile-page-card scroll-mt-6">
                        @include('profile.partials.two-factor-form')
                    </section>

                    <section id="delete-account" class="profile-page-card profile-page-card-danger scroll-mt-6">
                        @include('profile.partials.delete-user-form')
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
