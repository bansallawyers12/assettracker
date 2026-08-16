<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Classify statement line for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    @php
        $statementEntry = $transaction->bankStatementEntries->first();
        $isLoanActivity = (bool) ($bankAccount?->isLoanLedgerAccount());
        $typeGroups = $bankAccount
            ? \App\Models\Transaction::typeSelectGroupsForBankAccount($bankAccount)
            : \App\Models\Transaction::typeSelectGroups();
        $allTypes = \App\Models\Transaction::allTypes();
        $currentType = (string) $transaction->transaction_type;
        $typeInGroups = false;
        foreach ($typeGroups as $types) {
            if (array_key_exists($currentType, $types)) {
                $typeInGroups = true;
                break;
            }
        }
        if ($currentType !== '' && ! $typeInGroups && array_key_exists($currentType, $allTypes)) {
            $typeGroups = [
                'Current' => [$currentType => $allTypes[$currentType]],
            ] + $typeGroups;
        }
        $oldType = old('transaction_type', $transaction->transaction_type);
        $cancelHref = request('return_to') === 'bank-account' && $transaction->bank_account_id
            ? route('business-entities.show', ['business_entity' => $businessEntity->id, 'open_bank_transactions' => $transaction->bank_account_id]).'#tab_bank_accounts'
            : route('business-entities.show', $businessEntity->id).'#tab_transactions';
        $signedAmount = $statementEntry
            ? (float) $statementEntry->amount
            : $transaction->bankAccountSignedAmount();
    @endphp

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-amber-300 dark:border-amber-600" data-statement-transaction-edit>
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                        <p class="font-semibold mb-2">Could not update this transaction:</p>
                        <ul class="list-disc list-inside space-y-1.5 leading-snug">
                            @foreach ($errors->all() as $err)
                                <li class="whitespace-normal wrap-break-word">{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" id="statement-edit-transaction-form" action="{{ route('business-entities.transactions.update', [$businessEntity->id, $transaction->id]) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_origin" value="statement">
                    <input type="hidden" name="business_entity_id" value="{{ $businessEntity->id }}">
                    <input type="hidden" name="date" value="{{ $transaction->date->toDateString() }}">
                    <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                    @if (request()->filled('return_to'))
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @endif

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Date, amount, and the bank narrative come from the statement. Change the type, asset, and markers the same way as when the line was accepted.
                    </p>

                    <dl class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ ($statementEntry?->date ?? $transaction->date)->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</dt>
                            <dd class="mt-1 font-semibold tabular-nums {{ $signedAmount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                {{ $signedAmount >= 0 ? '+' : '−' }}${{ number_format(abs($signedAmount), 2) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</dt>
                            <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $bankAccount?->entityWorkspaceLabel($businessEntity) ?? '—' }}</dd>
                        </div>
                        <div class="sm:col-span-3">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Statement description</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $statementEntry?->description ?: ($transaction->description ?: '—') }}</dd>
                        </div>
                    </dl>

                    @if ($transaction->isSplit())
                        <div class="mb-5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-100">
                            This is a split remittance ({{ $transaction->lines->count() }} allocations). Header amount and allocation types stay fixed.
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $transaction->description) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @unless ($transaction->isSplit())
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $isLoanActivity ? 'Loan activity' : 'Transaction Type' }}</label>
                                @include('partials.transaction-type-select', [
                                    'selected' => $oldType,
                                    'groups' => $typeGroups,
                                    'class' => 'mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white',
                                ])
                                @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endunless

                        <div id="counterpart_account_field" class="{{ $oldType === 'internal_transfer' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transfer to / from account</label>
                            <select name="counterpart_bank_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select account</option>
                                @foreach ($counterpartAccounts ?? [] as $counterpart)
                                    <option value="{{ $counterpart->id }}" @selected((string) old('counterpart_bank_account_id', $transaction->counterpart_bank_account_id) === (string) $counterpart->id)>
                                        {{ $counterpart->displayLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required for internal transfers. Loan interest and repayments stay on the loan account.</p>
                            @error('counterpart_bank_account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div id="related_entity_field" class="{{ in_array($oldType, ['director_loan_in', 'director_loan_out', 'director_loan_repayment', 'directors_loans_to_company', 'repayment_directors_loans', 'company_loans_to_directors'], true) ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <x-tom-select name="related_entity_id" class="mt-1 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Related Entity</option>
                                @foreach ($relatedEntities as $entity)
                                    <option value="{{ $entity->id }}" @selected((string) old('related_entity_id', $transaction->related_entity_id) === (string) $entity->id)>{{ $entity->legal_name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <x-tom-select name="asset_id" class="mt-1 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None — entity only</option>
                                @foreach ($entityAssets as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) old('asset_id', $transaction->asset_id) === (string) $asset->id)>{{ $asset->name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @include('partials.transaction-marker-fields', ['transaction' => $transaction])
                    </div>

                    <div class="flex gap-4 mt-5">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-xs transition duration-200">Update Transaction</button>
                        <a href="{{ $cancelHref }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 font-semibold py-2 px-4 rounded-md shadow-xs transition duration-200">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('statement-edit-transaction-form');
            const transactionTypeSelect = document.getElementById('transaction_type');
            const relatedEntityField = document.getElementById('related_entity_field');
            const counterpartField = document.getElementById('counterpart_account_field');
            const relatedPartyTypes = [
                'director_loan_in',
                'director_loan_out',
                'director_loan_repayment',
                'directors_loans_to_company',
                'repayment_directors_loans',
                'company_loans_to_directors',
            ];

            function syncTypeDependentFields(clearHidden) {
                if (!transactionTypeSelect) return;
                const type = transactionTypeSelect.value;
                if (relatedEntityField) {
                    const showRelated = relatedPartyTypes.includes(type);
                    relatedEntityField.classList.toggle('hidden', !showRelated);
                    const rs = relatedEntityField.querySelector('select');
                    if (rs) {
                        rs.required = showRelated;
                        if (!showRelated && clearHidden) window.setSelectValue?.(rs, '');
                    }
                }
                if (counterpartField) {
                    const showTransfer = type === 'internal_transfer';
                    counterpartField.classList.toggle('hidden', !showTransfer);
                    if (!showTransfer && clearHidden) {
                        const cs = counterpartField.querySelector('select');
                        if (cs) cs.value = '';
                    }
                }
            }

            transactionTypeSelect?.addEventListener('change', function () {
                syncTypeDependentFields(true);
            });
            syncTypeDependentFields(false);

            form?.addEventListener('submit', () => {
                const assetSelect = form.querySelector('select[name="asset_id"]');
                if (!assetSelect) return;
                const value = window.getSelectValue?.(assetSelect) ?? assetSelect.value;
                window.setSelectValue?.(assetSelect, value);
            });
        });
    </script>
</x-app-layout>
