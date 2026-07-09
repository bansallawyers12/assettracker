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
                <tr class="align-top hover:bg-gray-50/80 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                        {{ $u->name }}
                        @if ($u->isPrimaryAdministrator())
                            <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">{{ __('Primary admin') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if ($u->isAccountActive())
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
                        @include('admin.users.partials.row-actions', ['user' => $u])
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
