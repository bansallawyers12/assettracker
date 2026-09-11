<x-app-layout>
    @php
        $statusBadge = match ($invoice->status) {
            'draft' => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
            'approved' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-900',
            'partial' => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-900',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900',
            'void' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900',
            default => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
        };
        $amountDue = $amountDue ?? $invoice->amountDue();
        $amountPaid = round((float) $invoice->total_amount - $amountDue, 2);
        $isOverdue = in_array($invoice->status, ['approved', 'partial'], true) && $invoice->due_date && $invoice->due_date->isPast();
        $canRecordPayment = $canRecordPayment ?? (in_array($invoice->status, ['approved', 'partial'], true) && $amountDue > 0.005);
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
                @elseif (! $invoice->hasPaymentAllocations() && ! $invoice->payment_transaction_id)
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
                                ${{ number_format($invoice->status === 'paid' ? (float) $invoice->total_amount : $amountDue, 2) }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->currency }}</p>
                            <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-gray-400">Invoice total</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($invoice->total_amount, 2) }}</dd>
                                </div>
                                @if ($amountPaid > 0)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-gray-500 dark:text-gray-400">Paid to date</dt>
                                        <dd class="font-medium text-emerald-700 dark:text-emerald-300">${{ number_format($amountPaid, 2) }}</dd>
                                    </div>
                                @endif
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

            @if ($invoice->paymentAllocations->isNotEmpty())
                <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <div class="flex flex-col gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">
                                {{ $invoice->status === 'paid' ? 'Payment recorded' : 'Payments received' }}
                            </h3>
                            @if ($invoice->status === 'paid' && $invoice->paid_at)
                                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                                    Fully paid on {{ $invoice->paid_at->format('d/m/Y') }}
                                    @if ($invoice->payment_method)
                                        — {{ \App\Models\Transaction::$paymentMethods[$invoice->payment_method] ?? $invoice->payment_method }}
                                    @endif
                                    @if ($invoice->payment_reference) ({{ $invoice->payment_reference }}) @endif
                                </p>
                            @elseif ($invoice->status === 'partial')
                                <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                                    ${{ number_format($amountPaid, 2) }} paid · ${{ number_format($amountDue, 2) }} remaining
                                </p>
                            @endif
                        </div>
                        <ul class="space-y-2">
                            @foreach ($invoice->paymentAllocations->sortBy('id') as $allocation)
                                @php $payTx = $allocation->transaction; @endphp
                                <li class="rounded-lg border border-emerald-100/80 bg-white/70 px-3 py-2 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium tabular-nums">${{ number_format((float) $allocation->amount, 2) }}</span>
                                        <span class="text-xs text-emerald-700 dark:text-emerald-300">
                                            {{ $payTx?->paid_at?->format('d/m/Y') ?? $payTx?->date?->format('d/m/Y') ?? '—' }}
                                            @if ($payTx)
                                                · receipt #{{ $payTx->id }}
                                                @if ($payTx->bankStatementEntries->isNotEmpty())
                                                    · matched to bank
                                                @endif
                                            @endif
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
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
                                    <td class="px-4 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ (float) $line->unit_price < 0 ? '-' : '' }}${{ number_format(abs((float) $line->unit_price), 2) }}</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ (float) $line->gst_rate * 100 }}%</td>
                                    <td class="px-4 py-3.5 text-right tabular-nums font-semibold text-gray-900 dark:text-white">{{ (float) $line->line_total < 0 ? '-' : '' }}${{ number_format(abs((float) $line->line_total), 2) }}</td>
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

            @if ($canRecordPayment)
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-6 dark:border-gray-800 dark:bg-gray-800/40">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Record payment</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Clears Accounts Receivable for the amount paid (does not re-book revenue). Partial payments leave the invoice open until the balance is zero. Use <span class="font-medium">Director funds</span> when the customer paid the director (or the director settled it) and money did not enter the entity bank.
                            </p>
                            <form method="POST" action="{{ route('business-entities.invoices.record-payment', [$businessEntity, $invoice]) }}" class="mt-4 space-y-3" enctype="multipart/form-data" id="invoice-record-payment-form">
                                @csrf
                                @php
                                    $hasPaymentBanks = ($paymentBankAccounts ?? collect())->isNotEmpty();
                                    $selectedChannel = old(
                                        'payment_channel',
                                        $hasPaymentBanks
                                            ? \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT
                                            : \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS
                                    );
                                    if (! $hasPaymentBanks) {
                                        $selectedChannel = \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS;
                                    }
                                    $isBankChannel = $selectedChannel === \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT;
                                @endphp
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Paid via <span class="text-red-500">*</span></label>
                                    <select name="payment_channel" id="invoice_payment_channel" required class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                        <option value="{{ \App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT }}" @selected($isBankChannel) @disabled(! $hasPaymentBanks)>
                                            Bank account
                                        </option>
                                        <option value="{{ \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS }}" @selected(! $isBankChannel)>
                                            Director funds (no bank)
                                        </option>
                                    </select>
                                    @unless ($hasPaymentBanks)
                                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">No operating bank linked — use director funds, or link a bank account first.</p>
                                    @endunless
                                    @error('payment_channel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Paid date <span class="text-red-500">*</span></label>
                                    <x-date-input name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                                    @error('paid_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Amount <span class="text-red-500">*</span></label>
                                    <input
                                        type="number"
                                        name="amount"
                                        id="invoice_payment_amount"
                                        step="0.01"
                                        min="0.01"
                                        max="{{ number_format($amountDue, 2, '.', '') }}"
                                        value="{{ old('amount', number_format($amountDue, 2, '.', '')) }}"
                                        required
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Balance remaining: ${{ number_format($amountDue, 2) }}</p>
                                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div id="invoice_payment_bank_fields" class="space-y-3" @if(! $isBankChannel) style="display: none" @endif>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Bank account <span class="text-red-500">*</span></label>
                                        <select name="bank_account_id" id="invoice_payment_bank_account_id"
                                                class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                                @disabled(! $isBankChannel)
                                                @if($isBankChannel) required @endif>
                                            <option value="">Select account…</option>
                                            @foreach ($paymentBankAccounts ?? [] as $account)
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
                                        <select name="bank_statement_entry_id" id="invoice_payment_statement_entry_id"
                                                class="w-full rounded-lg border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                                @disabled(! $isBankChannel)>
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
                                    const channelSelect = document.getElementById('invoice_payment_channel');
                                    const bankFields = document.getElementById('invoice_payment_bank_fields');
                                    const accountSelect = document.getElementById('invoice_payment_bank_account_id');
                                    const entrySelect = document.getElementById('invoice_payment_statement_entry_id');
                                    const amountInput = document.getElementById('invoice_payment_amount');
                                    if (!channelSelect || !bankFields) return;

                                    const bankChannel = @json(\App\Models\Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT);
                                    const amountDue = {{ json_encode((float) $amountDue) }};

                                    function syncChannelUi() {
                                        const isBank = channelSelect.value === bankChannel;
                                        bankFields.style.display = isBank ? '' : 'none';
                                        if (accountSelect) {
                                            accountSelect.required = isBank;
                                            accountSelect.disabled = !isBank;
                                            if (!isBank) {
                                                accountSelect.value = '';
                                            }
                                        }
                                        if (entrySelect) {
                                            entrySelect.disabled = !isBank;
                                            if (!isBank) {
                                                entrySelect.value = '';
                                            }
                                        }
                                    }

                                    channelSelect.addEventListener('change', function () {
                                        syncChannelUi();
                                        if (channelSelect.value === bankChannel) {
                                            syncStatementOptions();
                                        }
                                    });
                                    syncChannelUi();

                                    if (!accountSelect || !entrySelect) return;

                                    function syncStatementOptions() {
                                        if (channelSelect.value !== bankChannel) return;
                                        const accountId = accountSelect.value;
                                        const paymentAmount = Math.abs(parseFloat(amountInput?.value || String(amountDue)) || amountDue);
                                        Array.from(entrySelect.options).forEach((opt) => {
                                            if (!opt.value) {
                                                opt.hidden = false;
                                                return;
                                            }
                                            const matchesAccount = !accountId || String(opt.dataset.bankAccountId) === String(accountId);
                                            const amount = Math.abs(parseFloat(opt.dataset.amount || '0'));
                                            const amountPositive = parseFloat(opt.dataset.amount || '0') >= 0;
                                            const withinBalance = amount <= amountDue + 0.01;
                                            const matchesSelectedAmount = Math.abs(amount - paymentAmount) <= 0.01;
                                            opt.hidden = !(matchesAccount && amountPositive && withinBalance && matchesSelectedAmount);
                                            if (opt.hidden && opt.selected) {
                                                entrySelect.value = '';
                                            }
                                        });
                                    }

                                    accountSelect.addEventListener('change', syncStatementOptions);
                                    amountInput?.addEventListener('input', syncStatementOptions);
                                    entrySelect.addEventListener('change', function () {
                                        const selected = entrySelect.options[entrySelect.selectedIndex];
                                        if (selected?.value && amountInput) {
                                            amountInput.value = Math.abs(parseFloat(selected.dataset.amount || '0')).toFixed(2);
                                            syncStatementOptions();
                                        }
                                    });

                                    const suggestedOpt = entrySelect.querySelector('option[selected]');
                                    if (suggestedOpt?.dataset?.bankAccountId && !accountSelect.value) {
                                        accountSelect.value = String(suggestedOpt.dataset.bankAccountId);
                                    }

                                    syncStatementOptions();

                                    if (suggestedOpt?.value && !suggestedOpt.hidden && channelSelect.value === bankChannel) {
                                        entrySelect.value = suggestedOpt.value;
                                        if (amountInput) {
                                            amountInput.value = Math.abs(parseFloat(suggestedOpt.dataset.amount || String(amountDue))).toFixed(2);
                                        }
                                    }
                                });
                            </script>
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
