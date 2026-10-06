@php
    $isEdit = isset($invoice) && $invoice;
    $formAction = $isEdit
        ? route('business-entities.invoices.update', [$businessEntity, $invoice])
        : route('business-entities.invoices.store', $businessEntity);
    $suggestNumberUrl = route('business-entities.invoices.suggest-number', $businessEntity);
    $cancelUrl = $isEdit
        ? route('business-entities.invoices.show', [$businessEntity, $invoice])
        : route('business-entities.invoices.index', $businessEntity);
    $collapseLine = function (array $line) use ($defaultAccountCode): array {
        $quantity = (float) ($line['quantity'] ?? 1);
        if ($quantity <= 0) {
            $quantity = 1;
        }
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $taxCode = $line['tax_code'] ?? null;
        if (! in_array($taxCode, ['gst', 'free'], true)) {
            if (array_key_exists('gst_rate', $line) && (float) $line['gst_rate'] > 0) {
                $taxCode = 'gst';
            } elseif (! empty($line['legacy_unrated'])) {
                // Old mixed drafts stored one invoice GST total and a zero rate on every line.
                // Leave the rate blank so a save cannot treat those lines as GST Free.
                $taxCode = '';
            } elseif (array_key_exists('gst_rate', $line)) {
                $taxCode = 'free';
            } else {
                $taxCode = 'gst';
            }
        }

        return [
            'description' => $line['description'] ?? '',
            'quantity' => 1,
            // Qty is hidden (always 1); fold existing qty into the unit price so totals stay correct.
            'unit_price' => round($quantity * $unitPrice, 2),
            'account_code' => $line['account_code'] ?? $defaultAccountCode,
            'tax_code' => $taxCode,
        ];
    };
    $legacyMixedGst = $isEdit
        && ($invoice->gst_basis ?? null) === 'manual'
        && (float) $invoice->gst_amount > 0
        && $invoice->lines->every(fn ($line) => (float) $line->gst_rate <= 0);
    $defaultLines = $isEdit
        ? $invoice->lines->map(fn ($line) => $collapseLine([
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'account_code' => $line->account_code ?? $defaultAccountCode,
            'gst_rate' => (float) $line->gst_rate,
            'legacy_unrated' => $legacyMixedGst,
        ]))->values()->all()
        : [$collapseLine([
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'account_code' => $defaultAccountCode,
        ])];
    $oldLines = old('lines');
    $formConfig = [
        'assets' => $assetsForForm,
        'lineAccounts' => $lineAccounts->map(fn ($a) => [
            'code' => $a->account_code,
            'label' => $a->account_code.' — '.$a->account_name,
        ])->values(),
        'defaultAccountCode' => $defaultAccountCode,
        'assetId' => old('asset_id', $isEdit ? $invoice->asset_id : null),
        'leaseId' => old('lease_id', $isEdit ? $invoice->lease_id : null),
        'customerName' => old('customer_name', $isEdit ? $invoice->customer_name : ''),
        'customerAbn' => old('customer_abn', $isEdit ? ($invoice->lease?->tenant?->abn ?? '') : ''),
        'reference' => old('reference', $isEdit ? $invoice->reference : ''),
        'notes' => old('notes', $isEdit ? $invoice->notes : ''),
        'gstBasis' => old('gst_basis', $isEdit ? ($invoice->gst_basis ?: 'inclusive') : 'inclusive'),
        'issueDate' => $issueDate,
        'dueDate' => $defaultDueDate,
        'invoiceNumber' => $suggestedInvoiceNumber,
        'suggestedInvoiceNumber' => $suggestedInvoiceNumber,
        'suggestNumberUrl' => $suggestNumberUrl,
        'lines' => is_array($oldLines)
            ? array_values(array_map(
                fn ($line) => $collapseLine(is_array($line) ? $line : []),
                $oldLines
            ))
            : $defaultLines,
        'lockInvoiceNumber' => (bool) ($lockInvoiceNumber ?? false),
        'lockDueDate' => (bool) ($lockDueDate ?? false),
    ];
    $fieldClass = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-2xs transition-colors placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-indigo-400';
