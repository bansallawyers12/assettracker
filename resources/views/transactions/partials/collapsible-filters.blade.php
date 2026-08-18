@php
    use App\Models\Transaction;
    use App\Support\TransactionListFilters;

    $filters = $filters ?? TransactionListFilters::empty();
    $filtersActive = (bool) ($filtersActive ?? TransactionListFilters::isActive($filters));
    $filtersExpanded = $filtersActive || (bool) ($filtersExpanded ?? false);
    $idPrefix = $idPrefix ?? 'tx';
    $storageKey = $storageKey ?? 'tx-filters-expanded';
    $mode = $mode ?? 'page'; // page | ajax
    $wideGrid = (bool) ($wideGrid ?? false);
    $showEntityFilter = (bool) ($showEntityFilter ?? false);
    $entities = $entities ?? collect();
    $entityOptionLabel = $entityOptionLabel ?? 'legal_name';
    $hiddenInputs = $hiddenInputs ?? [];
    $formAction = $formAction ?? url()->current();
    $clearUrl = $clearUrl ?? $formAction;
    $transactionTypeGroups = $transactionTypeGroups ?? Transaction::typeSelectGroups();
    $filterControlClass = $filterControlClass
        ?? 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
    $bodyId = $bodyId ?? ($idPrefix.'-filters-body');
    $isAjax = $mode === 'ajax';
@endphp

<div
    class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60"
    data-tx-filters-wrap
    data-tx-filters-mode="{{ $mode }}"
    data-tx-filters-active="{{ $filtersActive ? '1' : '0' }}"
    data-tx-filters-storage-key="{{ $storageKey }}"
    @if($isAjax)
        data-bank-transactions-filters-wrap
        data-bank-transactions-filters-active="{{ $filtersActive ? '1' : '0' }}"
    @endif
