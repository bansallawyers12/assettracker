@php
    $lineRows = old('lines', [
        ['chart_of_account_id' => '', 'debit' => '', 'credit' => '', 'description' => '', 'tracking_category_id' => '', 'tracking_sub_category_id' => ''],
        ['chart_of_account_id' => '', 'debit' => '', 'credit' => '', 'description' => '', 'tracking_category_id' => '', 'tracking_sub_category_id' => ''],
    ]);
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Manual journal entry</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Post opening balances, equity adjustments, or other entries not created by bank transactions or invoices.
                Debits must equal credits.
            </p>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-sm border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('financial-reports.journal-entries.store') }}"
              class="bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entity</label>
                    <select name="business_entity_id" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($businessEntities as $entity)
                            <option value="{{ $entity->id }}" @selected((int) old('business_entity_id', $businessEntity->id) === (int) $entity->id)>
                                {{ $entity->legal_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                    <x-date-input name="entry_date" value="{{ $entryDate }}" class="w-full text-sm" required />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" required maxlength="255"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference (optional)</label>
                <input type="text" name="reference_number" value="{{ old('reference_number') }}" maxlength="50"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
            </div>

            <div>
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Lines</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500">
                                <th class="pb-2 pr-2">Account</th>
                                <th class="pb-2 pr-2 w-28">Debit</th>
                                <th class="pb-2 pr-2 w-28">Credit</th>
                                <th class="pb-2 pr-2">Tracking</th>
                                <th class="pb-2">Memo</th>
                            </tr>
                        </thead>
                        <tbody class="space-y-2">
                            @foreach($lineRows as $i => $row)
                                <tr>
                                    <td class="py-1 pr-2">
                                        <select name="lines[{{ $i }}][chart_of_account_id]" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" @selected((string) ($row['chart_of_account_id'] ?? '') === (string) $account->id)>
                                                    {{ $account->account_code }} — {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]"
                                               value="{{ $row['debit'] ?? '' }}"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm tabular-nums">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]"
                                               value="{{ $row['credit'] ?? '' }}"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm tabular-nums">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <select name="lines[{{ $i }}][tracking_category_id]"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($trackingCategories as $category)
                                                <option value="{{ $category->id }}" @selected((string) ($row['tracking_category_id'] ?? '') === (string) $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="lines[{{ $i }}][tracking_sub_category_id]"
                                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($trackingCategories as $category)
                                                @foreach($category->activeSubCategories as $subCategory)
                                                    <option value="{{ $subCategory->id }}" @selected((string) ($row['tracking_sub_category_id'] ?? '') === (string) $subCategory->id)>
                                                        {{ $category->name }} / {{ $subCategory->name }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1">
                                        <input type="text" name="lines[{{ $i }}][description]"
                                               value="{{ $row['description'] ?? '' }}"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500">Leave unused rows blank. Amounts are gross — one side per line. Tracking is optional per line.</p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('financial-reports.index') }}"
                   class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Post journal
                </button>
            </div>
        </form>

        <div class="mt-10 bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Entity opening balances</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Set per-entity opening positions as of a date. Each non-zero amount creates a balanced journal
                against <strong>3190 Opening Balance Equity</strong>.
            </p>

            <form method="POST" action="{{ route('financial-reports.opening-balances.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entity</label>
                        <select name="business_entity_id" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                            @foreach($businessEntities as $entity)
                                <option value="{{ $entity->id }}" @selected((int) $businessEntity->id === (int) $entity->id)>
                                    {{ $entity->legal_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">As of date</label>
                        <x-date-input name="as_of_date" value="{{ now()->toDateString() }}" class="w-full text-sm" required />
                    </div>
                </div>

                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500">
                                <th class="pb-2">Account</th>
                                <th class="pb-2 w-40 text-right">Net balance (debit − credit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $i => $account)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="py-2 text-gray-800 dark:text-gray-200">
                                        {{ $account->account_code }} — {{ $account->account_name }}
                                        <input type="hidden" name="balances[{{ $i }}][chart_of_account_id]" value="{{ $account->id }}">
                                    </td>
                                    <td class="py-2 text-right">
                                        <input type="number" step="0.01" name="balances[{{ $i }}][amount]"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm tabular-nums text-right">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        Post opening balances
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
