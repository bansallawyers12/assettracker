<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Edit Transaction for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    @php
        $isStatementLinked = $transaction->isLinkedToBankStatement();
        $statementEntry = $isStatementLinked ? $transaction->bankStatementEntries->first() : null;
        $oldStatus = $isStatementLinked ? 'paid' : old('payment_status', $transaction->payment_status ?? 'paid');
        $oldChannel = $isStatementLinked
            ? ($transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT)
            : old('payment_channel', $transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT);
        $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($transaction->paid_by);
        if (old('paid_by_select') !== null) {
            $pbSplit = [
                'select' => old('paid_by_select', ''),
                'other' => old('paid_by_other', ''),
            ];
        }
        $paidAtDefault = old('paid_at', $transaction->paid_at?->toDateString() ?? $transaction->date->toDateString());
        $txnLabel = 'block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5';
        $txnInput = 'block w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/80 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-100 shadow-xs placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:border-blue-400 transition-colors';
        $txnSelect = 'block w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/80 text-sm text-gray-900 dark:text-gray-100 shadow-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:border-blue-400 transition-colors';
        $txnSection = 'rounded-xl border border-gray-100 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-900/30 p-5 space-y-4';
    @endphp

    <div class="py-8 lg:py-10 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div
                @class([
                    'bg-white dark:bg-gray-800 shadow-xl rounded-2xl border overflow-visible',
                    'border-amber-300 dark:border-amber-600' => $isStatementLinked,
                    'border-gray-200 dark:border-gray-700' => ! $isStatementLinked,
                ])
                @if ($isStatementLinked) data-statement-transaction-edit @endif
            >
                <div class="relative border-b border-gray-100 dark:border-gray-700 px-6 py-5">
                    <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-blue-500 via-indigo-500 to-violet-500 rounded-t-2xl"></div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit transaction</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Same fields as Add Transactions — all saved values are shown below.</p>
                </div>

                <div class="p-6">
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

                    @if ($isStatementLinked)
                        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100" data-statement-edit-locked-notice>
                            <p class="font-medium">Date, amount, and bank account are locked to the matched statement line.</p>
                            <p class="mt-1 text-amber-900/90 dark:text-amber-100/90">
                                You can still change account, GST, vendor, invoice number, attachments, and payment docs.
                                Use <span class="font-semibold">Unmatch</span> to change date, amount, or account.
                            </p>
                        </div>
                    @endif

                    @if ($transaction->isSplit())
                        <div class="mb-5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-100">
                            Split remittance ({{ $transaction->lines->count() }} allocations).
                            @if ($isStatementLinked)
                                Allocation amounts stay locked to the bank match; you can update account, GST, vendor, and description per line.
                            @else
                                Adjust allocations below; net to bank updates when you save.
                            @endif
                        </div>
                    @endif

                    @include('partials.dashboard-txn-batch-script')

                    <form method="POST"
                          id="bank-edit-transaction-form"
                          action="{{ route('business-entities.transactions.update', [$businessEntity->id, $transaction->id]) }}"
                          enctype="multipart/form-data"
                          class="dashboard-txn-form space-y-6"
                          @if (! $isStatementLinked) data-manual-transaction-edit data-transaction-paid-by-form @endif
                          data-require-bank-account-when-paid="false"
                          data-booking-entity-id="{{ $businessEntity->id }}"
                          x-data="window.dashboardTxnBatch(@js($dashboardTxnBatchConfig))"
                          x-init="init()">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_origin" value="{{ $isStatementLinked ? 'statement' : 'manual' }}">
                        @if (request()->filled('return_to'))
                            <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                        @endif

                        {{-- Where --}}
                        <section class="{{ $txnSection }}">
                            <div class="flex items-center gap-2 pb-1">
                                <x-lucide-building-2 class="w-4 h-4 text-blue-500 dark:text-blue-400" />
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Where</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="{{ $txnLabel }}">Business Entity</label>
                                    <p class="{{ $txnInput }} bg-gray-50 dark:bg-gray-800/80 text-gray-800 dark:text-gray-100">{{ $businessEntity->legal_name }}</p>
                                </div>
                                <div>
                                    <label class="{{ $txnLabel }}">Asset <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                                    <x-tom-select name="asset_id" class="{{ $txnSelect }}">
                                        <option value="">None — entity only</option>
                                        @foreach ($entityAssets as $asset)
                                            <option value="{{ $asset->id }}" @selected((string) old('asset_id', $transaction->asset_id) === (string) $asset->id)>{{ $asset->name }}</option>
                                        @endforeach
                                    </x-tom-select>
                                    @error('asset_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="{{ $txnLabel }}">
                                        Date
                                        @if ($isStatementLinked)
                                            <span class="ml-1 rounded bg-amber-200/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-900 dark:bg-amber-900/60 dark:text-amber-100">Locked</span>
                                        @endif
                                    </label>
                                    @if ($isStatementLinked)
                                        <input type="hidden" name="date" value="{{ $transaction->date->toDateString() }}">
                                        <p class="{{ $txnInput }} bg-gray-50 dark:bg-gray-800/80 font-medium">{{ ($statementEntry?->date ?? $transaction->date)->format('d/m/Y') }}</p>
                                    @else
                                        <x-date-input name="date" value="{{ old('date', $transaction->date->toDateString()) }}" class="{{ $txnInput }}" required />
                                    @endif
                                    @error('date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="mt-4">
                                @include('partials.transaction-marker-fields', ['transaction' => $transaction])
                            </div>
                        </section>

                        @include('partials.dashboard-transaction-lines', [
                            'txnLabel' => $txnLabel,
                            'txnInput' => $txnInput,
                            'txnSection' => $txnSection,
                            'dashboardChartAccounts' => $dashboardChartAccounts,
                            'vendors' => $vendors,
                            'dashboardRelatedEntitiesJson' => $dashboardRelatedEntitiesJson,
                            'hideExclusiveGst' => $isStatementLinked,
                            'lockLineAmount' => $isStatementLinked,
                        ])

                        @error('lines') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        @foreach ($errors->getMessages() as $errKey => $errMsgs)
                            @if (str_starts_with($errKey, 'lines.'))
                                @foreach ($errMsgs as $errMsg)
                                    <span class="text-red-500 text-xs mt-1 block">{{ $errMsg }}</span>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Attachments --}}
                        <section class="{{ $txnSection }}">
                            <div class="flex items-center gap-2 pb-1">
                                <x-lucide-paperclip class="w-4 h-4 text-violet-500 dark:text-violet-400" />
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Attachments</h4>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Attached once and linked to every line in this batch.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="{{ $txnLabel }}">Invoice / Bill <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                                    @php
                                        $receiptDocs = $transaction->receiptDocuments->filter(fn ($doc) => $doc->hasFile());
                                    @endphp
                                    @if ($receiptDocs->isNotEmpty())
                                        <div class="mb-3 space-y-2">
                                            @foreach ($receiptDocs as $document)
                                                @include('partials.document-attachment-row', [
                                                    'businessEntity' => $businessEntity,
                                                    'document' => $document,
                                                    'showRemove' => true,
                                                    'removeInputName' => 'remove_documents[]',
                                                ])
                                            @endforeach
                                        </div>
                                    @endif
                                    @include('partials.attachment-dropzone', [
                                        'inputName' => 'documents[]',
                                        'inputId' => 'bank-txn-edit-documents',
                                        'zoneId' => 'bank-txn-edit-documents-dropzone',
                                        'previewId' => 'bank-txn-edit-documents-pending',
                                        'accent' => 'blue',
                                    ])
                                    @error('document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    @error('documents.*') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="{{ $txnLabel }}">Invoice / Bill Name</label>
                                    <input type="text" name="document_name"
                                           value="{{ old('document_name', $transaction->receiptDocument?->file_name ?? '') }}"
                                           class="{{ $txnInput }}"
                                           placeholder="e.g., Invoice123">
                                    @error('document_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </section>

                        {{-- Payment --}}
                        <section class="{{ $txnSection }}">
                            <div class="flex items-center gap-2 pb-1">
                                <x-lucide-wallet class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Payment</h4>
                            </div>

                            @if ($isStatementLinked)
                                <input type="hidden" name="payment_status" id="payment_status_paid" value="paid">
                                <input type="hidden" name="payment_channel" id="payment_channel" value="{{ $transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT }}">
                                <input type="hidden" name="paid_by_select" value="{{ $pbSplit['select'] }}">
                                <input type="hidden" name="paid_by_other" value="{{ $pbSplit['other'] }}">
                                <input type="hidden" name="bank_account_id" value="{{ $transaction->bank_account_id }}">
                                <input type="hidden" name="paid_at" value="{{ $transaction->paid_at?->toDateString() ?? $transaction->date->toDateString() }}">
                                <input type="hidden" name="payment_method" value="{{ $transaction->payment_method }}">
                                <dl class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                                    <div>
                                        <dt class="text-xs font-medium uppercase text-gray-500">Status</dt>
                                        <dd class="mt-1 font-medium">Paid</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase text-gray-500">Account</dt>
                                        <dd class="mt-1 font-medium">{{ $bankAccount?->entityWorkspaceLabel($businessEntity) ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase text-gray-500">Payment date</dt>
                                        <dd class="mt-1 font-medium">{{ ($transaction->paid_at ?? $transaction->date)->format('d/m/Y') }}</dd>
                                    </div>
                                </dl>
                            @else
                                <div class="rounded-xl bg-gray-100/80 dark:bg-gray-900/50 p-1.5 grid grid-cols-2 gap-1.5 max-w-md">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="payment_status" value="paid" id="payment_status_paid" class="sr-only peer" {{ $oldStatus === 'paid' ? 'checked' : '' }}>
                                        <div class="flex items-center justify-center gap-2 rounded-lg py-2.5 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400 transition-all peer-checked:bg-white dark:peer-checked:bg-gray-800 peer-checked:text-blue-600 peer-checked:shadow-sm">
                                            <x-lucide-check class="w-4 h-4" /> Paid
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="payment_status" value="unpaid" id="payment_status_unpaid" class="sr-only peer" {{ $oldStatus === 'unpaid' ? 'checked' : '' }}>
                                        <div class="flex items-center justify-center gap-2 rounded-lg py-2.5 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400 transition-all peer-checked:bg-white dark:peer-checked:bg-gray-800 peer-checked:text-amber-600 peer-checked:shadow-sm">
                                            <x-lucide-clock class="w-4 h-4" /> Unpaid
                                        </div>
                                    </label>
                                </div>
                                <div id="unpaid_block" class="{{ $oldStatus === 'unpaid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="{{ $txnLabel }}">Due Date</label>
                                        <x-date-input name="due_date" value="{{ old('due_date', $transaction->due_date?->toDateString()) }}" class="{{ $txnInput }}" />
                                        @error('due_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div id="paid_block" class="{{ $oldStatus === 'paid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                    <div>
                                        <label class="{{ $txnLabel }}">Payment Date</label>
                                        <x-date-input name="paid_at" value="{{ $paidAtDefault }}" class="{{ $txnInput }}" />
                                        @error('paid_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $txnLabel }}">Payment Method</label>
                                        <x-tom-select name="payment_method" class="{{ $txnSelect }} px-3 py-2.5">
                                            <option value="">Select Method</option>
                                            @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                                <option value="{{ $val }}" @selected(old('payment_method', $transaction->payment_method) == $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </x-tom-select>
                                        @error('payment_method') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $txnLabel }}">Payment Channel</label>
                                        <x-tom-select name="payment_channel" id="payment_channel" class="{{ $txnSelect }} px-3 py-2.5">
                                            @foreach (\App\Models\Transaction::$paymentChannels as $value => $label)
                                                <option value="{{ $value }}" @selected($oldChannel === $value)>{{ $label }}</option>
                                            @endforeach
                                        </x-tom-select>
                                        @include('partials.payment-channel-funding-hint', ['hintClass' => 'mt-1 text-xs text-gray-500 dark:text-gray-400'])
                                        @error('payment_channel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="md:col-span-2 lg:col-span-1">
                                        @include('partials.transaction-paid-by-fields', [
                                            'payerOptions' => $payerOptions,
                                            'paidBySelect' => $pbSplit['select'],
                                            'paidByOther' => $pbSplit['other'],
                                            'bankAccountId' => old('bank_account_id', $transaction->bank_account_id),
                                            'paidByLabelText' => 'Paid / received by',
                                            'labelClass' => $txnLabel,
                                            'selectClass' => $txnSelect . ' px-3 py-2.5',
                                            'errorClass' => 'text-xs mt-1',
                                        ])
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="md:col-span-2">
                                    <label class="{{ $txnLabel }}">Payment Receipt <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                                    @php
                                        $paymentDocs = $transaction->paymentDocuments->filter(fn ($doc) => $doc->hasFile());
                                    @endphp
                                    @if ($paymentDocs->isNotEmpty())
                                        <div class="mb-3 space-y-2">
                                            @foreach ($paymentDocs as $document)
                                                @include('partials.document-attachment-row', [
                                                    'businessEntity' => $businessEntity,
                                                    'document' => $document,
                                                    'showRemove' => true,
                                                    'removeInputName' => 'remove_payment_documents[]',
                                                ])
                                            @endforeach
                                        </div>
                                    @endif
                                    @include('partials.attachment-dropzone', [
                                        'inputName' => 'payment_documents[]',
                                        'inputId' => 'bank-txn-edit-payment-documents',
                                        'zoneId' => 'bank-txn-edit-payment-documents-dropzone',
                                        'previewId' => 'bank-txn-edit-payment-documents-pending',
                                        'accent' => 'emerald',
                                    ])
                                    @error('payment_document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    @error('payment_documents.*') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="{{ $txnLabel }}">Payment Receipt Name</label>
                                    <input type="text" name="payment_document_name"
                                           value="{{ old('payment_document_name', $transaction->paymentDocument?->file_name ?? '') }}"
                                           class="{{ $txnInput }}"
                                           placeholder="e.g., Bank Transfer Confirmation">
                                    @error('payment_document_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </section>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <a href="{{ request('return_to') === 'bank-account' && $transaction->bank_account_id
                                ? route('business-entities.show', ['business_entity' => $businessEntity->id, 'open_bank_transactions' => $transaction->bank_account_id]).'#tab_bank_accounts'
                                : route('business-entities.show', $businessEntity->id).'#tab_transactions' }}"
                               class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-600 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20">
                                <x-lucide-check class="w-4 h-4" />
                                <span x-text="submitLabel">Update transaction</span>
                            </button>
                        </div>
                    </form>

                    @if ($isStatementLinked && $statementEntry && $bankAccount)
                        @php
                            $hasTransferSibling = filled($transaction->transfer_group_id)
                                && \App\Models\Transaction::query()
                                    ->where('transfer_group_id', $transaction->transfer_group_id)
                                    ->where('id', '!=', $transaction->id)
                                    ->exists();
                            $bankPanelHref = route('business-entities.show', [
                                'business_entity' => $businessEntity->id,
                                'open_bank_transactions' => $bankAccount->id,
                            ]).'#tab_bank_accounts';
                        @endphp
                        <div class="mt-8 border-t border-gray-200 pt-5 dark:border-gray-700" data-statement-edit-full-form-path>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Wrong bank match?</h3>
                            <p class="mb-3 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Unmatch</span> keeps this transaction and unlocks date, amount, and account.
                                <span class="font-medium">Remove &amp; Redo</span> deletes the booking and returns the line to unmatched.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" data-statement-unmatch
                                        data-unmatch-url="{{ route('bank-accounts.import.unmatch', $bankAccount) }}"
                                        data-business-entity-id="{{ $businessEntity->id }}"
                                        data-transaction-id="{{ $transaction->id }}"
                                        data-redirect="{{ $bankPanelHref }}"
                                        class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">Unmatch</button>
                                <button type="button" data-statement-remove-and-redo
                                        data-remove-url="{{ route('bank-accounts.import.remove-and-redo', $bankAccount) }}"
                                        data-business-entity-id="{{ $businessEntity->id }}"
                                        data-transaction-id="{{ $transaction->id }}"
                                        data-redirect="{{ $bankPanelHref }}"
                                        @if ($hasTransferSibling) data-has-transfer-sibling="1" @endif
                                        class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700">Remove &amp; Redo</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('bank-edit-transaction-form');
            const unpaidBlock = document.getElementById('unpaid_block');
            const paidBlock = document.getElementById('paid_block');
            const paymentStatusPaid = document.getElementById('payment_status_paid');
            const paymentStatusUnpaid = document.getElementById('payment_status_unpaid');

            function syncPaymentStatusBlocks() {
                const isPaid = paymentStatusPaid && paymentStatusPaid.checked;
                if (unpaidBlock) unpaidBlock.classList.toggle('hidden', isPaid);
                if (paidBlock) paidBlock.classList.toggle('hidden', !isPaid);
                window.refreshTransactionPaidByBankAccount?.(form);
            }
            if (paymentStatusPaid) paymentStatusPaid.addEventListener('change', syncPaymentStatusBlocks);
            if (paymentStatusUnpaid) paymentStatusUnpaid.addEventListener('change', syncPaymentStatusBlocks);
            syncPaymentStatusBlocks();

            const paidBySelect = document.getElementById('paid_by_select');
            const paidByOtherWrap = document.getElementById('paid_by_other_wrap');
            function syncPaidByOther() {
                if (!paidBySelect || !paidByOtherWrap) return;
                paidByOtherWrap.classList.toggle('hidden', paidBySelect.value !== 'other');
            }
            if (paidBySelect) paidBySelect.addEventListener('change', syncPaidByOther);
            syncPaidByOther();

            form?.addEventListener('submit', () => {
                const assetSelect = form.querySelector('select[name="asset_id"]');
                if (!assetSelect) return;
                const value = window.getSelectValue?.(assetSelect) ?? assetSelect.value;
                window.setSelectValue?.(assetSelect, value);
            });

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            async function postCorrection(url, body) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                    credentials: 'same-origin',
                });
                return { response, payload: await response.json().catch(() => ({})) };
            }

            document.querySelector('[data-statement-unmatch]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                if (!window.confirm('Unlink this bank line. The transaction stays so you can match it again.')) return;
                button.disabled = true;
                try {
                    const { response, payload } = await postCorrection(button.dataset.unmatchUrl, {
                        business_entity_id: Number(button.dataset.businessEntityId),
                        transaction_id: Number(button.dataset.transactionId),
                    });
                    if (!response.ok || !payload?.success) {
                        window.alert(payload?.message || 'Could not unmatch.');
                        return;
                    }
                    window.location.assign(button.dataset.redirect);
                } finally {
                    button.disabled = false;
                }
            });

            document.querySelector('[data-statement-remove-and-redo]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                if (!window.confirm('Delete this booking and return the bank line to unmatched?')) return;
                button.disabled = true;
                try {
                    const { response, payload } = await postCorrection(button.dataset.removeUrl, {
                        business_entity_id: Number(button.dataset.businessEntityId),
                        transaction_id: Number(button.dataset.transactionId),
                    });
                    if (!response.ok || !payload?.success) {
                        window.alert(payload?.message || 'Could not remove booking.');
                        return;
                    }
                    window.location.assign(button.dataset.redirect);
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