>
    <button
        type="button"
        class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left"
        data-tx-filters-toggle
        @if($isAjax) data-bank-transactions-filters-toggle @endif
        aria-expanded="{{ $filtersExpanded ? 'true' : 'false' }}"
        aria-controls="{{ $bodyId }}"
    >
        <span class="flex min-w-0 items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filters</span>
            @if($filtersActive)
                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Active
                </span>
            @endif
        </span>
        <x-lucide-chevron-down
            @class([
                'h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 dark:text-gray-400',
                'rotate-180' => $filtersExpanded,
            ])
            data-tx-filters-chevron
            @if($isAjax) data-bank-transactions-filters-chevron @endif
            aria-hidden="true"
        />
    </button>

    <form
        method="GET"
        action="{{ $formAction }}"
        id="{{ $bodyId }}"
        @class([
            'border-t border-gray-200 p-3 dark:border-gray-700',
            'hidden' => ! $filtersExpanded,
        ])
        data-tx-filters
        data-tx-filters-body
        data-tx-filters-clear-url="{{ $clearUrl }}"
        @if($isAjax)
            data-bank-transactions-filters
            data-bank-transactions-filters-body
            data-bank-transactions-clear-url="{{ $clearUrl }}"
        @endif
    >
        @foreach($hiddenInputs as $hiddenName => $hiddenValue)
            @if($hiddenValue !== null && $hiddenValue !== '')
                <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
            @endif
        @endforeach

        <div @class([
            'grid grid-cols-1 gap-3 sm:grid-cols-2',
            'lg:grid-cols-4' => $wideGrid,
        ])>
            <div @class([
                'sm:col-span-2' => ! $wideGrid,
                'lg:col-span-2' => $wideGrid,
            ])>
                <label for="{{ $idPrefix }}_filter_q" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input
                    id="{{ $idPrefix }}_filter_q"
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Description, vendor, invoice #"
                    class="{{ $filterControlClass }}"
                >
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_date_from" class="block text-xs font-medium text-gray-700 dark:text-gray-300">From</label>
                <x-date-input
                    id="{{ $idPrefix }}_filter_date_from"
                    name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="{{ $filterControlClass }}"
                />
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_date_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">To</label>
                <x-date-input
                    id="{{ $idPrefix }}_filter_date_to"
                    name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="{{ $filterControlClass }}"
                />
            </div>

            <div @class([
                'sm:col-span-2' => ! $wideGrid,
                'lg:col-span-4' => $wideGrid,
            ])>
                <p class="block text-xs font-medium text-gray-700 dark:text-gray-300">Quick range</p>
                <div class="mt-1 flex flex-wrap gap-1.5">
                    @php
                        $today = \Carbon\Carbon::now();
                        $shortcuts = array_merge(
                            \App\Support\FinancialYear::monthShortcuts($today),
                            \App\Support\FinancialYear::periodShortcuts($today)
                        );
                    @endphp
                    @foreach($shortcuts as $label => [$s, $e])
                        <button
                            type="button"
                            data-tx-date-shortcut
                            data-date-from="{{ $s }}"
                            data-date-to="{{ $e }}"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-600 hover:border-indigo-400 hover:text-indigo-600 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if($showEntityFilter)
                <div>
                    <label for="{{ $idPrefix }}_filter_entity" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Entity</label>
                    <select
                        id="{{ $idPrefix }}_filter_entity"
                        name="entity_id"
                        class="{{ $filterControlClass }}"
                        data-tx-filter-auto
                        @if($isAjax) data-bank-transactions-filter-auto @endif
                    >
                        <option value="">All entities</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" @selected((int) ($filters['entity_id'] ?? 0) === (int) $entity->id)>
                                {{ data_get($entity, $entityOptionLabel) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="{{ $idPrefix }}_filter_direction" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Income / Expense</label>
                <select
                    id="{{ $idPrefix }}_filter_direction"
                    name="direction"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All</option>
                    <option value="income" @selected(($filters['direction'] ?? '') === 'income')>Income</option>
                    <option value="expense" @selected(($filters['direction'] ?? '') === 'expense')>Expense</option>
                </select>
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Type</label>
                <select
                    id="{{ $idPrefix }}_filter_type"
                    name="type"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All types</option>
                    @foreach($transactionTypeGroups as $groupLabel => $types)
                        <optgroup label="{{ $groupLabel }}">
                            @foreach($types as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected(($filters['type'] ?? '') === $typeKey)>{{ $typeLabel }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_payment" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Payment</label>
                <select
                    id="{{ $idPrefix }}_filter_payment"
                    name="payment_status"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All</option>
                    <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                    <option value="unpaid" @selected(($filters['payment_status'] ?? '') === 'unpaid')>Unpaid</option>
                </select>
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_match" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Bank match</label>
                <select
                    id="{{ $idPrefix }}_filter_match"
                    name="match_status"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All</option>
                    <option value="matched" @selected(($filters['match_status'] ?? '') === 'matched')>Matched</option>
                    <option value="unmatched" @selected(($filters['match_status'] ?? '') === 'unmatched')>Unmatched</option>
                </select>
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_bas" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Subject to BAS</label>
                <select
                    id="{{ $idPrefix }}_filter_bas"
                    name="subject_to_bas"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All</option>
                    <option value="yes" @selected(($filters['subject_to_bas'] ?? '') === 'yes')>Yes</option>
                    <option value="no" @selected(($filters['subject_to_bas'] ?? '') === 'no')>No</option>
                </select>
            </div>

            <div>
                <label for="{{ $idPrefix }}_filter_flagged" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Flagged</label>
                <select
                    id="{{ $idPrefix }}_filter_flagged"
                    name="is_flagged"
                    class="{{ $filterControlClass }}"
                    data-tx-filter-auto
                    @if($isAjax) data-bank-transactions-filter-auto @endif
                >
                    <option value="">All</option>
                    <option value="yes" @selected(($filters['is_flagged'] ?? '') === 'yes')>Yes</option>
                    <option value="no" @selected(($filters['is_flagged'] ?? '') === 'no')>No</option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
            >
                Apply filters
            </button>
            @if($filtersActive)
                <button
                    type="button"
                    data-tx-filters-clear
                    @if($isAjax) data-bank-transactions-filters-clear @endif
                    class="inline-flex items-center px-2 py-1.5 text-xs font-medium text-gray-600 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                >
                    Clear filters
                </button>
            @endif
        </div>
    </form>
</div>
