@php
    $editing = $editing ?? null;
    $emptyLine = ['chart_of_account_id' => '', 'debit' => '', 'credit' => '', 'description' => '', 'tracking_category_id' => '', 'tracking_sub_category_id' => ''];
    $defaultLines = $editing
        ? $editing->journalLines
            ->sortBy('id')
            ->values()
            ->map(fn ($line) => [
                'chart_of_account_id' => (string) $line->chart_of_account_id,
                'debit' => (float) $line->debit_amount > 0 ? $line->debit_amount : '',
                'credit' => (float) $line->credit_amount > 0 ? $line->credit_amount : '',
                'description' => $line->description ?? '',
                'tracking_category_id' => $line->tracking_category_id !== null ? (string) $line->tracking_category_id : '',
                'tracking_sub_category_id' => $line->tracking_sub_category_id !== null ? (string) $line->tracking_sub_category_id : '',
            ])->all()
        : [$emptyLine, $emptyLine];
    while (count($defaultLines) < 2) {
        $defaultLines[] = $emptyLine;
    }
    $lineRows = collect(old('lines', $defaultLines))
        ->map(fn ($row) => array_merge($emptyLine, [
            'chart_of_account_id' => isset($row['chart_of_account_id']) && $row['chart_of_account_id'] !== ''
                ? (string) $row['chart_of_account_id']
                : '',
            'debit' => $row['debit'] ?? '',
            'credit' => $row['credit'] ?? '',
            'description' => $row['description'] ?? '',
            'tracking_category_id' => isset($row['tracking_category_id']) && $row['tracking_category_id'] !== ''
                ? (string) $row['tracking_category_id']
                : '',
            'tracking_sub_category_id' => isset($row['tracking_sub_category_id']) && $row['tracking_sub_category_id'] !== ''
                ? (string) $row['tracking_sub_category_id']
                : '',
        ]))
        ->values()
        ->all();
    if ($lineRows === []) {
        $lineRows = [$emptyLine, $emptyLine];
    }
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
            : array_merge(['journalEntry' => $editing], $scopeQuery))
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

        <script>
            window.manualJournalLinesForm = function manualJournalLinesForm(config) {
                let keySequence = 0;
                const nextKey = () => 'mj-line-' + (++keySequence);

                const blankLine = () => ({
                    _key: nextKey(),
                    chart_of_account_id: '',
                    debit: '',
                    credit: '',
                    description: '',
                    tracking_category_id: '',
                    tracking_sub_category_id: '',
                });

                const initialLines = Array.isArray(config.lines) && config.lines.length
                    ? config.lines.map((line) => ({
                        _key: nextKey(),
                        chart_of_account_id: line.chart_of_account_id != null && line.chart_of_account_id !== ''
                            ? String(line.chart_of_account_id)
                            : '',
                        debit: line.debit ?? '',
                        credit: line.credit ?? '',
                        description: line.description ?? '',
                        tracking_category_id: line.tracking_category_id != null && line.tracking_category_id !== ''
                            ? String(line.tracking_category_id)
                            : '',
                        tracking_sub_category_id: line.tracking_sub_category_id != null && line.tracking_sub_category_id !== ''
                            ? String(line.tracking_sub_category_id)
                            : '',
                    }))
                    : [blankLine(), blankLine()];

                while (initialLines.length < 2) {
                    initialLines.push(blankLine());
                }

                return {
                    lines: initialLines,
                    maxLines: Number(config.maxLines) || 40,
                    get canAddLine() {
                        return this.lines.length < this.maxLines;
                    },
                    get canRemoveLine() {
                        return this.lines.length > 2;
                    },
                    get totalDebit() {
                        return this.lines.reduce((sum, line) => sum + (Number(line.debit) || 0), 0);
                    },
                    get totalCredit() {
                        return this.lines.reduce((sum, line) => sum + (Number(line.credit) || 0), 0);
                    },
                    get hasAmounts() {
                        return this.totalDebit > 0 || this.totalCredit > 0;
                    },
                    get imbalance() {
                        return Math.abs(this.totalDebit - this.totalCredit);
                    },
                    get isBalanced() {
                        return this.hasAmounts && this.imbalance < 0.005;
                    },
                    get isOutOfBalance() {
                        return this.hasAmounts && this.imbalance >= 0.005;
                    },
                    formatMoney(value) {
                        return (Number(value) || 0).toFixed(2);
                    },
                    addLine() {
                        if (! this.canAddLine) {
                            return;
                        }
                        this.lines.push(blankLine());
                    },
                    removeLine(index) {
                        if (! this.canRemoveLine) {
                            return;
                        }
                        this.lines.splice(index, 1);
                    },
                };
            };
        </script>

        <form method="POST" action="{{ $formAction }}"
              class="bg-white dark:bg-gray-900 shadow rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-6">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

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

            <div x-data="window.manualJournalLinesForm(@js(['lines' => $lineRows, 'maxLines' => 40]))">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Lines</h2>
                    <button type="button"
                            @click="addLine()"
                            :disabled="! canAddLine"
                            class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">
                        <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                        Add line
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500">
                                <th class="pb-2 pr-2">Account</th>
                                <th class="pb-2 pr-2 w-28">Debit</th>
                                <th class="pb-2 pr-2 w-28">Credit</th>
                                <th class="pb-2 pr-2">Tracking</th>
                                <th class="pb-2 pr-2">Memo</th>
                                <th class="pb-2 w-10"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in lines" :key="line._key">
                                <tr>
                                    <td class="py-1 pr-2">
                                        <select :name="'lines[' + index + '][chart_of_account_id]'"
                                                x-model="line.chart_of_account_id"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">
                                                    {{ $account->account_code }} — {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input type="number" step="0.01" min="0"
                                               :name="'lines[' + index + '][debit]'"
                                               x-model="line.debit"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm tabular-nums">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input type="number" step="0.01" min="0"
                                               :name="'lines[' + index + '][credit]'"
                                               x-model="line.credit"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm tabular-nums">
                                    </td>
                                    <td class="py-1 pr-2">
                                        <select :name="'lines[' + index + '][tracking_category_id]'"
                                                x-model="line.tracking_category_id"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($trackingCategories as $category)
                                                <option value="{{ $category->id }}">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select :name="'lines[' + index + '][tracking_sub_category_id]'"
                                                x-model="line.tracking_sub_category_id"
                                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                            <option value="">—</option>
                                            @foreach($trackingCategories as $category)
                                                @foreach($category->activeSubCategories as $subCategory)
                                                    <option value="{{ $subCategory->id }}">
                                                        {{ $category->name }} / {{ $subCategory->name }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input type="text"
                                               :name="'lines[' + index + '][description]'"
                                               x-model="line.description"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                                    </td>
                                    <td class="py-1 align-top">
                                        <button type="button"
                                                @click="removeLine(index)"
                                                x-show="canRemoveLine"
                                                class="inline-flex items-center rounded-md px-2 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                                                title="Remove line">
                                            <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                                            <span class="sr-only">Remove line</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 text-sm dark:border-gray-700">
                                <td class="pt-3 pr-2 font-medium text-gray-700 dark:text-gray-300">Totals</td>
                                <td class="pt-3 pr-2 tabular-nums font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(totalDebit)"></td>
                                <td class="pt-3 pr-2 tabular-nums font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(totalCredit)"></td>
                                <td class="pt-3 pr-2" colspan="3">
                                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400" x-show="isBalanced" x-cloak>Balanced</span>
                                    <span class="text-xs font-medium text-amber-700 dark:text-amber-400" x-show="isOutOfBalance" x-cloak>
                                        Out by <span x-text="formatMoney(imbalance)"></span>
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Use Add line for extra rows. Blank rows are ignored. Amounts are gross — one side per line. Tracking is optional per line.
                </p>
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
