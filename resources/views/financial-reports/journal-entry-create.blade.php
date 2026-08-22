@php
    $editing = $editing ?? null;
    $emptyLine = ['chart_of_account_id' => '', 'debit' => '', 'credit' => '', 'description' => '', 'tracking_category_id' => '', 'tracking_sub_category_id' => ''];
    $defaultLines = $editing
        ? $editing->journalLines->map(fn ($line) => [
            'chart_of_account_id' => $line->chart_of_account_id,
            'debit' => (float) $line->debit_amount > 0 ? $line->debit_amount : '',
            'credit' => (float) $line->credit_amount > 0 ? $line->credit_amount : '',
            'description' => $line->description ?? '',
            'tracking_category_id' => $line->tracking_category_id ?? '',
            'tracking_sub_category_id' => $line->tracking_sub_category_id ?? '',
        ])->all()
        : [$emptyLine, $emptyLine];
    if ($editing) {
        $defaultLines[] = $emptyLine;
        $defaultLines[] = $emptyLine;
    }
    $lineRows = old('lines', $defaultLines);
    $entityScoped = $entityScoped ?? false;
    $routes = $routes ?? [
        'index' => route('financial-reports.journal-entries.index'),
        'create' => route('financial-reports.journal-entries.create'),
        'store' => route('financial-reports.journal-entries.store'),
        'openingBalancesStore' => route('financial-reports.opening-balances.store'),
    ];
    $scopeQuery = $scopeQuery ?? [];
    $entityPickerAction = $routes['create'].($scopeQuery !== [] ? '?'.http_build_query($scopeQuery) : '');
    $formAction = $editing
        ? route($routes['update'], $entityScoped && isset($routes['entity'])
            ? ['businessEntity' => $routes['entity'], 'journalEntry' => $editing]
            : ['journalEntry' => $editing])
        : $routes['store'];
    $cancelUrl = $editing
        ? route($routes['show'], $entityScoped && isset($routes['entity'])
            ? ['businessEntity' => $routes['entity'], 'journalEntry' => $editing]
            : ['journalEntry' => $editing])
        : $routes['index'];
@endphp

<x-app-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $editing ? 'Edit manual journal' : 'Manual journal entry' }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Post opening balances, equity adjustments, or other entries not created by bank transactions or invoices.
                    Debits must equal credits.
                </p>
                @if($entityScoped)
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $businessEntity->legal_name }}</p>
                @endif
            </div>
            <a href="{{ $routes['index'] }}"
               class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                View all journals
            </a>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-sm border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        @unless($entityScoped || $editing)
            <form method="GET" action="{{ $entityPickerAction }}" class="mb-4 bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entity (applies to both forms below)</label>
                <div class="flex flex-wrap items-end gap-3">
                    @foreach($scopeQuery as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <select name="prefill_entity_id" required onchange="this.form.submit()"
                            class="min-w-[16rem] rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                        @foreach($businessEntities as $entity)
                            <option value="{{ $entity->id }}" @selected((int) $businessEntity->id === (int) $entity->id)>
                                {{ $entity->legal_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500">Tracking categories match the selected entity.</p>
                </div>
            </form>
        @endunless

        <form method="POST" action="{{ $formAction }}"
              class="bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-6">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <input type="hidden" name="business_entity_id" value="{{ $businessEntity->id }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                <x-date-input name="entry_date" value="{{ $entryDate }}" class="w-full text-sm max-w-xs" required />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $editing?->description) }}" required maxlength="255"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference (optional)</label>
                <input type="text" name="reference_number" value="{{ old('reference_number', $editing?->reference_number) }}" maxlength="50"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                       @readonly($editing?->isOpeningBalance())>
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
                <a href="{{ $cancelUrl }}"
                   class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ $editing ? 'Save changes' : 'Post journal' }}
                </button>
            </div>
        </form>

        @unless($editing)
        <div class="mt-10 bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Entity opening balances</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Set per-entity opening positions as of a date. Each non-zero amount creates a balanced journal
                against <strong>3190 Opening Balance Equity</strong>.
            </p>

            <form method="POST" action="{{ $routes['openingBalancesStore'] }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="business_entity_id" value="{{ $businessEntity->id }}">
                @unless($entityScoped)
                    @foreach($scopeQuery as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                @endunless

                <div class="max-w-xs">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">As of date</label>
                    <x-date-input name="as_of_date" value="{{ now()->toDateString() }}" class="w-full text-sm" required />
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
        @endunless
    </div>
</x-app-layout>
