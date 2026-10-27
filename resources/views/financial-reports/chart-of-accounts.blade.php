@php
    $typeBadgeClasses = [
        'asset' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900',
        'liability' => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-900',
        'equity' => 'bg-indigo-50 text-indigo-800 ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-200 dark:ring-indigo-900',
        'income' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-900',
        'expense' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
    ];
@endphp

<x-report-shell
    title="Chart of Accounts"
    subtitle="Shared firm-wide chart — journal line counts for this entity only. Read-only; manage accounts on the firm Chart of Accounts."
    :entity="$businessEntity">

    <div class="px-6 py-4 border-b border-gray-100 print:hidden">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $accounts->count() }} account{{ $accounts->count() === 1 ? '' : 's' }} ·
            {{ (int) $accounts->sum('journal_lines_count') }} journal line{{ (int) $accounts->sum('journal_lines_count') === 1 ? '' : 's' }} for this entity
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50/90 dark:bg-gray-800/60">
                <tr class="border-b border-gray-200 dark:border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Account name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Journal lines</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($accounts as $account)
                    <tr class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20">
                        <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs font-semibold text-gray-900 dark:text-gray-100">
                            {{ $account->account_code }}
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $account->account_name }}</div>
                            @if ($account->description)
                                <div class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">{{ $account->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $typeBadgeClasses[$account->account_type] ?? 'bg-gray-50 text-gray-700 ring-gray-200' }}">
                                {{ $accountTypes[$account->account_type] ?? $account->account_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">
                            {{ $accountCategories[$account->account_category] ?? $account->account_category }}
                        </td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-gray-900 dark:text-white">
                            {{ (int) $account->journal_lines_count }}
                        </td>
                        <td class="px-4 py-3.5">
                            @if ($account->is_active)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-14 text-center text-sm text-gray-500 dark:text-gray-400">
                            No chart of accounts found. Seed or add accounts on the firm Chart of Accounts.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-report-shell>
