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

        return [
            'description' => $line['description'] ?? '',
            'quantity' => 1,
            // Qty is hidden (always 1); fold existing qty into the unit price so totals stay correct.
            'unit_price' => round($quantity * $unitPrice, 2),
            'account_code' => $line['account_code'] ?? $defaultAccountCode,
        ];
    };
    $defaultLines = $isEdit
        ? $invoice->lines->map(fn ($line) => $collapseLine([
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'account_code' => $line->account_code ?? $defaultAccountCode,
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
        'incomeAccounts' => $incomeAccounts->map(fn ($a) => [
            'code' => $a->account_code,
            'label' => $a->account_code.' — '.$a->account_name,
        ])->values(),
        'defaultAccountCode' => $defaultAccountCode,
        'assetId' => old('asset_id', $isEdit ? $invoice->asset_id : null),
        'leaseId' => old('lease_id', $isEdit ? $invoice->lease_id : null),
        'customerName' => old('customer_name', $isEdit ? $invoice->customer_name : ''),
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
    $fieldClass = 'w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
@endphp

<div class="py-8 w-full px-4 sm:px-6 lg:px-8"
     x-data="invoiceForm(@js($formConfig))"
     x-init="init()">
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @unless ($isEdit)
        <div class="mb-5 rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200">
            For recurring monthly rent, prefer
            <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}" class="font-semibold underline hover:no-underline">Rent invoices</a>
            so amounts and lease links are generated automatically. Use this form for one-off invoices.
            Rent invoices follow the lease GST setting (10% inclusive when GST applies, or GST not applicable).
        </div>
    @endunless

    <form method="POST" action="{{ $formAction }}" class="space-y-5">
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
        <input type="hidden" name="gst_percent" value="{{ ($formConfig['gstBasis'] ?? 'inclusive') === 'none' ? 0 : 10 }}" :value="gstPercent">
        <input type="hidden" name="gst_basis" value="{{ $formConfig['gstBasis'] ?? 'inclusive' }}" :value="gstBasis">

        {{-- Invoice details --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Invoice details</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Dates for this invoice</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Issue date</label>
                    <x-date-input name="issue_date" value="{{ $issueDate }}" data-invoice-issue-date
                                  class="{{ $fieldClass }}" required />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Due date</label>
                    <x-date-input name="due_date" value="{{ $defaultDueDate }}" data-invoice-due-date
                                  class="{{ $fieldClass }}" />
                </div>
            </div>
        </section>

        {{-- Customer & lease --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Customer &amp; lease</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Optional lease fills customer automatically</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Lease / tenant</label>
                    <select x-model="leaseId" @change="onLeaseChange()"
                            class="{{ $fieldClass }}">
                        <option value="">— Optional —</option>
                        <template x-for="lease in allLeases" :key="lease.id">
                            <option :value="String(lease.id)"
                                    :selected="String(lease.id) === String(leaseId)"
                                    x-text="lease.fullLabel"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Customer</label>
                    <input name="customer_name" x-model="customerName" required
                           class="{{ $fieldClass }}" placeholder="Customer / bill-to name" />
                </div>
            </div>
        </section>

        {{-- GST --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">GST</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-text="gstHint"></p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3.5 py-3 text-sm has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50/70 dark:border-gray-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/40">
                        <input type="radio" name="gst_applicable_ui" value="1" x-model="gstApplicableRadio" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="block font-medium text-gray-900 dark:text-white">Yes — GST applies</span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3.5 py-3 text-sm has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50/70 dark:border-gray-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/40">
                        <input type="radio" name="gst_applicable_ui" value="0" x-model="gstApplicableRadio" class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="block font-medium text-gray-900 dark:text-white">No — GST not applicable</span>
                    </label>
                </div>
            </div>
        </section>

        {{-- Line items --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Line items</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="lines.length"></span> <span x-text="lines.length === 1 ? 'line' : 'lines'"></span>
                    </p>
                </div>
                <button type="button" @click="addLine()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-800 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">
                    <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                    Add line
                </button>
            </div>

            <div class="p-5">
                <div class="hidden md:grid md:grid-cols-12 gap-2 mb-2 px-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <div class="md:col-span-5">Description</div>
                    <div class="md:col-span-2" x-text="unitPriceLabel"></div>
                    <div class="md:col-span-4">Income account</div>
                    <div class="md:col-span-1"></div>
                </div>

                <template x-for="(line, index) in lines" :key="index">
                    <div class="mb-3 grid grid-cols-1 items-start gap-2 rounded-lg border border-gray-100 bg-gray-50/50 p-3 md:grid-cols-12 md:border-0 md:bg-transparent md:p-0 dark:border-gray-800 dark:bg-gray-800/30 md:dark:bg-transparent">
                        <div class="md:col-span-5">
                            <label class="mb-1 block text-xs text-gray-500 md:hidden">Description</label>
                            <input :name="'lines[' + index + '][description]'" x-model="line.description" required
                                   class="{{ $fieldClass }}" />
                            <input type="hidden" :name="'lines[' + index + '][quantity]'" value="1">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs text-gray-500 md:hidden" x-text="unitPriceLabel"></label>
                            <input type="number" step="0.01" :name="'lines[' + index + '][unit_price]'" x-model.number="line.unit_price" required
                                   class="{{ $fieldClass }}" />
                        </div>
                        <div class="md:col-span-4">
                            <label class="mb-1 block text-xs text-gray-500 md:hidden">Income account</label>
                            <input type="hidden" :name="'lines[' + index + '][account_code]'" :value="line.account_code">
                            <select x-model="line.account_code"
                                    class="{{ $fieldClass }}">
                                @foreach ($incomeAccounts as $account)
                                    <option value="{{ $account->account_code }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-1 flex md:justify-end">
                            <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30">
                                <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                                <span class="md:hidden">Remove</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- Totals & actions --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="w-full max-w-xs rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Totals</p>
                    <dl class="mt-3 space-y-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between gap-6">
                            <dt x-text="gstApplicable ? 'Subtotal (ex GST)' : 'Subtotal'"></dt>
                            <dd class="font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(totals.subtotal)"></dd>
                        </div>
                        <div class="flex justify-between gap-6">
                            <dt>GST</dt>
                            <dd class="font-medium tabular-nums text-gray-900 dark:text-white" x-text="formatMoney(totals.gst)"></dd>
                        </div>
                        <div class="flex justify-between gap-6 border-t border-indigo-100 pt-2 text-base font-semibold text-gray-900 dark:border-indigo-900/60 dark:text-white">
                            <dt>Total</dt>
                            <dd class="tabular-nums text-indigo-600 dark:text-indigo-300" x-text="formatMoney(totals.total)"></dd>
                        </div>
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $cancelUrl }}"
                       class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit" name="save_and_post" value="0"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                        Save draft
                    </button>
                    <button type="submit" name="save_and_post" value="1"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                        <x-lucide-book-check class="h-4 w-4" aria-hidden="true" />
                        Save &amp; post
                    </button>
                </div>
            </div>
        </section>
    </form>
</div>

<script>
    function invoiceForm(config) {
        return {
            assets: config.assets || [],
            incomeAccounts: config.incomeAccounts || [],
            defaultAccountCode: config.defaultAccountCode || '',
            assetId: config.assetId ? String(config.assetId) : '',
            leaseId: config.leaseId ? String(config.leaseId) : '',
            customerName: config.customerName || '',
            reference: config.reference || '',
            notes: config.notes || '',
            gstApplicableRadio: (config.gstBasis && config.gstBasis !== 'none') ? '1' : '0',
            // Preserve exclusive on existing drafts; new invoices always use inclusive.
            gstBasisWhenApplicable: config.gstBasis === 'exclusive' ? 'exclusive' : 'inclusive',
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
            get gstApplicable() {
                return this.gstApplicableRadio === '1' || this.gstApplicableRadio === 1 || this.gstApplicableRadio === true;
            },
            get gstBasis() {
                return this.gstApplicable ? this.gstBasisWhenApplicable : 'none';
            },
            get gstPercent() {
                return this.gstApplicable ? 10 : 0;
            },
            get gstHint() {
                if (!this.gstApplicable) {
                    return 'GST not charged on this invoice';
                }
                return this.gstBasisWhenApplicable === 'exclusive'
                    ? '10% exclusive (GST added on top) — kept from this draft'
                    : '10% inclusive when GST applies';
            },
            get unitPriceLabel() {
                if (!this.gstApplicable) {
                    return 'Unit price';
                }
                return this.gstBasisWhenApplicable === 'exclusive' ? 'Unit price (ex GST)' : 'Unit price (inc GST)';
            },
            get gstRate() {
                if (!this.gstApplicable) {
                    return 0;
                }
                return 0.1;
            },
            get totals() {
                return this.lines.reduce((carry, line) => {
                    const amounts = this.lineAmounts(line);
                    carry.subtotal += amounts.net;
                    carry.gst += amounts.gst;
                    carry.total += amounts.lineTotal;
                    return carry;
                }, { subtotal: 0, gst: 0, total: 0 });
            },
            lineAmounts(line) {
                const qty = 1;
                const price = Number(line.unit_price) || 0;
                const rate = this.gstRate;
                if (rate <= 0) {
                    const total = Math.round(qty * price * 100) / 100;
                    return { net: total, gst: 0, lineTotal: total };
                }
                if (this.gstBasisWhenApplicable === 'exclusive') {
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
                    account_code: this.defaultAccountCode || (this.incomeAccounts[0]?.code ?? ''),
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
                const assetName = lease.asset_name || '';
                if (assetName) {
                    this.reference = 'Invoice for ' + assetName + (lease.tenant_name ? ' — ' + lease.tenant_name : '');
                }
                if (Object.prototype.hasOwnProperty.call(lease, 'gst_applicable')) {
                    this.gstApplicableRadio = lease.gst_applicable ? '1' : '0';
                    // Do not force inclusive here — that would wipe exclusive on existing drafts
                    // when the lease dropdown is changed or re-selected.
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
