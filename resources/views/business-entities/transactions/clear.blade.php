<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Clear transactions — {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-red-500">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400">Danger zone</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Permanently remove accounting transactions for this entity so you can start again
                    (for example after testing loan imports). This cannot be undone.
                </p>

                @if (session('error'))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <dt class="text-gray-500 dark:text-gray-400">Transactions to delete</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($preview['transactions']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <dt class="text-gray-500 dark:text-gray-400">Manual journals (optional)</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($preview['manual_journals']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <dt class="text-gray-500 dark:text-gray-400">Invoice payment links reset</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($preview['linked_invoices']) }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <dt class="text-gray-500 dark:text-gray-400">Bank statement lines unmatched</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($preview['bank_statement_entries']) }}</dd>
                    </div>
                </dl>

                <ul class="mt-6 list-disc pl-5 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <li>Deletes every transaction booked to this entity (bank, balance sheet entry, dashboard).</li>
                    <li>Removes linked journal entries automatically.</li>
                    <li>Unmatches imported bank statement lines so you can import again.</li>
                    <li>Does <strong>not</strong> delete posted invoices, depreciation journals, or bank accounts.</li>
                </ul>

                <form method="POST"
                      action="{{ route('business-entities.transactions.clear.destroy', $businessEntity) }}"
                      class="mt-8 space-y-4"
                      onsubmit="return confirm('This will permanently delete the selected data. Continue?');">
                    @csrf
                    @method('DELETE')

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="include_manual_journals" value="0">
                        <input type="checkbox"
                               name="include_manual_journals"
                               value="1"
                               class="mt-1 rounded-sm border-gray-300 text-red-600 focus:ring-red-500"
                               @checked(old('include_manual_journals'))>
                        <span class="text-sm text-gray-700 dark:text-gray-200">
                            Also delete manual journals for this entity (opening balances, loan drawdown journal, etc.)
                        </span>
                    </label>

                    <div>
                        <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Type <span class="font-semibold">{{ $businessEntity->legal_name }}</span> to confirm
                        </label>
                        <input type="text"
                               name="confirmation"
                               id="confirmation"
                               value="{{ old('confirmation') }}"
                               required
                               autocomplete="off"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        @error('confirmation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_transactions"
                           class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Cancel
                        </a>
                        <button type="submit"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                                @disabled($preview['transactions'] === 0 && $preview['manual_journals'] === 0)>
                            Clear transactions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