@endphp

<div class="py-6 sm:py-8 w-full px-4 sm:px-6 lg:px-8"
     style="max-width: 1400px; margin-left: auto; margin-right: auto;"
     x-data="invoiceForm(@js($formConfig))"
     x-init="init()">
    @if (session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800 shadow-2xs dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200">
            <x-lucide-alert-circle class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" />
            <div class="flex-1 font-medium">{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800 shadow-2xs dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200">
            <x-lucide-alert-triangle class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" />
            <div class="flex-1">
                <p class="font-semibold">Please correct the following errors:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs text-rose-700 dark:text-rose-300">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @unless ($isEdit)
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3.5 text-sm text-indigo-900 shadow-2xs dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200">
            <x-lucide-info class="h-5 w-5 shrink-0 text-indigo-600 dark:text-indigo-400 mt-0.5" />
            <div class="text-xs leading-relaxed">
                For recurring monthly rent, prefer
                <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}" class="font-semibold underline hover:no-underline">Rent invoices</a>
                so amounts and lease links are generated automatically. Use this form for one-off invoices.
                Rent invoices follow the lease GST setting (10% inclusive when GST applies, or GST not applicable).
            </div>
        </div>
    @endunless

    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- Hidden defaults (kept for backend compatibility) --}}
        <input type="hidden" name="invoice_number" value="{{ $suggestedInvoiceNumber }}" x-model="invoiceNumber">
        <input type="hidden" name="currency" value="AUD">
        <input type="hidden" name="reference" value="{{ $formConfig['reference'] }}" x-model="reference">
        <input type="hidden" name="notes" value="{{ $formConfig['notes'] }}" x-model="notes">
        <input type="hidden" name="asset_id" value="{{ $formConfig['assetId'] }}" :value="assetId">
        <input type="hidden" name="lease_id" value="{{ $formConfig['leaseId'] }}" :value="leaseId">
        <input type="hidden" name="gst_percent" value="{{ in_array(($formConfig['gstBasis'] ?? 'inclusive'), ['none', 'manual'], true) ? 0 : 10 }}" :value="gstPercent">
        <input type="hidden" name="gst_basis" value="{{ $formConfig['gstBasis'] ?? 'inclusive' }}" :value="gstBasis">
        <input type="hidden" name="expected_attachment_count" value="0" data-expected-attachment-count>

        {{-- Main Invoice Document Sheet --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            {{-- Document Header & Metadata Panel --}}
            <div class="border-b border-gray-100 bg-gradient-to-br from-slate-50/70 via-white to-indigo-50/30 px-6 py-6 sm:px-8 dark:border-gray-800 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950/20">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-2xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <x-lucide-file-text class="h-3.5 w-3.5 text-indigo-500" />
                            Invoice # <span class="font-mono text-gray-900 dark:text-white" x-text="invoiceNumber || '{{ $suggestedInvoiceNumber }}'"></span>
                        </span>
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">
                            Draft · Editable
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-500 shadow-2xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <x-lucide-coins class="h-3.5 w-3.5 text-amber-500" />
                            Currency: AUD ($)
                        </span>
                    </div>
                </div>

                {{-- Two-column configuration grid --}}
                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                    {{-- Customer & Lease (7 columns) --}}
                    <div class="space-y-4 lg:col-span-7">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2 dark:border-gray-800">
                            <x-lucide-user-check class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Customer &amp; Lease</h3>
                            <span class="text-xs text-gray-400 font-normal">(Optional lease auto-fills tenant details)</span>
                        </div>

                        <div>
                            <label class="mb-1.5 flex items-center justify-between text-xs font-medium text-gray-700 dark:text-gray-300">
                                <span>Lease / tenant</span>
                                <span class="text-[11px] text-gray-400 font-normal">Optional</span>
                            </label>
                            <select x-model="leaseId" @change="onLeaseChange()" class="{{ $fieldClass }}">
                                <option value="">— Optional —</option>
                                <template x-for="lease in allLeases" :key="lease.id">
                                    <option :value="String(lease.id)"
                                            :selected="String(lease.id) === String(leaseId)"
                                            x-text="lease.fullLabel"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                Customer <span class="text-rose-500">*</span>
                            </label>
                            <input name="customer_name" x-model="customerName"
                                   value="{{ old('customer_name', $isEdit ? $invoice->customer_name : '') }}" required
                                   class="{{ $fieldClass }}" placeholder="Customer / bill-to name" />
                        </div>

                        <div x-show="formattedCustomerAbn" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-200">Customer ABN</span>
                                <span class="font-mono text-xs font-bold text-indigo-950 dark:text-indigo-100" x-text="formattedCustomerAbn"></span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">From the selected tenant record.</p>
                        </div>
                    </div>

                    {{-- Invoice Dates & GST Treatment (5 columns) --}}
                    <div class="space-y-4 lg:col-span-5">
                        <div class="flex items-center gap-2 border-b border-gray-100 pb-2 dark:border-gray-800">
                            <x-lucide-calendar class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Invoice Schedule &amp; GST</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Issue date <span class="text-rose-500">*</span>
                                </label>
                                <x-date-input name="issue_date" value="{{ $issueDate }}" data-invoice-issue-date
                                              class="{{ $fieldClass }}" required />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Due date
                                </label>
                                <x-date-input name="due_date" value="{{ $defaultDueDate }}" data-invoice-due-date
                                              class="{{ $fieldClass }}" />
                            </div>
                        </div>

                        <div class="pt-1">
                            <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                                GST treatment
                            </label>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <label class="relative flex cursor-pointer items-start gap-2.5 rounded-xl border p-2.5 transition-all duration-150"
                                       :class="gstMode === 'inclusive' ? 'border-indigo-600 bg-indigo-50/70 ring-1 ring-indigo-600/30 dark:bg-indigo-950/40 dark:border-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800/60 dark:hover:bg-gray-800'">
                                    <input type="radio" name="gst_mode_ui" value="inclusive" x-model="gstMode" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700" />
                                    <div class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-gray-900 dark:text-white">Yes — 10% inclusive</span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">Prices include GST</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer items-start gap-2.5 rounded-xl border p-2.5 transition-all duration-150"
                                       :class="gstMode === 'exclusive' ? 'border-indigo-600 bg-indigo-50/70 ring-1 ring-indigo-600/30 dark:bg-indigo-950/40 dark:border-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800/60 dark:hover:bg-gray-800'">
                                    <input type="radio" name="gst_mode_ui" value="exclusive" x-model="gstMode" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700" />
                                    <div class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-gray-900 dark:text-white">Yes — 10% exclusive</span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">+10% added on top</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer items-start gap-2.5 rounded-xl border p-2.5 transition-all duration-150"
                                       :class="gstMode === 'manual' ? 'border-indigo-600 bg-indigo-50/70 ring-1 ring-indigo-600/30 dark:bg-indigo-950/40 dark:border-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800/60 dark:hover:bg-gray-800'">
                                    <input type="radio" name="gst_mode_ui" value="manual" x-model="gstMode" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700" />
                                    <div class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-gray-900 dark:text-white">Mixed rates — tax per line</span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">Tax rate per item</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer items-start gap-2.5 rounded-xl border p-2.5 transition-all duration-150"
                                       :class="gstMode === 'none' ? 'border-indigo-600 bg-indigo-50/70 ring-1 ring-indigo-600/30 dark:bg-indigo-950/40 dark:border-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800/60 dark:hover:bg-gray-800'">
                                    <input type="radio" name="gst_mode_ui" value="none" x-model="gstMode" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700" />
                                    <div class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold text-gray-900 dark:text-white">No — GST not applicable</span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400">GST exempt</span>
                                    </div>
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                                <x-lucide-info class="h-3.5 w-3.5 shrink-0 text-indigo-500" />
                                <span x-text="gstHint"></span>
                            </p>
                            @if ($legacyMixedGst)
                                <p class="mt-2 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-lg p-2.5">
                                    This draft stored one GST total and no rate on each line, so the Profit &amp; Loss report could not split it. Choose GST 10% or GST Free on every line before you save.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Line Items Section --}}
            <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2">
                        <x-lucide-list class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Line items</h3>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <span x-text="lines.length"></span> <span x-text="lines.length === 1 ? 'item' : 'items'"></span>
                        </span>
                    </div>
                    <button type="button" @click="addLine()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 hover:text-indigo-600 hover:border-indigo-300 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <x-lucide-plus class="h-3.5 w-3.5" aria-hidden="true" />
                        Add line
                    </button>
                </div>

                {{-- Table Header (Desktop) --}}
                <div class="hidden md:grid md:grid-cols-12 gap-3 mb-2 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 bg-gray-50/80 rounded-lg dark:bg-gray-800/50 dark:text-gray-400 border border-gray-100 dark:border-gray-800">
                    <div :class="gstMode === 'manual' ? 'md:col-span-3' : 'md:col-span-5'">Description</div>
                    <div class="md:col-span-2" x-text="unitPriceLabel"></div>
                    <div class="md:col-span-2" x-show="gstMode === 'manual'" x-cloak>Tax rate</div>
                    <div class="md:col-span-1 text-right" x-show="gstMode === 'manual'" x-cloak>Tax amount</div>
                    <div :class="gstMode === 'manual' ? 'md:col-span-3' : 'md:col-span-4'">Account</div>
                    <div class="md:col-span-1"></div>
                </div>

                {{-- Rows --}}
                <div class="space-y-3">
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="grid grid-cols-1 items-start gap-3 rounded-xl border border-gray-200/70 bg-gray-50/40 p-4 md:grid-cols-12 md:border-0 md:bg-transparent md:p-0 dark:border-gray-800 dark:bg-gray-800/30 md:dark:bg-transparent hover:bg-indigo-50/20 md:hover:bg-transparent transition-colors">
                            {{-- Description --}}
                            <div :class="gstMode === 'manual' ? 'md:col-span-3' : 'md:col-span-5'">
                                <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Description</label>
                                <input :name="'lines[' + index + '][description]'" x-model="line.description" required
                                       class="{{ $fieldClass }}" placeholder="Item description (e.g. Monthly Rent)" />
                                <input type="hidden" :name="'lines[' + index + '][quantity]'" value="1">
                            </div>

                            {{-- Unit Price --}}
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden" x-text="unitPriceLabel"></label>
                                <div class="relative rounded-lg shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-gray-400">$</div>
                                    <input type="number" step="0.01" :name="'lines[' + index + '][unit_price]'" x-model.number="line.unit_price" required
                                           class="{{ $fieldClass }} pl-7 font-mono text-right" placeholder="0.00" />
                                </div>
                            </div>

                            {{-- Tax Rate (manual only) --}}
                            <div class="md:col-span-2" x-show="gstMode === 'manual'" x-cloak>
                                <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Tax rate</label>
                                <select :name="'lines[' + index + '][tax_code]'" x-model="line.tax_code"
                                        :disabled="gstMode !== 'manual'" :required="gstMode === 'manual'"
                                        class="{{ $fieldClass }}">
                                    <option value="">Select tax</option>
                                    <option value="gst">GST 10%</option>
                                    <option value="free">GST Free</option>
                                </select>
                            </div>

                            {{-- Tax Amount (manual only) --}}
                            <div class="md:col-span-1" x-show="gstMode === 'manual'" x-cloak>
                                <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Tax amount</label>
                                <div class="flex items-center md:justify-end py-2 px-1">
                                    <span class="font-mono text-sm font-semibold tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(lineAmounts(line).gst)"></span>
                                </div>
                            </div>

                            {{-- Account --}}
                            <div :class="gstMode === 'manual' ? 'md:col-span-3' : 'md:col-span-4'">
                                <label class="mb-1 block text-xs font-medium text-gray-500 md:hidden">Account</label>
                                <input type="hidden" :name="'lines[' + index + '][account_code]'" :value="line.account_code">
                                <select x-model="line.account_code" class="{{ $fieldClass }}">
                                    @foreach ($lineAccounts as $account)
                                        <option value="{{ $account->account_code }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Action --}}
                            <div class="md:col-span-1 flex items-center md:justify-end">
                                <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors dark:text-gray-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/40"
                                        title="Remove line">
                                    <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                                    <span class="sr-only">Remove</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Bottom Section: Attachments & Totals Summary --}}
            <div class="bg-gray-50/40 p-6 sm:p-8 dark:bg-gray-900/40">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                    {{-- Attachments (7 columns) --}}
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-2xs dark:border-gray-800 dark:bg-gray-850">
                            <div class="flex items-center gap-2 mb-3">
                                <x-lucide-paperclip class="h-4 w-4 text-indigo-500" />
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Attachments</h3>
                            </div>
                            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                                Optional PDFs or image receipts stored with this invoice in Documents.
                            </p>
                            @if ($isEdit)
                                @include('invoices.partials.attachment-display', [
                                    'invoice' => $invoice,
                                    'businessEntity' => $businessEntity,
                                    'variant' => 'form',
                                ])
                            @endif
                            <div class="mt-3">
                                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ $isEdit ? 'Attach files or add more' : 'Attach files' }}
                                </p>
                                @include('invoices.partials.attachment-dropzone')
                                @error('attachments')
                                    <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                                @error('attachments.*')
                                    <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Summary Card (5 columns) --}}
                    <div class="lg:col-span-5">
                        <div class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-xs dark:border-indigo-900/50 dark:bg-gray-850">
                            <div class="border-b border-indigo-50 bg-indigo-50/60 px-5 py-3.5 dark:border-indigo-900/40 dark:bg-indigo-950/40">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-lucide-calculator class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-200">Invoice Summary</span>
                                    </div>
                                    <span class="rounded-md bg-indigo-100/70 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">AUD</span>
                                </div>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300">
                                    <span x-text="gstApplicable ? 'Subtotal (ex GST)' : 'Subtotal'"></span>
                                    <span class="font-mono font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(totals.subtotal)"></span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300">
                                    <span>GST</span>
                                    <span class="font-mono font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(totals.gst)"></span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3.5 flex justify-between items-baseline">
                                    <div>
                                        <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                                        <p class="text-[11px] text-gray-400" x-text="gstApplicable ? 'Includes applicable GST' : 'No GST charged'"></p>
                                    </div>
                                    <span class="text-2xl font-bold tracking-tight tabular-nums text-indigo-600 dark:text-indigo-400 font-mono" x-text="formatMoney(totals.total)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Footer Bar --}}
            <div class="border-t border-gray-200/80 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900 sm:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                    <x-lucide-shield-check class="h-4 w-4 text-emerald-500" />
                    <span>{{ $isEdit ? 'Draft invoices can be edited freely. Posted invoices stay read-only.' : 'Draft will be created and can be posted anytime.' }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ $cancelUrl }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-2xs hover:bg-gray-50 hover:text-gray-900 transition-colors dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit" name="save_and_post" value="0"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-600 shadow-2xs hover:bg-indigo-50 hover:border-indigo-700 transition-colors dark:border-indigo-500 dark:bg-gray-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40">
                        <x-lucide-save class="h-4 w-4" aria-hidden="true" />
                        Save draft
                    </button>
                    <button type="submit" name="save_and_post" value="1"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 transition-colors focus:ring-2 focus:ring-emerald-500/20">
                        <x-lucide-book-check class="h-4 w-4" aria-hidden="true" />
                        Save &amp; post
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function invoiceForm(config) {
        return {
            assets: config.assets || [],
            lineAccounts: config.lineAccounts || [],
            defaultAccountCode: config.defaultAccountCode || '',
            assetId: config.assetId ? String(config.assetId) : '',
            leaseId: config.leaseId ? String(config.leaseId) : '',
            customerName: config.customerName || '',
            customerAbn: config.customerAbn || '',
            reference: config.reference || '',
            notes: config.notes || '',
            gstMode: config.gstBasis === 'none'
                ? 'none'
                : (config.gstBasis === 'manual' ? 'manual' : (config.gstBasis === 'exclusive' ? 'exclusive' : 'inclusive')),
            issueDate: config.issueDate || '',
            dueDate: config.dueDate || '',
            invoiceNumber: config.invoiceNumber || '',
            suggestedInvoiceNumber: config.suggestedInvoiceNumber || '',
            suggestNumberUrl: config.suggestNumberUrl || '',
            invoiceNumberTouched: Boolean(config.lockInvoiceNumber),
            dueDateTouched: Boolean(config.lockDueDate),
            dateHooksBound: false,
            lines: (config.lines || []).map((line) => ({
                description: line.description || '',
                quantity: 1,
                unit_price: Number(line.unit_price ?? 0),
                account_code: line.account_code || config.defaultAccountCode || '',
                tax_code: line.tax_code === 'free' || line.tax_code === 'gst' ? line.tax_code : '',
            })),
            get allLeases() {
                return this.assets.flatMap((asset) =>
                    (asset.leases || []).map((lease) => ({
                        ...lease,
                        asset_id: asset.id,
                        fullLabel: `${asset.name} — ${lease.label}`,
                    }))
                );
            },
            get resolvedCustomerAbn() {
                if (this.leaseId) {
                    const lease = this.allLeases.find((item) => String(item.id) === String(this.leaseId));
                    if (lease?.tenant_abn) {
                        return String(lease.tenant_abn);
                    }
                }

                return this.customerAbn ? String(this.customerAbn) : '';
            },
            get formattedCustomerAbn() {
                return this.formatAbn(this.resolvedCustomerAbn);
            },
            formatAbn(value) {
                const digits = String(value || '').replace(/\D/g, '');
                if (digits.length !== 11) {
                    return digits !== '' ? digits : '';
                }

                return `${digits.slice(0, 2)} ${digits.slice(2, 5)} ${digits.slice(5, 8)} ${digits.slice(8, 11)}`;
            },
            get gstApplicable() {
                return this.gstMode !== 'none';
            },
            get gstBasis() {
                return this.gstMode;
            },
            get gstPercent() {
                return this.gstMode === 'inclusive' || this.gstMode === 'exclusive' ? 10 : 0;
            },
            get gstHint() {
                if (this.gstMode === 'none') {
                    return 'GST not charged on this invoice';
                }
                if (this.gstMode === 'manual') {
                    return 'Mixed rates: line prices are cash totals. Choose GST 10% or GST Free on each line.';
                }
                if (this.gstMode === 'exclusive') {
                    return '10% exclusive (GST added on top) — kept from this draft';
                }
                return '10% inclusive when GST applies';
            },
            get unitPriceLabel() {
                if (this.gstMode === 'none') {
                    return 'Unit price';
                }
                if (this.gstMode === 'exclusive') {
                    return 'Unit price (ex GST)';
                }
                return 'Unit price (inc GST)';
            },
            get gstRate() {
                return (this.gstMode === 'inclusive' || this.gstMode === 'exclusive') ? 0.1 : 0;
            },
            get totals() {
                const carry = this.lines.reduce((acc, line) => {
                    const amounts = this.lineAmounts(line);
                    acc.subtotal += amounts.net;
                    acc.gst += amounts.gst;
                    acc.total += amounts.lineTotal;
                    return acc;
                }, { subtotal: 0, gst: 0, total: 0 });

                return carry;
            },
            lineAmounts(line) {
                const qty = 1;
                const price = Number(line.unit_price) || 0;
                if (this.gstMode === 'manual') {
                    const lineTotal = Math.round(qty * price * 100) / 100;
                    if (line.tax_code !== 'gst') {
                        return { net: lineTotal, gst: 0, lineTotal };
                    }
                    const net = Math.round((lineTotal / 1.1) * 100) / 100;
                    const gst = Math.round((lineTotal - net) * 100) / 100;
                    return { net, gst, lineTotal };
                }
                const rate = this.gstRate;
                if (rate <= 0) {
                    const total = Math.round(qty * price * 100) / 100;
                    return { net: total, gst: 0, lineTotal: total };
                }
                if (this.gstMode === 'exclusive') {
                    const net = Math.round(qty * price * 100) / 100;
                    const gst = Math.round(net * rate * 100) / 100;
                    const lineTotal = Math.round((net + gst) * 100) / 100;
                    return { net, gst, lineTotal };
                }
                const lineTotal = Math.round(qty * price * 100) / 100;
                const net = Math.round((lineTotal / (1 + rate)) * 100) / 100;
                const gst = Math.round((lineTotal - net) * 100) / 100;
                return { net, gst, lineTotal };
            },
            formatMoney(value) {
                const amount = Number(value) || 0;
                const formatted = Math.abs(amount).toFixed(2);
                return (amount < 0 ? '-$' : '$') + formatted;
            },
            addLine() {
                this.lines.push({
                    description: '',
                    quantity: 1,
                    unit_price: 0,
                    account_code: this.defaultAccountCode || (this.lineAccounts[0]?.code ?? ''),
                    tax_code: 'gst',
                });
            },
            removeLine(index) {
                if (this.lines.length > 1) {
                    this.lines.splice(index, 1);
                }
            },
            syncLeaseAsset() {
                if (!this.leaseId) {
                    this.assetId = '';
                    return;
                }
                const lease = this.allLeases.find((item) => String(item.id) === String(this.leaseId));
                if (lease) {
                    this.assetId = String(lease.asset_id);
                }
            },
            onLeaseChange() {
                this.syncLeaseAsset();
                this.applyLeaseDefaults();
            },
            applyLeaseDefaults() {
                const lease = this.allLeases.find((item) => String(item.id) === String(this.leaseId));
                if (!lease) {
                    return;
                }
                if (lease.tenant_name) {
                    this.customerName = lease.tenant_name;
                }
                if (lease.tenant_abn) {
                    this.customerAbn = String(lease.tenant_abn);
                }
                const assetName = lease.asset_name || '';
                if (assetName) {
                    this.reference = 'Invoice for ' + assetName + (lease.tenant_name ? ' — ' + lease.tenant_name : '');
                }
                if (Object.prototype.hasOwnProperty.call(lease, 'gst_applicable')) {
                    if (!lease.gst_applicable) {
                        this.gstMode = 'none';
                    } else if (this.gstMode === 'none') {
                        this.gstMode = 'inclusive';
                    }
                    // Do not force inclusive over exclusive/manual drafts.
                }
            },
            init() {
                this.syncLeaseAsset();
                // Re-apply after x-for options render so the selected lease sticks.
                const selectedLeaseId = this.leaseId;
                this.$nextTick(() => {
                    if (selectedLeaseId) {
                        this.leaseId = selectedLeaseId;
                        this.syncLeaseAsset();
                    }
                });
                this.initFlatpickrHooks();
            },
            addDaysYmd(ymd, days) {
                const parts = String(ymd).split('-').map(Number);
                if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
                    return '';
                }
                const next = new Date(parts[0], parts[1] - 1, parts[2]);
                next.setDate(next.getDate() + days);
                const yyyy = next.getFullYear();
                const mm = String(next.getMonth() + 1).padStart(2, '0');
                const dd = String(next.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            },
            syncDueDateFromIssue() {
                if (!this.issueDate || this.dueDateTouched) {
                    return;
                }
                this.dueDate = this.addDaysYmd(this.issueDate, 30);
                const dueSource = this.$root.querySelector('[data-invoice-due-date], input[name="due_date"]');
                if (dueSource && typeof window.setDateInputValue === 'function') {
                    window.setDateInputValue(dueSource, this.dueDate);
                } else if (dueSource) {
                    dueSource.value = this.dueDate;
                }
            },
            async refreshSuggestedNumber() {
                if (!this.issueDate || !this.suggestNumberUrl || this.invoiceNumberTouched) {
                    return;
                }
                try {
                    const url = new URL(this.suggestNumberUrl, window.location.origin);
                    url.searchParams.set('issue_date', this.issueDate);
                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        return;
                    }
                    const data = await response.json();
                    if (data.invoice_number) {
                        this.invoiceNumber = data.invoice_number;
                        this.suggestedInvoiceNumber = data.invoice_number;
                    }
                } catch (e) {
                    // ignore network errors; server will still validate uniqueness
                }
            },
            onIssueDateChanged(value) {
                this.issueDate = value || '';
                this.syncDueDateFromIssue();
                this.refreshSuggestedNumber();
            },
            onDueDateChanged(value) {
                const next = value || '';
                if (this.dueDate && next && next !== this.dueDate) {
                    this.dueDateTouched = true;
                }
                this.dueDate = next;
            },
            bindDateInput(selector, onChange) {
                const el = this.$root.querySelector(selector);
                if (!el) {
                    return false;
                }
                const source = (typeof window.queryDateInput === 'function')
                    ? (window.queryDateInput(this.$root, selector) || el)
                    : el;

                if (source.dataset.invoiceDateChangeBound !== '1') {
                    source.addEventListener('change', () => {
                        const value = typeof window.getDateInputValue === 'function'
                            ? window.getDateInputValue(source)
                            : source.value;
                        onChange(value);
                    });
                    source.dataset.invoiceDateChangeBound = '1';
                }

                const fp = source._flatpickr;
                if (!fp) {
                    return false;
                }

                if (source.dataset.invoiceDateFpBound !== '1') {
                    const handler = (_dates, dateStr) => onChange(
                        dateStr || (typeof window.getDateInputValue === 'function' ? window.getDateInputValue(source) : source.value)
                    );
                    if (Array.isArray(fp.config.onChange)) {
                        fp.config.onChange.push(handler);
                    } else if (fp.config.onChange) {
                        fp.config.onChange = [fp.config.onChange, handler];
                    } else {
                        fp.config.onChange = [handler];
                    }
                    source.dataset.invoiceDateFpBound = '1';
                }

                source.dataset.invoiceDateBound = '1';
                return true;
            },
            initFlatpickrHooks() {
                if (this.dateHooksBound) {
                    return;
                }
                const tryBind = () => {
                    if (this.dateHooksBound) {
                        return;
                    }
                    const issueReady = this.bindDateInput('[data-invoice-issue-date], input[name="issue_date"]', (v) => this.onIssueDateChanged(v));
                    const dueReady = this.bindDateInput('[data-invoice-due-date], input[name="due_date"]', (v) => this.onDueDateChanged(v));
                    if (issueReady && dueReady) {
                        this.dateHooksBound = true;
                    }
                };
                this.$nextTick(() => {
                    tryBind();
                    setTimeout(tryBind, 150);
                    setTimeout(tryBind, 400);
                });
            },
        };
    }
</script>
