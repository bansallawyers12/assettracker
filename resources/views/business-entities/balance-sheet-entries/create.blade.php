<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Add balance sheet entry — {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    @php
        $td = $transactionData;
        $returnContext = $returnContext ?? [];
        $oldChannel = old('payment_channel', $td['payment_channel'] ?? \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS);
        $oldType = old('transaction_type', $td['transaction_type'] ?? 'asset_purchase');
        $returnTo = old('return_to', $returnContext['return_to'] ?? null);
        $returnBankAccountId = old('return_bank_account_id', $returnContext['return_bank_account_id'] ?? null);
        $returnBusinessEntityId = old('return_business_entity_id', $returnContext['return_business_entity_id'] ?? null);
    @endphp

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-indigo-400 dark:border-indigo-600">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Record capital and director-loan items not paid through a bank account
                    (for example a property deposit from director funds — use Asset Purchase).
                    For day-to-day income and expenses, use Add transaction on the dashboard.
                    For advanced journals, use
                    <a href="{{ route('business-entities.financial-reports.journal-entries.create', $businessEntity) }}" class="text-indigo-600 hover:underline dark:text-indigo-400">manual journal</a>.
                </p>

                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                        <p class="font-semibold mb-2">Could not save this entry:</p>
                        <ul class="list-disc list-inside space-y-1.5 leading-snug">
                            @foreach ($errors->all() as $err)
                                <li class="whitespace-normal wrap-break-word">{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('business-entities.balance-sheet-entries.store', $businessEntity) }}"
                    id="balance-sheet-entry-form"
                    data-transaction-paid-by-form
                    data-booking-entity-id="{{ $businessEntity->id }}"
                >
                    @csrf
                    <input type="hidden" name="payment_status" value="paid">
                    @if($returnTo)
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    @if($returnBusinessEntityId)
                        <input type="hidden" name="return_business_entity_id" value="{{ $returnBusinessEntityId }}">
                    @endif
                    @if($returnBankAccountId)
                        <input type="hidden" name="return_bank_account_id" value="{{ $returnBankAccountId }}">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <x-date-input name="date" value="{{ old('date', $td['date'] ?? now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required />
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $td['amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $td['description'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" placeholder="e.g., Deposit / purchase for 3 Faul Street">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Entry type</label>
                            <select
                                name="transaction_type"
                                id="transaction_type"
                                required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs"
                                data-tomselect
                                data-tomselect-search="false"
                            >
                                <option value="">Select type</option>
                                @foreach (\App\Models\Transaction::balanceSheetTypeSelectGroups() as $groupLabel => $types)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($types as $value => $label)
                                            @php
                                                $optionDirection = array_key_exists($value, \App\Models\Transaction::$incomeTypes)
                                                    ? 'income'
                                                    : 'expense';
                                            @endphp
                                            <option
                                                value="{{ $value }}"
                                                data-direction="{{ $optionDirection }}"
                                                @selected((string) $oldType === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <x-tom-select name="asset_id" class="mt-1 rounded-md shadow-xs">
                                <option value="">None — entity only</option>
                                @foreach ($businessEntity->assets()->orderBy('name')->get() as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) old('asset_id', $td['asset_id'] ?? '') === (string) $asset->id)>{{ $asset->name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Funding (non-bank)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment channel</label>
                                <select name="payment_channel" id="payment_channel" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required>
                                    @foreach (\App\Models\Transaction::nonBankPaymentChannels() as $value => $label)
                                        <option value="{{ $value }}" @selected($oldChannel === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_channel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @php
                                $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($td['paid_by'] ?? null);
                                $typeDirection = array_key_exists($oldType, \App\Models\Transaction::$incomeTypes) ? 'income' : 'expense';
                            @endphp
                            @include('partials.transaction-paid-by-fields', [
                                'payerOptions' => $payerOptions,
                                'paidBySelect' => $pbSplit['select'],
                                'paidByOther' => $pbSplit['other'],
                                'paidByLabelText' => $typeDirection === 'income' ? 'Received By' : 'Paid By',
                                'hideBankAccountField' => true,
                            ])
                        </div>
                    </div>

                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md shadow-xs transition duration-200 font-medium">
                            Save balance sheet entry
                        </button>
                        @php
                            $cancelHref = match ($returnTo) {
                                'bank-account' => route('bank-accounts.index'),
                                'transactions-page' => $returnBankAccountId
                                    ? route('bank-accounts.transactions.page', array_filter([
                                        'bankAccount' => $returnBankAccountId,
                                        'business_entity_id' => $returnBusinessEntityId,
                                    ]))
                                    : route('business-entities.show', $businessEntity),
                                'entity' => $returnBankAccountId
                                    ? route('business-entities.show', [
                                        'business_entity' => $businessEntity->id,
                                        'open_bank_transactions' => $returnBankAccountId,
                                    ]).'#tab_bank_accounts'
                                    : route('business-entities.show', $businessEntity),
                                default => route('business-entities.show', $businessEntity),
                            };
                        @endphp
                        <a href="{{ $cancelHref }}" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-md shadow-xs transition duration-200 font-medium">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transactionTypeSelect = document.getElementById('transaction_type');
            const paidByLabel = document.getElementById('paid_by_label');
            const paidBySelect = document.getElementById('paid_by_select');
            const paidByOtherWrap = document.getElementById('paid_by_other_wrap');

            function selectedOption(select) {
                if (!select) return null;
                if (select.tomselect) {
                    const value = select.tomselect.getValue();
                    const raw = Array.isArray(value) ? (value[0] ?? '') : (value ?? '');

                    return Array.from(select.options).find((option) => option.value === String(raw)) ?? null;
                }

                return select.options[select.selectedIndex] ?? null;
            }

            function bindSelectChange(select, handler) {
                if (!select) return;
                select.addEventListener('change', handler);
                if (select.tomselect) {
                    select.tomselect.on('change', handler);
                }
            }

            function updatePaidByLabel() {
                if (!paidByLabel || !transactionTypeSelect) return;
                const direction = selectedOption(transactionTypeSelect)?.dataset?.direction || 'expense';
                paidByLabel.textContent = direction === 'income' ? 'Received By' : 'Paid By';
            }

            bindSelectChange(transactionTypeSelect, updatePaidByLabel);
            updatePaidByLabel();

            function syncPaidByOther() {
                if (!paidBySelect || !paidByOtherWrap) return;
                const value = paidBySelect.tomselect
                    ? (Array.isArray(paidBySelect.tomselect.getValue())
                        ? (paidBySelect.tomselect.getValue()[0] ?? '')
                        : (paidBySelect.tomselect.getValue() ?? ''))
                    : paidBySelect.value;
                paidByOtherWrap.classList.toggle('hidden', value !== 'other');
            }
            bindSelectChange(paidBySelect, syncPaidByOther);
            syncPaidByOther();
        });
    </script>
</x-app-layout>
