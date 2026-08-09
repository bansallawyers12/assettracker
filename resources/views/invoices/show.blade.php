<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Invoice {{ $invoice->invoice_number }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @if ($invoice->asset_id)
                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $invoice->asset_id]) }}#tab_invoices" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                        Back to asset
                    </a>
                @endif
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    All invoices
                </a>
                @if (!$invoice->is_posted)
                    <form method="POST" action="{{ route('business-entities.invoices.post', [$businessEntity, $invoice]) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">Post to ledger</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">{{ session('info') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-200 dark:border-gray-700">
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500 dark:text-gray-400">Customer</span><br><span class="font-medium text-gray-900 dark:text-white">{{ $invoice->customer_name }}</span></div>
                    @if ($invoice->lease?->tenant)
                        <div><span class="text-gray-500 dark:text-gray-400">Tenant email</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->lease->tenant->email ?? '—' }}</span></div>
                    @endif
                    @if ($invoice->reference)
                        <div><span class="text-gray-500 dark:text-gray-400">Reference</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->reference }}</span></div>
                    @endif
                    <div><span class="text-gray-500 dark:text-gray-400">Issue date</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->issue_date->format('d/m/Y') }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Due date</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Status</span><br>
                        @php
                            $invStatusClass = match ($invoice->status) {
                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                'void' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                            };
                        @endphp
                        <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $invStatusClass }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">Posted</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->is_posted ? 'Yes' : 'No' }}</span></div>
                </div>
                <div class="text-right space-y-1 text-sm">
                    <div class="text-gray-500 dark:text-gray-400">Subtotal</div>
                    <div class="text-lg text-gray-900 dark:text-white">${{ number_format($invoice->subtotal, 2) }}</div>
                    <div class="text-gray-500 dark:text-gray-400">GST</div>
                    <div class="text-gray-900 dark:text-white">${{ number_format($invoice->gst_amount, 2) }}</div>
                    <div class="text-gray-500 dark:text-gray-400 pt-2 font-semibold">Total ({{ $invoice->currency }})</div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($invoice->total_amount, 2) }}</div>
                </div>
            </div>

            @if ($invoice->status === 'paid' && $invoice->paid_at)
                <div class="px-6 py-4 bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-900/40 text-sm">
                    <h3 class="font-semibold text-green-900 dark:text-green-200 mb-2">Payment recorded</h3>
                    <p class="text-green-800 dark:text-green-300">Paid on {{ $invoice->paid_at->format('d/m/Y') }}
                        @if ($invoice->payment_method)
                            — {{ \App\Models\Transaction::$paymentMethods[$invoice->payment_method] ?? $invoice->payment_method }}
                        @endif
                        @if ($invoice->payment_reference) ({{ $invoice->payment_reference }}) @endif
                    </p>
                    @if ($invoice->paymentTransaction)
                        @php $payTx = $invoice->paymentTransaction; @endphp
                        <p class="mt-2 text-green-800 dark:text-green-300">
                            Accounting receipt #{{ $payTx->id }}
                            @if ($payTx->bankAccount)
                                on {{ $payTx->bankAccount->account_name ?: $payTx->bankAccount->bank_name }}
                            @endif
                            —
                            @if ($payTx->bankStatementEntries->isNotEmpty())
                                matched to bank statement
                            @else
                                unmatched (statement line can be linked later)
                            @endif
                        </p>
                        @if ($payTx->bank_account_id)
                            <a
                                href="{{ route('business-entities.show', [
                                    'business_entity' => $businessEntity->id,
                                    'open_bank_transactions' => $payTx->bank_account_id,
                                ]) }}#tab_bank_accounts"
                                class="inline-flex mt-2 text-sm font-medium text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200"
                            >
                                Open bank account transactions
                            </a>
                        @endif
                    @endif
                </div>
            @endif

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Line items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4">Description</th>
                                <th class="py-2 pr-4">Qty</th>
                                <th class="py-2 pr-4">Unit</th>
                                <th class="py-2 pr-4">GST %</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($invoice->lines as $line)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ $line->description }}</td>
                                    <td class="py-2 pr-4">{{ $line->quantity }}</td>
                                    <td class="py-2 pr-4">${{ number_format($line->unit_price, 2) }}</td>
                                    <td class="py-2 pr-4">{{ (float) $line->gst_rate * 100 }}%</td>
                                    <td class="py-2 text-right font-medium">${{ number_format($line->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($invoice->notes)
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Notes</span>
                    <p class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $invoice->notes }}</p>
                </div>
            @endif

            @if ($invoice->status === 'approved' && !$invoice->paid_at)
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Record payment</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Clears Accounts Receivable (does not re-book revenue). Optionally match a bank statement line now, or leave unmatched until the statement arrives.
                        </p>
                        @if (($paymentBankAccounts ?? collect())->isEmpty())
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                Link an operating bank account to this entity before recording payment.
                            </p>
                        @else
                            <form method="POST" action="{{ route('business-entities.invoices.record-payment', [$businessEntity, $invoice]) }}" class="space-y-3" enctype="multipart/form-data" id="invoice-record-payment-form">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Paid date <span class="text-red-500">*</span></label>
                                    <x-date-input name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                    @error('paid_at') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Bank account <span class="text-red-500">*</span></label>
                                    <select name="bank_account_id" id="invoice_payment_bank_account_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs text-sm">
                                        <option value="">Select account…</option>
                                        @foreach ($paymentBankAccounts as $account)
                                            @php
                                                $accountSelected = old('bank_account_id') !== null
                                                    ? (string) old('bank_account_id') === (string) $account->id
                                                    : (int) ($suggestedPaymentBankAccountId ?? 0) === (int) $account->id;
                                            @endphp
                                            <option value="{{ $account->id }}" @selected($accountSelected)>
                                                {{ $account->transactionAccountLabel() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Match statement line <span class="font-normal text-gray-400">(optional)</span></label>
                                    <select name="bank_statement_entry_id" id="invoice_payment_statement_entry_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs text-sm">
                                        <option value="">— Leave unmatched —</option>
                                        @foreach (($unmatchedStatementEntries ?? collect()) as $entry)
                                            @php
                                                $isSuggested = (int) ($suggestedStatementEntryId ?? 0) === (int) $entry->id;
                                                $selected = old('bank_statement_entry_id') !== null
                                                    ? (string) old('bank_statement_entry_id') === (string) $entry->id
                                                    : $isSuggested;
                                            @endphp
                                            <option
                                                value="{{ $entry->id }}"
                                                data-bank-account-id="{{ $entry->bank_account_id }}"
                                                data-amount="{{ $entry->amount }}"
                                                @selected($selected)
                                            >
                                                @if($isSuggested)★ @endif{{ $entry->date?->format('d/m/Y') }} · ${{ number_format((float) $entry->amount, 2) }} · {{ \Illuminate\Support\Str::limit($entry->description ?: 'No description', 48) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_statement_entry_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Payment method</label>
                                    <select name="payment_method" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs text-sm">
                                        <option value="">Select method…</option>
                                        @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                            <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    @error('payment_method') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Reference</label>
                                    <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="Receipt / transaction ID" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs text-sm" />
                                    @error('payment_reference') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Payment receipt <span class="font-normal text-gray-400">(optional)</span></label>
                                    <input type="file" name="payment_document" accept="{{ config('documents.transaction_file_accept') }}" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700 dark:file:bg-gray-700 dark:file:text-emerald-300" />
                                    @error('payment_document') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Receipt name</label>
                                    <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}" placeholder="e.g. Bank transfer confirmation" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-xs text-sm" />
                                </div>
                                <button type="submit" class="w-full sm:w-auto inline-flex justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">Record payment</button>
                            </form>
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const accountSelect = document.getElementById('invoice_payment_bank_account_id');
                                    const entrySelect = document.getElementById('invoice_payment_statement_entry_id');
                                    if (!accountSelect || !entrySelect) return;

                                    const invoiceTotal = {{ json_encode((float) $invoice->total_amount) }};

                                    function syncStatementOptions() {
                                        const accountId = accountSelect.value;
                                        Array.from(entrySelect.options).forEach((opt) => {
                                            if (!opt.value) {
                                                opt.hidden = false;
                                                return;
                                            }
                                            const matchesAccount = !accountId || String(opt.dataset.bankAccountId) === String(accountId);
                                            const amount = Math.abs(parseFloat(opt.dataset.amount || '0'));
                                            const matchesAmount = Math.abs(amount - invoiceTotal) <= 0.005;
                                            const amountPositive = parseFloat(opt.dataset.amount || '0') >= 0;
                                            opt.hidden = !(matchesAccount && matchesAmount && amountPositive);
                                            if (opt.hidden && opt.selected) {
                                                entrySelect.value = '';
                                            }
                                        });
                                    }

                                    accountSelect.addEventListener('change', syncStatementOptions);

                                    // Keep suggested statement + its bank account aligned on first paint.
                                    const suggestedOpt = entrySelect.querySelector('option[selected]');
                                    if (suggestedOpt?.dataset?.bankAccountId && !accountSelect.value) {
                                        accountSelect.value = String(suggestedOpt.dataset.bankAccountId);
                                    }

                                    syncStatementOptions();

                                    if (suggestedOpt?.value && !suggestedOpt.hidden) {
                                        entrySelect.value = suggestedOpt.value;
                                    }
                                });
                            </script>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Follow up</h3>
                        @if ($invoice->lease?->tenant?->email)
                            <form method="POST" action="{{ route('business-entities.invoices.remind', [$businessEntity, $invoice]) }}" onsubmit="return confirm('Send reminder email?');">
                                @csrf
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Send a payment reminder to <strong>{{ $invoice->lease->tenant->email }}</strong>.</p>
                                @if ($invoice->last_reminder_sent_at)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Last sent: {{ $invoice->last_reminder_sent_at->format('d/m/Y H:i') }} ({{ $invoice->reminder_count }} total)</p>
                                @endif
                                <button type="submit" class="inline-flex px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">Send reminder email</button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Add a tenant email on the lease to send reminders.</p>
                        @endif
                    </div>
                </div>
            @endif

            @if (!$invoice->is_posted)
                <div class="px-6 py-4 flex flex-wrap gap-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('business-entities.invoices.edit', [$businessEntity, $invoice]) }}" class="inline-flex px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Edit</a>
                    <form method="POST" action="{{ route('business-entities.invoices.destroy', [$businessEntity, $invoice]) }}" onsubmit="return confirm('Delete this invoice?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
