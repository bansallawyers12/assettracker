<x-app-layout>
    @php
        $statusBadge = match ($invoice->status) {
            'draft' => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
            'approved' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-900',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900',
            'void' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900',
            default => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
        };
        $isOverdue = $invoice->status === 'approved' && $invoice->due_date && $invoice->due_date->isPast();
        $gstBasisLabel = match ($invoice->gst_basis) {
            'none' => 'GST not applicable',
            'exclusive' => 'Exclusive (unit prices ex GST)',
            default => 'Inclusive (unit prices inc GST)',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $businessEntity->legal_name }}
                </p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                        {{ $invoice->invoice_number }}
                    </h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusBadge }}">
                        {{ \App\Models\Invoice::$statuses[$invoice->status] ?? ucfirst($invoice->status) }}
                    </span>
                    @if ($isOverdue)
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900">
                            Overdue
                        </span>
                    @endif
                    @if ($invoice->is_posted)
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900">
                            Posted
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">
                            Not posted
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Issued {{ $invoice->issue_date->format('d/m/Y') }}
                    @if ($invoice->due_date)
                        · Due {{ $invoice->due_date->format('d/m/Y') }}
                    @endif
                    @if ($invoice->customer_name)
                        · {{ $invoice->customer_name }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($invoice->asset_id)
                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $invoice->asset_id]) }}#tab_invoices"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                        Back to asset
                    </a>
                @endif
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    All invoices
                </a>
                <a href="{{ route('business-entities.invoices.download', [$businessEntity, $invoice]) }}"
                   target="_blank"
                   rel="noopener"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    <x-lucide-download class="h-4 w-4" aria-hidden="true" />
                    Download
                </a>
                @if (!$invoice->is_posted)
                    <form method="POST" action="{{ route('business-entities.invoices.post', [$businessEntity, $invoice]) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                            <x-lucide-book-check class="h-4 w-4" aria-hidden="true" />
                            Post to ledger
                        </button>
                    </form>
                @elseif (!$invoice->payment_transaction_id)
                    <form method="POST" action="{{ route('business-entities.invoices.unpost', [$businessEntity, $invoice]) }}" class="inline"
                          data-confirm
                          data-confirm-title="Unpost invoice?"
                          data-confirm-message="Unpost this invoice and remove its ledger entry?"
                          data-confirm-button="Unpost"
                          data-confirm-variant="danger">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500">
                            Unpost
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">{{ session('info') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            {{-- Document summary --}}
            <div class="border-b border-gray-200 bg-gradient-to-br from-gray-50 via-white to-indigo-50/40 px-6 py-6 dark:border-gray-800 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950/20">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                    <div class="lg:col-span-4 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bill to</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $invoice->customer_name ?: '—' }}</p>
                            @if ($invoice->lease?->tenant?->email)
                                <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $invoice->lease->tenant->email }}</p>
                            @endif
                        </div>
                        @if ($invoice->asset)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Property</p>
                                <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $invoice->asset_id]) }}"
                                   class="mt-1 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    {{ $invoice->asset->name }}
                                </a>
                            </div>
                        @endif
                        @if ($invoice->reference)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $invoice->reference }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="lg:col-span-4 grid grid-cols-2 gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-gray-200/80 bg-white/80 px-3.5 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Issue date</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200/80 bg-white/80 px-3.5 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Due date</p>
                            <p class="mt-1 text-sm font-semibold {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}
                            </p>
                        </div>
                        <div class="col-span-2 rounded-lg border border-gray-200/80 bg-white/80 px-3.5 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">GST basis</p>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $gstBasisLabel }}</p>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="rounded-xl border border-indigo-200 bg-white p-5 shadow-xs dark:border-indigo-900/60 dark:bg-gray-800/80">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">
                                {{ $invoice->status === 'paid' ? 'Amount paid' : 'Amount due' }}
                            </p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                                ${{ number_format($invoice->total_amount, 2) }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->currency }}</p>
                            <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($invoice->subtotal, 2) }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-gray-400">GST</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($invoice->gst_amount, 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            @if ($invoice->status === 'paid' && $invoice->paid_at)
                <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Payment recorded</h3>
                            <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                                Paid on {{ $invoice->paid_at->format('d/m/Y') }}
                                @if ($invoice->payment_method)
                                    — {{ \App\Models\Transaction::$paymentMethods[$invoice->payment_method] ?? $invoice->payment_method }}
                                @endif
                                @if ($invoice->payment_reference) ({{ $invoice->payment_reference }}) @endif
                            </p>
                            @if ($invoice->paymentTransaction)
                                @php $payTx = $invoice->paymentTransaction; @endphp
                                <p class="mt-2 text-sm text-emerald-800 dark:text-emerald-300">
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
                            @endif
                        </div>
                        @if ($invoice->paymentTransaction?->bank_account_id)
                            <a
                                href="{{ route('business-entities.show', [
                                    'business_entity' => $businessEntity->id,
                                    'open_bank_transactions' => $invoice->paymentTransaction->bank_account_id,
                                ]) }}#tab_bank_accounts"
                                class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200"
                            >
                                Open bank transactions
                                <x-lucide-arrow-right class="h-4 w-4" aria-hidden="true" />
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="px-6 py-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Line items</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->lines->count() }} {{ \Illuminate\Support\Str::plural('line', $invoice->lines->count()) }}</p>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50/90 dark:bg-gray-800/60">
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">GST %</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($invoice->lines as $line)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                                    <td class="px-4 py-3.5 text-gray-900 dark:text-gray-100">{{ $line->description }}</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ number_format((float) $line->quantity, 4) }}</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300">${{ number_format($line->unit_price, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ (float) $line->gst_rate * 100 }}%</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">${{ number_format($line->line_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No line items on this invoice.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/40">
                            <tr>
                                <td colspan="4" class="px-4 py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">Subtotal</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-sm font-medium text-gray-900 dark:text-white">${{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-4 py-2.5 text-right text-sm text-gray-500 dark:text-gray-400">GST</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-sm font-medium text-gray-900 dark:text-white">${{ number_format($invoice->gst_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">Total ({{ $invoice->currency }})</td>
                                <td class="px-4 py-3 text-right text-base font-bold tabular-nums text-indigo-600 dark:text-indigo-400">${{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($invoice->notes)
                <div class="border-t border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notes</h3>
                    <p class="mt-2 whitespace-pre-wrap rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-200">{{ $invoice->notes }}</p>
                </div>
            @endif

            @if ($invoice->status === 'approved' && !$invoice->paid_at)
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-6 dark:border-gray-800 dark:bg-gray-800/40">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Record payment</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Clears Accounts Receivable (does not re-book revenue). Optionally match a bank statement line now, or leave unmatched until the statement arrives.
                            </p>
                            @if (($paymentBankAccounts ?? collect())->isEmpty())
                                <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">
                                    Link an operating bank account to this entity before recording payment.
                                </p>
                            @else
                                <form method="POST" action="{{ route('business-entities.invoices.record-payment', [$businessEntity, $invoice]) }}" class="mt-4 space-y-3" enctype="multipart/form-data" id="invoice-record-payment-form">
                                    @csrf
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Paid date <span class="text-red-500">*</span></label>
                                        <x-date-input name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                        @error('paid_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Bank account <span class="text-red-500">*</span></label>
                                        <select name="bank_account_id" id="invoice_payment_bank_account_id" required class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
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
                                        @error('bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Match statement line <span class="font-normal text-gray-400">(optional)</span></label>
                                        <select name="bank_statement_entry_id" id="invoice_payment_statement_entry_id" class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
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
                                        @error('bank_statement_entry_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Payment method</label>
                                        <select name="payment_method" class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                            <option value="">Select method…</option>
                                            @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                                <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                        @error('payment_method') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Reference</label>
                                        <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="Receipt / transaction ID" class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                        @error('payment_reference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Payment receipt <span class="font-normal text-gray-400">(optional)</span></label>
                                        <input type="file" name="payment_document" accept="{{ config('documents.transaction_file_accept') }}" class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700 dark:file:bg-gray-700 dark:file:text-emerald-300" />
                                        @error('payment_document') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Receipt name</label>
                                        <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}" placeholder="e.g. Bank transfer confirmation" class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                    </div>
                                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 sm:w-auto">Record payment</button>
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
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Follow up</h3>
                            @if ($invoice->lease?->tenant?->email)
                                <form method="POST" action="{{ route('business-entities.invoices.remind', [$businessEntity, $invoice]) }}" class="mt-3"
                                      data-confirm
                                      data-confirm-title="Send reminder?"
                                      data-confirm-message="Send reminder email?"
                                      data-confirm-button="Send reminder">
                                    @csrf
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Send a payment reminder to <strong class="text-gray-900 dark:text-white">{{ $invoice->lease->tenant->email }}</strong>.</p>
                                    @if ($invoice->last_reminder_sent_at)
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Last sent: {{ $invoice->last_reminder_sent_at->format('d/m/Y H:i') }} ({{ $invoice->reminder_count }} total)</p>
                                    @endif
                                    <button type="submit" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                                        <x-lucide-mail class="h-4 w-4" aria-hidden="true" />
                                        Send reminder email
                                    </button>
                                </form>
                            @else
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Add a tenant email on the lease to send reminders.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if (!$invoice->is_posted)
                <div class="flex flex-wrap gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                    <a href="{{ route('business-entities.invoices.edit', [$businessEntity, $invoice]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                        <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                        Edit
                    </a>
                    <form method="POST" action="{{ route('business-entities.invoices.destroy', [$businessEntity, $invoice]) }}"
                          data-confirm
                          data-confirm-title="Delete invoice?"
                          data-confirm-message="Delete this invoice? This cannot be undone."
                          data-confirm-button="Delete"
                          data-confirm-variant="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:bg-gray-900 dark:text-rose-300 dark:hover:bg-rose-950/40">
                            <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                            Delete
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
