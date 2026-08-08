<div
    class="rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
    data-bank-import-panel
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Import &amp; match</h3>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                Upload CSV/Excel statement lines, then match to an existing transaction or create one from the chart of accounts.
            </p>
        </div>
        <span class="text-xs font-medium text-amber-800 dark:text-amber-200" data-bank-import-unmatched-count>
            {{ $unmatchedEntries->count() }} unmatched
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
                    <select
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
                    </select>
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
                    accept=".xlsx,.xls,.csv"
                    required
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-300"
                >
            </div>
        </div>

        <div class="flex justify-end">
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
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Unmatched lines</h4>
            <button
                type="button"
                data-bank-import-apply
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                @disabled($unmatchedEntries->isEmpty())
            >
                Apply matches
            </button>
        </div>

        <div class="mt-3 space-y-3 max-h-96 overflow-y-auto" data-bank-import-entries>
            @forelse($unmatchedEntries as $entry)
                <div
                    class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900"
                    data-bank-import-entry
                    data-entry-id="{{ $entry->id }}"
                    data-entry-amount="{{ $entry->amount }}"
                    data-entry-date="{{ $entry->date?->format('Y-m-d') }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $entry->description ?: '—' }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $entry->date?->format('d/m/Y') ?? '—' }}
                                @if($entry->transaction_type)
                                    · {{ $entry->transaction_type }}
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 text-sm font-semibold tabular-nums {{ (float) $entry->amount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                            {{ (float) $entry->amount >= 0 ? '+' : '−' }}${{ number_format(abs((float) $entry->amount), 2) }}
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Match existing</label>
                            <select
                                data-bank-import-transaction
                                class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">— None —</option>
                                @foreach($matchCandidates as $candidate)
                                    <option
                                        value="{{ $candidate->id }}"
                                        data-amount="{{ $candidate->amount }}"
                                        data-date="{{ $candidate->date?->format('Y-m-d') }}"
                                    >
                                        {{ $candidate->date?->format('d/m/Y') }} · ${{ number_format((float) $candidate->amount, 2) }}
                                        · {{ \Illuminate\Support\Str::limit($candidate->description ?: 'No description', 40) }}
                                        @if($candidate->businessEntity)
                                            ({{ $candidate->businessEntity->legal_name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Or create from account</label>
                            <select
                                data-bank-import-chart-account
                                class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">— None —</option>
                            </select>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4" data-bank-import-empty>
                    No unmatched statement lines. Upload a CSV/Excel file to begin.
                </p>
            @endforelse
        </div>
    </div>
</div>
