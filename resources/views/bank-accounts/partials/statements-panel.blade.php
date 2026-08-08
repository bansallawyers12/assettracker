@php
    $uploadUrl = route('bank-accounts.statements.store', $bankAccount);
    $indexUrl = route('bank-accounts.statements.index', $bankAccount);
    $canManageStatements = $canManageStatements ?? false;
@endphp

<div
    class="bank-statements-panel space-y-6"
    data-bank-statements-panel
    data-bank-account-id="{{ $bankAccount->id }}"
    data-bank-statements-index-url="{{ $indexUrl }}"
>
    @if($canManageStatements)
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Upload statement</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            PDF only. Enter the statement period; opening and closing balances are optional.
        </p>

        <form
            method="POST"
            action="{{ $uploadUrl }}"
            enctype="multipart/form-data"
            class="mt-4 space-y-4"
            data-bank-statements-upload-form
        >
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="statement_period_start" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Start date <span class="text-red-500">*</span></label>
                    <input
                        type="date"
                        name="statement_period_start"
                        id="statement_period_start"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>
                <div>
                    <label for="statement_period_end" class="block text-xs font-medium text-gray-700 dark:text-gray-300">End date <span class="text-red-500">*</span></label>
                    <input
                        type="date"
                        name="statement_period_end"
                        id="statement_period_end"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="opening_balance" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Opening balance</label>
                    <input
                        type="number"
                        name="opening_balance"
                        id="opening_balance"
                        step="0.01"
                        placeholder="Optional"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>
                <div>
                    <label for="closing_balance" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Closing balance</label>
                    <input
                        type="number"
                        name="closing_balance"
                        id="closing_balance"
                        step="0.01"
                        placeholder="Optional"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>
            </div>

            <div>
                <label for="statement_file" class="block text-xs font-medium text-gray-700 dark:text-gray-300">PDF file <span class="text-red-500">*</span></label>
                <input
                    type="file"
                    name="statement_file"
                    id="statement_file"
                    accept="{{ config('bank_statements.file_accept') }}"
                    required
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-300"
                >
            </div>

            <div>
                <label for="statement_notes" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea
                    name="notes"
                    id="statement_notes"
                    rows="2"
                    maxlength="500"
                    placeholder="Optional"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                    data-bank-statements-upload-submit
                >
                    Upload statement
                </button>
            </div>
        </form>
    </div>
    @endif

    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Saved statements</h3>
        <div class="mt-3" data-bank-statements-list>
            @include('bank-accounts.partials.statements-list', [
                'statements' => $statements,
                'bankAccount' => $bankAccount,
                'canManageStatements' => $canManageStatements,
            ])
        </div>
    </div>
</div>
