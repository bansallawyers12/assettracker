@php
    $suggestions = $suggestions ?? [];
    $isLoanActivityImport = (bool) ($isLoanActivityImport ?? $bankAccount->isLoanLedgerAccount());
    $transactionTypeGroups = $transactionTypeGroups
        ?? ($isLoanActivityImport
            ? \App\Models\Transaction::loanActivityTypeSelectGroups()
            : \App\Models\Transaction::typeSelectGroups());
    $allTypes = \App\Models\Transaction::allTypes();
    $pendingCountLabel = $isLoanActivityImport ? 'to apply' : 'unmatched';
    $matchedEntryCount = (int) ($matchedEntryCount ?? 0);
@endphp

<div
    class="rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
    data-bank-import-panel
    data-reconciliation-panel
    data-loan-activity="{{ $isLoanActivityImport ? '1' : '0' }}"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            @if($isLoanActivityImport)
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Update loan activity</h3>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    This is the loan ledger, not cash. Interest and fees capitalise to the loan. Repayments reduce the loan and cash once — keep offset “to loan” lines as internal transfers.
                </p>
            @else
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Reconcile statement</h3>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    Upload CSV statement lines, review suggestions, then accept selected rows. Nothing posts until you accept.
                </p>
            @endif
        </div>
        <span class="text-xs font-medium text-amber-800 dark:text-amber-200" data-bank-import-unmatched-count>
            {{ $unmatchedEntries->count() }} {{ $pendingCountLabel }}
        </span>
    </div>

    <form class="mt-4 space-y-3" data-bank-import-upload-form enctype="multipart/form-data">
        <div
            data-bank-import-errors
            class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300"
        ></div>

        <div class="grid gap-3 sm:grid-cols-2">
            @if($importEntities->count() > 1)
                <div>
                    <label for="bank_import_entity_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Booking entity <span class="text-red-500">*</span>
                    </label>
                    <x-tom-select
                        id="bank_import_entity_id"
                        name="business_entity_id"
                        data-bank-import-entity
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option value="">Select entity…</option>
                        @foreach($importEntities as $entity)
                            <option value="{{ $entity->id }}" @selected((int) ($defaultImportEntityId ?? 0) === (int) $entity->id)>
                                {{ $entity->legal_name }}
                            </option>
                        @endforeach
                    </x-tom-select>
                </div>
            @elseif($importEntities->count() === 1)
                <input type="hidden" name="business_entity_id" data-bank-import-entity value="{{ $importEntities->first()->id }}">
            @endif

            <div class="{{ $importEntities->count() > 1 ? '' : 'sm:col-span-2' }}">
                <label for="bank_import_statement_file" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Statement file <span class="text-red-500">*</span>
                </label>
                <input
                    type="file"
                    id="bank_import_statement_file"
                    name="statement_file"
                    accept=".csv"
                    required
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-300"
                >
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <button
                type="button"
                data-bank-import-clear-unmatched
                class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/40"
                @disabled($unmatchedEntries->isEmpty())
            >
                Clear unmatched
            </button>
            <button
                type="button"
                data-bank-import-clear-matched
                class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/40"
                @disabled($matchedEntryCount === 0)
            >
                Clear matched
            </button>
            <button
                type="submit"
                data-bank-import-upload-submit
                class="inline-flex items-center rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-60"
            >
                Upload &amp; parse
            </button>
        </div>
    </form>

    <div class="mt-5 border-t border-amber-200/80 pt-4 dark:border-amber-900/40">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Unmatched lines</h4>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-bank-import-select-all class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    Select all
                </button>
                <button type="button" data-bank-import-deselect-all class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    Deselect all
                </button>
                <button type="button" data-bank-import-select-suggestions class="rounded-md border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-800 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
                    Select suggestions
                </button>
                <button
                    type="button"
                    data-bank-import-remove-selected
                    class="inline-flex items-center justify-center rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/40"
                    @disabled($unmatchedEntries->isEmpty())
                >
                    Remove selected
                </button>
                <button
                    type="button"
                    data-bank-import-accept-selected
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                    @disabled($unmatchedEntries->isEmpty())
                >
                    Accept selected
                </button>
            </div>
        </div>

        <div class="mt-3 space-y-3 max-h-[28rem] overflow-y-auto" data-bank-import-entries>
            @forelse($unmatchedEntries as $entry)
                @php
                    $suggestion = $suggestions[(int) $entry->id] ?? [
                        'action' => 'none',
                        'confidence' => 'low',
                        'reason' => null,
                        'transaction_id' => null,
                        'transaction_type' => null,
                        'asset_id' => null,
                    ];
                    $confidence = $suggestion['confidence'] ?? 'low';
                    $action = $suggestion['action'] ?? 'none';
                    $suggestedType = $suggestion['transaction_type'] ?? null;
                    $suggestedTxId = $suggestion['transaction_id'] ?? null;
                    $typeLabel = $suggestedType ? ($allTypes[$suggestedType] ?? $suggestedType) : null;
                    $balanceAfter = is_array($entry->meta) ? ($entry->meta['balance_after'] ?? null) : null;
                    $hasSuggestion = in_array($confidence, ['high', 'medium'], true) && $action !== 'none';
                @endphp
                <div
                    class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900"
                    data-bank-import-entry
                    data-entry-id="{{ $entry->id }}"
                    data-entry-amount="{{ $entry->amount }}"
                    data-entry-date="{{ $entry->date?->format('Y-m-d') }}"
                    data-has-suggestion="{{ $hasSuggestion ? '1' : '0' }}"
                >
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            data-bank-import-select
                            @checked($hasSuggestion)
                        >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $entry->description ?: '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $entry->date?->format('d/m/Y') ?? '—' }}
                                        @if($balanceAfter !== null)
                                            · Bal ${{ number_format((float) $balanceAfter, 2) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="shrink-0 text-sm font-semibold tabular-nums {{ (float) $entry->amount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    {{ (float) $entry->amount >= 0 ? '+' : '−' }}${{ number_format(abs((float) $entry->amount), 2) }}
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                @if($hasSuggestion && $action === 'match_transaction')
                                    <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" data-bank-import-suggestion-label>
                                        Match · {{ $typeLabel ?: 'Transaction' }} · TXN-{{ $suggestedTxId }}
                                    </span>
                                @elseif($hasSuggestion && $action === 'create_transaction')
                                    <span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-950/40 dark:text-sky-200" data-bank-import-suggestion-label>
                                        Create · {{ $typeLabel ?: 'Transaction' }} · ${{ number_format(abs((float) $entry->amount), 2) }}
                                    </span>
                                @else
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300" data-bank-import-suggestion-label>
                                        No suggestion
                                    </span>
                                @endif

                                @if(in_array($confidence, ['high', 'medium'], true) && $action !== 'none')
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $confidence === 'high' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' }}">
                                        {{ $confidence }}
                                    </span>
                                @endif

                                @if(!empty($suggestion['reason']))
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $suggestion['reason'] }}</span>
                                @endif

                                <button type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-500" data-bank-import-toggle-change>
                                    Change ▾
                                </button>
                            </div>

                            <div class="mt-3 hidden grid gap-2 sm:grid-cols-2" data-bank-import-change>
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Match existing</label>
                                    <x-tom-select
                                        data-bank-import-transaction
                                        class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    >
                                        <option value="">— None —</option>
                                        @foreach($matchCandidates as $candidate)
                                            <option
                                                value="{{ $candidate->id }}"
                                                data-amount="{{ $candidate->amount }}"
                                                data-date="{{ $candidate->date?->format('Y-m-d') }}"
                                                @selected((int) ($suggestedTxId ?? 0) === (int) $candidate->id)
                                            >
                                                {{ $candidate->date?->format('d/m/Y') }} · ${{ number_format((float) $candidate->amount, 2) }}
                                                · {{ \Illuminate\Support\Str::limit($candidate->description ?: 'No description', 40) }}
                                                @if($candidate->businessEntity)
                                                    ({{ $candidate->businessEntity->legal_name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </x-tom-select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Or create as type</label>
                                    <x-tom-select
                                        data-bank-import-create-type
                                        class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    >
                                        <option value="">— None —</option>
                                        @foreach($transactionTypeGroups as $groupLabel => $types)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach($types as $typeKey => $typeLabelOption)
                                                    <option value="{{ $typeKey }}" @selected($suggestedType === $typeKey && $action === 'create_transaction')>
                                                        {{ $typeLabelOption }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </x-tom-select>
                                </div>
                                @unless($isLoanActivityImport)
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Or create from chart account</label>
                                        <x-tom-select
                                            data-bank-import-chart-account
                                            class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                        >
                                            <option value="">— None —</option>
                                        </x-tom-select>
                                    </div>
                                @endunless
                                <div class="sm:col-span-2 rounded-md border border-gray-200 dark:border-gray-700 p-2.5">
                                    <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 mb-2">Create markers</p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-bank-import-subject-to-bas>
                                            Subject to BAS
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-bank-import-is-flagged>
                                            Flagged
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Comments</label>
                                        <input type="text" class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" data-bank-import-comments placeholder="Optional marker notes">
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" data-bank-import-suggested-action value="{{ $action }}">
                            <input type="hidden" data-bank-import-suggested-transaction value="{{ $suggestedTxId ?? '' }}">
                            <input type="hidden" data-bank-import-suggested-type value="{{ $suggestedType ?? '' }}">
                            <input type="hidden" data-bank-import-suggested-asset value="{{ $suggestion['asset_id'] ?? '' }}">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4" data-bank-import-empty>
                    @if($isLoanActivityImport)
                        No loan activity lines waiting. Upload the loan CSV to apply interest, fees, and repayments.
                    @else
                        No unmatched statement lines. Upload a CSV/Excel file to begin.
                    @endif
                </p>
            @endforelse
        </div>
    </div>
</div>
