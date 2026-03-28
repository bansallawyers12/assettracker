<x-app-layout>
    <div class="py-8 lg:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Users') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Activate or deactivate accounts, reset passwords, and remove users. The primary administrator cannot be deactivated or deleted.') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('Create user') }}
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-md">
                        {{ __('Dashboard') }}
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ __('Email') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ __('Last login') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($users as $u)
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ $u->name }}
                                        @if ($u->isPrimaryAdministrator())
                                            <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">{{ __('Primary admin') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $u->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($u->is_active)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-800 dark:text-gray-200">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        @if ($u->last_login_at)
                                            {{ $u->last_login_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <div class="flex flex-col items-end gap-2">
                                            @if (! $u->isPrimaryAdministrator())
                                                @if ($u->is_active)
                                                    <form method="POST" action="{{ route('admin.users.deactivate', $u) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-amber-700 dark:text-amber-400 hover:underline text-left">{{ __('Deactivate') }}</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.users.activate', $u) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ __('Activate') }}</button>
                                                    </form>
                                                @endif
                                            @endif

                                            <details class="text-left w-full max-w-xs">
                                                <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:underline">{{ __('Reset password') }}</summary>
                                                <form method="POST" action="{{ route('admin.users.password', $u) }}" class="mt-2 space-y-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-600">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div>
                                                        <x-input-label for="pw-{{ $u->id }}" :value="__('New password')" class="sr-only" />
                                                        <x-text-input id="pw-{{ $u->id }}" class="block w-full text-sm" type="password" name="password" required autocomplete="new-password" placeholder="{{ __('New password') }}" />
                                                    </div>
                                                    <div>
                                                        <x-text-input class="block w-full text-sm" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}" />
                                                    </div>
                                                    <x-password-requirements-hint class="text-xs" />
                                                    <button type="submit" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ __('Save password') }}</button>
                                                </form>
                                            </details>

                                            @if (! $u->isPrimaryAdministrator() && ! $u->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm(@json(__('Delete this user and their data? This cannot be undone.')));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">{{ __('Delete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
