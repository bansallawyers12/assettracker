<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Clear bank transactions — {{ $bankAccount->entityWorkspaceLabel($businessEntity) }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-red-500">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400">Danger zone</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Permanently remove every transaction on this bank account for {{ $businessEntity->legal_name }}.
                    Filters on the transactions list are ignored. This cannot be undone.
                </p>

                @if (session('error'))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <dt class="text-gray-500 dark:text-gray-400">Transactions to delete</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($preview['transactions']) }}</dd>
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
                    <li>Deletes every transaction for this entity on this bank account (not just currently filtered rows).</li>
                    <li>Removes linked journal entries automatically.</li>
                    <li>Unmatches imported bank statement lines so they can be matched again.</li>
                    <li>Does <strong>not</strong> delete invoices, assets, bank accounts, or manual journals.</li>
                </ul>

                <form method="POST"
                      action="{{ route('business-entities.bank-accounts.transactions.clear.destroy', [$businessEntity, $bankAccount]) }}"
                      class="mt-8 space-y-4"
                      onsubmit="return confirm('This will permanently delete the selected data. Continue?');">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Type <span class="font-semibold">{{ $confirmationPhrase }}</span> to confirm
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
                        <a href="{{ route('business-entities.show', ['business_entity' => $businessEntity->id, 'open_bank_transactions' => $bankAccount->id]) }}#tab_bank_accounts"
                           class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Cancel
                        </a>
                        <button type="submit"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                                @disabled($preview['transactions'] === 0)>
                            Clear bank transactions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
