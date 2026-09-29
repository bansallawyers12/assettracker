<x-app-layout>
    @php
        $totalCount = $users->total();
        $activeCount = \App\Models\User::where('is_active', true)->count();
        $staffCount = \App\Models\User::where('app_role', \App\Enums\AppRole::Staff)->count();
        $adminCount = \App\Models\User::where('app_role', \App\Enums\AppRole::Administrator)->count();
        if ($adminCount === 0) {
            $adminCount = 1;
        }
    @endphp

    <div
        class="admin-users-workspace min-h-screen bg-linear-to-b from-gray-50/60 via-gray-50 to-gray-100/40 py-8 lg:py-10 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950"
        data-workspace-url="{{ route('admin.users.workspace') }}"
        data-create-form-url="{{ route('admin.users.form.create') }}"
        data-password-confirm-url="{{ route('password.confirm') }}"
        data-current-page="{{ $users->currentPage() }}"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 dark:bg-blue-950/60 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-400/20">
                            <x-lucide-shield-alert class="h-3.5 w-3.5" />
                            {{ __('Administration') }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ __('Users') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-2xl leading-relaxed">
                        {{ __('Manage staff accounts, assign application roles, reset credentials, and monitor user logins.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 shrink-0">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-2xs transition-colors"
                    >
                        <x-lucide-layout-dashboard class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        {{ __('Dashboard') }}
                    </a>

                    <button
                        type="button"
                        data-user-action="create"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:shadow-md transition-all duration-150"
                    >
                        <x-lucide-user-plus class="h-4 w-4" />
                        {{ __('Create user') }}
                    </button>
                </div>
            </div>

            {{-- Summary Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Users --}}
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Users') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($totalCount) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <x-lucide-users class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-lucide-shield class="h-3.5 w-3.5 text-gray-400" />
                        {{ __('Registered system accounts') }}
                    </p>
                </div>

                {{-- Active Accounts --}}
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('Active Accounts') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($activeCount) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <x-lucide-user-check class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-medium">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        {{ __('Able to sign in and authenticate') }}
                    </p>
                </div>

                {{-- Staff Users --}}
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ __('Staff Users') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($staffCount) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <x-lucide-briefcase class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-lucide-edit-3 class="h-3.5 w-3.5 text-gray-400" />
                        {{ __('Authorized portfolio editors') }}
                    </p>
                </div>

                {{-- Administrators --}}
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">{{ __('Administrators') }}</p>
                            <p class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($adminCount) }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <x-lucide-shield-check class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <x-lucide-key-round class="h-3.5 w-3.5 text-gray-400" />
                        {{ __('Full administrative control') }}
                    </p>
                </div>
            </div>

            {{-- Security Policy Notice --}}
            <div class="rounded-2xl border border-blue-200/80 bg-linear-to-r from-blue-50/70 via-indigo-50/30 to-white dark:border-blue-900/50 dark:bg-blue-950/20 dark:from-blue-950/30 dark:to-gray-800/40 p-4 sm:p-5 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 mt-0.5">
                        <x-lucide-shield-check class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ __('Primary administrator protection') }}
                        </p>
                        <p class="mt-0.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            {{ __('The primary administrator account cannot be deactivated or deleted from this workspace. Password management for this account is handled via') }}
                            <a href="{{ route('profile.edit') }}" class="font-semibold text-blue-600 dark:text-blue-400 underline underline-offset-2 hover:text-blue-700 dark:hover:text-blue-300">{{ __('Account Profile') }}</a>
                            {{ __('or the server console.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div id="admin-users-alerts"></div>

            {{-- Table Card --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800">
                <div data-admin-users-list>
                    @include('admin.users.partials.list', ['users' => $users, 'tableSort' => $tableSort])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
