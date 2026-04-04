<x-app-layout>
<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Chart of Accounts
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Shared by all business entities. Balances by entity appear on financial reports.
            </p>
        </div>

        <div class="flex flex-col sm:items-end gap-2 shrink-0">
            <a href="{{ route('chart-of-accounts.create') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                + Add Chart of Account
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 rounded border border-green-400 dark:border-green-700 bg-green-100 dark:bg-green-900/30 px-4 py-3 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-red-400 dark:border-red-700 bg-red-100 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Table --}}
    @if($accounts->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 px-6 py-12 text-center">
            <p class="font-medium text-gray-700 dark:text-gray-300">No chart of accounts found.</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                Run <code class="rounded bg-gray-200 dark:bg-gray-700 px-1 text-xs">php artisan db:seed --class=ChartOfAccountSeeder</code>
                or use the button above to add accounts manually.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg shadow ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Account Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Category</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Journal lines</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($accounts as $account)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                    {{ $account->account_code }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    <span @if($account->description) title="{{ $account->description }}" @endif class="cursor-default">
                                        {{ $account->account_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium
                                        @if($account->account_type === 'asset') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                        @elseif($account->account_type === 'liability') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                        @elseif($account->account_type === 'equity') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                        @elseif($account->account_type === 'income') bg-yellow-100 text-yellow-900 dark:bg-yellow-900/30 dark:text-yellow-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($account->account_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $account->account_category) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-900 dark:text-gray-100">
                                    {{ (int) ($account->journal_lines_count ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($account->is_active)
                                        <span class="text-green-600 dark:text-green-400">Active</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('chart-of-accounts.edit', $account) }}"
                                       class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mr-3">Edit</a>
                                    @if((int) ($account->journal_lines_count ?? 0) === 0)
                                        <form method="POST"
                                              action="{{ route('chart-of-accounts.destroy', $account) }}"
                                              class="inline"
                                              onsubmit="return confirm('Delete this account? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ $accounts->count() }} account{{ $accounts->count() === 1 ? '' : 's' }}
            </div>
        </div>
    @endif

</div>
</x-app-layout>
