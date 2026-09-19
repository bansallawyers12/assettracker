<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Edit Transaction for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    @php
        $isStatementLinked = $transaction->isLinkedToBankStatement();
        $statementEntry = $isStatementLinked ? $transaction->bankStatementEntries->first() : null;
        $isLoanActivity = (bool) ($bankAccount?->isLoanLedgerAccount());
        $signedAmount = $statementEntry
            ? (float) $statementEntry->amount
            : $transaction->bankAccountSignedAmount();
    @endphp

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div
                @class([
                    'bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4',
                    'border-amber-300 dark:border-amber-600' => $isStatementLinked,
                    'border-blue-300 dark:border-blue-600' => ! $isStatementLinked,
                ])
                @if ($isStatementLinked) data-statement-transaction-edit @endif
            >
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
                <form method="POST" id="bank-edit-transaction-form" action="{{ route('business-entities.transactions.update', [$businessEntity->id, $transaction->id]) }}" enctype="multipart/form-data" @if (! $isStatementLinked) data-manual-transaction-edit data-transaction-paid-by-form @endif data-require-bank-account-when-paid="false" data-booking-entity-id="{{ $businessEntity->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_origin" value="{{ $isStatementLinked ? 'statement' : 'manual' }}">
                    <input type="hidden" name="business_entity_id" id="business_entity_id" value="{{ $businessEntity->id }}">
                    @if (request()->filled('return_to'))
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @endif

                    @if ($transaction->isSplit())
                        <div class="mb-5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-100">
                            This is a split remittance ({{ $transaction->lines->count() }} allocations). Header amount and allocation types are fixed here.
                            @if ($isStatementLinked)
                                Date and bank stay locked to the statement. You can update description, asset, markers, and payment docs.
                            @else
                                You can update date, payment, and bank details. Amount stays at ${{ number_format((float) $transaction->amount, 2) }} (net to bank).
                            @endif
                        </div>
                    @endif

                    @php
                        $dir = $transaction->direction;
                        $oldDir = old('direction', $dir);
                        $oldStatus = $isStatementLinked ? 'paid' : old('payment_status', $transaction->payment_status ?? 'paid');
                        $oldChannel = $isStatementLinked
                            ? ($transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT)
                            : old('payment_channel', $transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT);
                    @endphp

                    @if ($isStatementLinked)
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100" data-statement-edit-locked-notice>
                            <p class="font-medium">Date, amount, and bank account are locked to the matched statement line.</p>
                            <p class="mt-1 text-amber-900/90 dark:text-amber-100/90">
                                You can still change type, GST (inclusive or manual), vendor, invoice number, and payment docs.
                                Use <span class="font-semibold">Unmatch</span> to change date, amount, or account.
                            </p>
                        </div>
                    @endif

                    @unless ($isStatementLinked)
                    {{-- Direction toggle --}}
                    <div class="flex gap-3 mb-5">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="expense" class="sr-only peer" {{ $oldDir === 'expense' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 peer-checked:text-red-700 dark:peer-checked:text-red-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                <x-lucide-arrow-down class="w-4 h-4" />
                                Expense
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="income" class="sr-only peer" {{ $oldDir === 'income' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 peer-checked:text-green-700 dark:peer-checked:text-green-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                <x-lucide-arrow-up class="w-4 h-4" />
                                Income
                            </div>
                        </label>
                    </div>
                    @endunless

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Date
                                @if ($isStatementLinked)
                                    <span class="ml-1 rounded bg-amber-200/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-normal text-amber-900 dark:bg-amber-900/60 dark:text-amber-100">Locked</span>
                                @endif
                            </label>
                            @if ($isStatementLinked)
                                <input type="hidden" name="date" value="{{ $transaction->date->toDateString() }}">
                                <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ ($statementEntry?->date ?? $transaction->date)->format('d/m/Y') }}</p>
                            @else
                                <x-date-input  name="date" value="{{ old('date', $transaction->date->toDateString()) }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required />
                            @endif
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Amount
                                @if ($isStatementLinked)
                                    <span class="ml-1 rounded bg-amber-200/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-normal text-amber-900 dark:bg-amber-900/60 dark:text-amber-100">Locked</span>
                                @endif
                            </label>
                            @if ($isStatementLinked)
                                <input type="hidden" name="amount" id="bank_edit_amount" value="{{ $transaction->amount }}">
                                <p class="mt-1 font-semibold tabular-nums {{ $signedAmount >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    {{ $signedAmount >= 0 ? '+' : '−' }}${{ number_format(abs($signedAmount), 2) }}
                                </p>
                            @else
                                <input type="number" name="amount" id="bank_edit_amount" step="0.01" value="{{ old('amount', $transaction->amount) }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @endif
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $transaction->description) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @include('partials.vendor-select', [
                            'vendors' => $vendors,
                            'selected' => old('vendor_id', $transaction->vendor_id),
                            'selectClass' => 'mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white',
                        ])

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Number <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $transaction->invoice_number) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="e.g., INV-0042">
                            @error('invoice_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $isLoanActivity ? 'Loan activity' : 'Transaction Type' }}</label>
                            @include('partials.transaction-type-select', [
                                'selected' => old('transaction_type', $transaction->transaction_type),
                                'bankAccount' => $bankAccount ?? $transaction->bankAccount,
                                'class' => 'mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white',
                            ])
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div id="counterpart_account_field" class="{{ old('transaction_type', $transaction->transaction_type) === 'internal_transfer' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transfer to / from account</label>
                            <select name="counterpart_bank_account_id" id="counterpart_bank_account_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
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

                        <div id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <x-tom-select name="related_entity_id" class="mt-1 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Related Entity</option>
                                @foreach ($relatedEntities as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', $transaction->related_entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <x-tom-select name="asset_id" class="mt-1 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None — entity only</option>
                                @foreach ($entityAssets as $asset)
                                    <option value="{{ $asset->id }}" {{ (string) old('asset_id', $transaction->asset_id) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @include('partials.transaction-marker-fields', ['transaction' => $transaction])

                        @php
                            $editGstBasis = old('gst_basis', $transaction->gst_basis ?? ((float) ($transaction->gst_amount ?? 0) > 0 ? 'inclusive' : ''));
                        @endphp
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST (10%)</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === '' || $editGstBasis === null ? 'checked' : '' }}> No GST
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="inclusive" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === 'inclusive' ? 'checked' : '' }}> GST inclusive — full amount includes 10%
                                </label>
                                @if (! $isStatementLinked || $editGstBasis === 'exclusive')
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="exclusive" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === 'exclusive' ? 'checked' : '' }}> GST exclusive — 10% on top
                                </label>
                                @endif
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="manual" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === 'manual' ? 'checked' : '' }}> Manual GST — enter amount from invoice (mixed rates)
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                @if ($isStatementLinked)
                                    Inclusive and Manual GST are part of the bank amount. Exclusive GST is not available while matched.
                                @else
                                    Inclusive/Exclusive auto-calculate. Use Manual when the invoice has mixed GST (some lines GST-free).
                                @endif
                            </p>
                            @error('gst_basis') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST amount <span class="text-gray-400 font-normal">(optional unless Manual)</span></label>
                            <input type="number" name="gst_amount" id="bank_edit_gst_amount" step="0.01" value="{{ old('gst_amount', $transaction->gst_amount) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    {{-- Payment Status --}}
                    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Payment Status</p>
                        @if ($isStatementLinked)
                            <input type="hidden" name="payment_status" id="payment_status_paid" value="paid">
                            <input type="hidden" name="payment_channel" id="payment_channel" value="{{ $transaction->payment_channel ?? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT }}">
                            @php
                                $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($transaction->paid_by);
                            @endphp
                            <input type="hidden" name="paid_by_select" value="{{ $pbSplit['select'] }}">
                            <input type="hidden" name="paid_by_other" value="{{ $pbSplit['other'] }}">
                            <input type="hidden" name="bank_account_id" value="{{ $transaction->bank_account_id }}">
                            <input type="hidden" name="paid_at" value="{{ $transaction->paid_at?->toDateString() ?? $transaction->date->toDateString() }}">
                            <input type="hidden" name="payment_method" value="{{ $transaction->payment_method }}">
                            <dl class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">Paid</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</dt>
                                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $bankAccount?->entityWorkspaceLabel($businessEntity) ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment date</dt>
                                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ ($transaction->paid_at ?? $transaction->date)->format('d/m/Y') }}</dd>
                                </div>
                            </dl>
                        @else
                            <div class="flex gap-3 mb-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="payment_status" value="paid" id="payment_status_paid" class="sr-only peer" {{ $oldStatus === 'paid' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:text-blue-700 dark:peer-checked:text-blue-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                        <x-lucide-check class="w-4 h-4" />
                                        Paid
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="payment_status" value="unpaid" id="payment_status_unpaid" class="sr-only peer" {{ $oldStatus === 'unpaid' ? 'checked' : '' }}>
                                    <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:text-amber-700 dark:peer-checked:text-amber-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                        <x-lucide-clock class="w-4 h-4" />
                                        Unpaid
                                    </div>
                                </label>
                            </div>

                            <div id="unpaid_block" class="{{ $oldStatus === 'unpaid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                    <x-date-input  name="due_date" value="{{ old('due_date', $transaction->due_date?->toDateString()) }}"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                                    @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div id="paid_block" class="{{ $oldStatus === 'paid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                                    <x-date-input  name="paid_at" value="{{ old('paid_at', $transaction->paid_at?->toDateString()) }}"
                                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" />
                                    @error('paid_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                                    <select name="payment_method" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select Method</option>
                                        @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                            <option value="{{ $val }}" {{ old('payment_method', $transaction->payment_method) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Channel</label>
                                    <select name="payment_channel" id="payment_channel" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        @foreach (\App\Models\Transaction::$paymentChannels as $value => $label)
                                            <option value="{{ $value }}" @selected($oldChannel === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @include('partials.payment-channel-funding-hint')
                                    @error('payment_channel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @php
                                    $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($transaction->paid_by);
                                @endphp
                                @include('partials.transaction-paid-by-fields', [
                                    'payerOptions' => $payerOptions,
                                    'paidBySelect' => $pbSplit['select'],
                                    'paidByOther' => $pbSplit['other'],
                                    'bankAccountId' => old('bank_account_id', $transaction->bank_account_id),
                                    'paidByLabelText' => $oldDir === 'income' ? 'Received By' : 'Paid By',
                                ])
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Payment Receipt <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                @if ($transaction->paymentDocument && $transaction->paymentDocument->path)
                                    <div class="mt-1 mb-2 text-xs text-gray-500 dark:text-gray-400">
                                        Current:
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($transaction->paymentDocument->path, now()->addMinutes(30)) }}"
                                           target="_blank"
                                           class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 underline">
                                            {{ $transaction->paymentDocument->display_name ?? 'View Receipt' }}
                                        </a>
                                        — upload a new file below to replace it
                                    </div>
                                @endif
                                <input type="file" name="payment_document"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 dark:file:bg-gray-700 dark:file:text-green-300"
                                       accept="{{ config('documents.transaction_file_accept') }}">
                                @error('payment_document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Receipt Name</label>
                                <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="e.g., Bank Transfer Confirmation">
                                @error('payment_document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-5">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-xs transition duration-200">Update Transaction</button>
                        <a href="{{ request('return_to') === 'bank-account' && $transaction->bank_account_id
                            ? route('business-entities.show', ['business_entity' => $businessEntity->id, 'open_bank_transactions' => $transaction->bank_account_id]).'#tab_bank_accounts'
                            : route('business-entities.show', $businessEntity->id).'#tab_transactions' }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 font-semibold py-2 px-4 rounded-md shadow-xs transition duration-200">Cancel</a>
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
                    <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700" data-statement-edit-full-form-path>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Wrong bank match?</h3>
                        <p class="mb-3 mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Need to change date, amount, or account?
                            <span class="font-medium text-gray-800 dark:text-gray-200">Unmatch</span> keeps this transaction and unlocks those fields on the next open.
                            <span class="font-medium text-gray-800 dark:text-gray-200">Remove &amp; Redo</span> deletes the booking and returns the statement line to unmatched.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                data-statement-unmatch
                                data-unmatch-url="{{ route('bank-accounts.import.unmatch', $bankAccount) }}"
                                data-business-entity-id="{{ $businessEntity->id }}"
                                data-transaction-id="{{ $transaction->id }}"
                                data-redirect="{{ $bankPanelHref }}"
                                class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                            >
                                Unmatch
                            </button>
                            <button
                                type="button"
                                data-statement-remove-and-redo
                                data-remove-url="{{ route('bank-accounts.import.remove-and-redo', $bankAccount) }}"
                                data-business-entity-id="{{ $businessEntity->id }}"
                                data-transaction-id="{{ $transaction->id }}"
                                data-redirect="{{ $bankPanelHref }}"
                                @if ($hasTransferSibling) data-has-transfer-sibling="1" @endif
                                class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300"
                            >
                                Remove &amp; Redo
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const directionRadios     = document.querySelectorAll('input[name="direction"]');
            const transactionTypeSelect = document.getElementById('transaction_type');
            const relatedEntityField  = document.getElementById('related_entity_field');
            const paymentStatusPaid   = document.getElementById('payment_status_paid');
            const paymentStatusUnpaid = document.getElementById('payment_status_unpaid');
            const unpaidBlock         = document.getElementById('unpaid_block');
            const paidBlock           = document.getElementById('paid_block');
            const paidByLabel         = document.getElementById('paid_by_label');

            const isStatementEdit = Boolean(document.querySelector('[data-statement-transaction-edit]'));

            function getDirection() {
                const checked = document.querySelector('input[name="direction"]:checked');
                return checked ? checked.value : 'expense';
            }

            function filterTypesByDirection(direction) {
                if (!transactionTypeSelect || isStatementEdit) return;
                Array.from(transactionTypeSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const optDir = opt.dataset.direction || '';
                    const match = !optDir || optDir === direction || optDir === 'both';
                    opt.hidden   = !match;
                    opt.disabled = !match;
                });
                if (transactionTypeSelect.options[transactionTypeSelect.selectedIndex]?.disabled) {
                    transactionTypeSelect.value = '';
                }
            }

            function updatePaidByLabel(direction) {
                if (paidByLabel) {
                    paidByLabel.textContent = direction === 'income' ? 'Received By' : 'Paid By';
                }
            }

            directionRadios.forEach(r => r.addEventListener('change', function () {
                filterTypesByDirection(this.value);
                updatePaidByLabel(this.value);
            }));
            filterTypesByDirection(getDirection());
            updatePaidByLabel(getDirection());

            function syncPaymentStatusBlocks() {
                const isPaid = paymentStatusPaid && paymentStatusPaid.checked;
                if (unpaidBlock) unpaidBlock.classList.toggle('hidden', isPaid);
                if (paidBlock)   paidBlock.classList.toggle('hidden', !isPaid);
            }
            if (paymentStatusPaid)   paymentStatusPaid.addEventListener('change', syncPaymentStatusBlocks);
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

            if (transactionTypeSelect && relatedEntityField) {
                const relatedPartyTypes = @json(\App\Models\Transaction::directorLoanRelatedPartyTypes());
                const counterpartField = document.getElementById('counterpart_account_field');
                function syncTypeDependentFields() {
                    const type = transactionTypeSelect.value;
                    const showRelated = relatedPartyTypes.includes(type);
                    relatedEntityField.style.display = showRelated ? 'block' : 'none';
                    const rs = relatedEntityField.querySelector('select');
                    if (rs) {
                        rs.required = showRelated;
                        if (!showRelated) window.setSelectValue?.(rs, '');
                    }
                    if (counterpartField) {
                        const showTransfer = type === 'internal_transfer';
                        counterpartField.classList.toggle('hidden', !showTransfer);
                        if (!showTransfer) {
                            const cs = counterpartField.querySelector('select');
                            if (cs) cs.value = '';
                        }
                    }
                }
                transactionTypeSelect.addEventListener('change', syncTypeDependentFields);
                syncTypeDependentFields();
            }

            (function bankEditGstCalc() {
                const form = document.getElementById('bank-edit-transaction-form');
                const amtEl = document.getElementById('bank_edit_amount');
                const gstEl = document.getElementById('bank_edit_gst_amount');
                if (!form || !amtEl || !gstEl) return;
                function basis() {
                    const r = form.querySelector('input[name="gst_basis"]:checked');
                    return r ? r.value : '';
                }
                function expectedGst(amount, b) {
                    if (b === 'inclusive') return Math.round((amount - amount / 1.1) * 100) / 100;
                    if (b === 'exclusive') return Math.round(amount * 0.1 * 100) / 100;
                    return null;
                }
                // Preserve invoice overrides (manual / non-standard GST) when editing the amount.
                let gstTouched = false;
                (function initGstTouched() {
                    const b = basis();
                    const a = parseFloat(amtEl.value);
                    const g = parseFloat(gstEl.value);
                    if (b === 'manual') {
                        gstTouched = true;
                        return;
                    }
                    if (Number.isNaN(a) || Number.isNaN(g) || g <= 0) return;
                    const expected = expectedGst(a, b);
                    if (expected !== null && Math.abs(expected - g) > 0.009) {
                        gstTouched = true;
                    }
                })();
                gstEl.addEventListener('input', () => { gstTouched = true; });
                function recalc() {
                    if (gstTouched) return;
                    const a = parseFloat(amtEl.value);
                    const b = basis();
                    if (!b || b === 'manual' || Number.isNaN(a)) {
                        if (b !== 'manual') gstEl.value = '';
                        return;
                    }
                    const expected = expectedGst(a, b);
                    if (expected !== null) gstEl.value = expected.toFixed(2);
                }
                amtEl.addEventListener('input', recalc);
                form.querySelectorAll('input[name="gst_basis"]').forEach((r) => r.addEventListener('change', () => {
                    if (!r.checked) return;
                    if (r.value === 'manual') {
                        gstTouched = true;
                        gstEl.value = '';
                        return;
                    }
                    gstTouched = false;
                    recalc();
                }));
            })();

            const editForm = document.getElementById('bank-edit-transaction-form');
            editForm?.addEventListener('submit', () => {
                const assetSelect = editForm.querySelector('select[name="asset_id"]');
                if (!assetSelect) return;
                const value = window.getSelectValue?.(assetSelect) ?? assetSelect.value;
                window.setSelectValue?.(assetSelect, value);
            });

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            function correctionErrorMessage(payload, fallback) {
                const fieldErrors = payload?.errors ? Object.values(payload.errors).flat().filter(Boolean) : [];
                return fieldErrors[0] || payload?.message || fallback;
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
                const payload = await response.json().catch(() => ({}));
                return { response, payload };
            }

            document.querySelector('[data-statement-unmatch]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                if (!window.confirm('Unlink this bank line. The transaction stays so you can match it again.')) {
                    return;
                }
                button.disabled = true;
                try {
                    const { response, payload } = await postCorrection(button.dataset.unmatchUrl, {
                        business_entity_id: Number(button.dataset.businessEntityId),
                        transaction_id: Number(button.dataset.transactionId),
                    });
                    if (!response.ok || !payload?.success) {
                        window.alert(correctionErrorMessage(payload, 'Could not unmatch.'));
                        return;
                    }
                    window.location.assign(button.dataset.redirect);
                } catch (error) {
                    window.alert(error?.message || 'Could not unmatch.');
                } finally {
                    button.disabled = false;
                }
            });

            document.querySelector('[data-statement-remove-and-redo]')?.addEventListener('click', async (event) => {
                const button = event.currentTarget;
                let message = 'Delete this booking and return the bank line to unmatched. If this paid invoices, they go back to unpaid/partial.';
                if (button.dataset.hasTransferSibling === '1') {
                    message += ' The other side of this internal transfer will stay booked.';
                }
                if (!window.confirm(message)) {
                    return;
                }
                button.disabled = true;
                try {
                    const { response, payload } = await postCorrection(button.dataset.removeUrl, {
                        business_entity_id: Number(button.dataset.businessEntityId),
                        transaction_id: Number(button.dataset.transactionId),
                    });
                    if (!response.ok || !payload?.success) {
                        window.alert(correctionErrorMessage(payload, 'Could not remove booking.'));
                        return;
                    }
                    window.location.assign(button.dataset.redirect);
                } catch (error) {
                    window.alert(error?.message || 'Could not remove booking.');
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
