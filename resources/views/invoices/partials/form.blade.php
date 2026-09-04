@php
    $isEdit = isset($invoice) && $invoice;
    $formAction = $isEdit
        ? route('business-entities.invoices.update', [$businessEntity, $invoice])
        : route('business-entities.invoices.store', $businessEntity);
    $suggestNumberUrl = route('business-entities.invoices.suggest-number', $businessEntity);
    $cancelUrl = route('business-entities.invoices.index', $businessEntity);
    $defaultLines = $isEdit
        ? $invoice->lines->map(fn ($line) => [
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'account_code' => $line->account_code ?? $defaultAccountCode,
        ])->values()->all()
        : [[
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'account_code' => $defaultAccountCode,
        ]];
    $formConfig = [
        'assets' => $assetsForForm,
        'tenants' => $tenantsForForm ?? [],
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
        'gstPercent' => (float) old('gst_percent', $isEdit ? ($defaultGstPercent ?? 10) : 10),
        'issueDate' => $issueDate,
        'dueDate' => $defaultDueDate,
        'invoiceNumber' => $suggestedInvoiceNumber,
        'suggestedInvoiceNumber' => $suggestedInvoiceNumber,
        'suggestNumberUrl' => $suggestNumberUrl,
        'lines' => old('lines', $defaultLines),
        'lockInvoiceNumber' => (bool) ($lockInvoiceNumber ?? false),
        'lockDueDate' => (bool) ($lockDueDate ?? false),
    ];
@endphp

<div class="py-8 w-full px-4 sm:px-6 lg:px-8"
     x-data="invoiceForm(@js($formConfig))"
     x-init="initFlatpickrHooks()">
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
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            For recurring monthly rent, prefer
            <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}" class="text-indigo-600 dark:text-indigo-400 underline">Rent invoices</a>
            so amounts and lease links are generated automatically. Use this form for one-off invoices.
            Rent invoices follow the lease GST setting (10% inclusive when GST applies, or GST not applicable).
        </p>
    @endunless

    <div class="mb-3">
        <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
            <input type="checkbox" value="1" @checked(!empty($includeEnded))
                   onchange="const u = new URL(window.location.href); if (this.checked) { u.searchParams.set('include_ended', '1'); } else { u.searchParams.delete('include_ended'); } window.location = u.toString();"
                   class="rounded-sm border-gray-300" />
            Include ended leases in the lease picker
        </label>
    </div>

    <form method="POST" action="{{ $formAction }}"
          class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice number</label>
                <input name="invoice_number" x-model="invoiceNumber" required
                       @input="invoiceNumberTouched = true"
                       class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                <p class="mt-1 text-xs text-gray-500">Includes entity id {{ $businessEntity->id }} (INV{{ $businessEntity->id }}-YYYYMM###).</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Issue date</label>
                <x-date-input name="issue_date" value="{{ $issueDate }}" data-invoice-issue-date
                              class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded-sm" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due date</label>
                <x-date-input name="due_date" value="{{ $defaultDueDate }}" data-invoice-due-date
                              class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded-sm" />
                <p class="mt-1 text-xs text-gray-500">Defaults to issue date + 30 days (until you change it).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asset</label>
                <select name="asset_id" x-model="assetId" @change="onAssetChange()"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm">
                    <option value="">— Optional —</option>
                    @foreach ($assetsForForm as $asset)
                        <option value="{{ $asset['id'] }}">{{ $asset['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lease / tenant</label>
                <input type="hidden" name="lease_id" :value="leaseId">
                <select x-model="leaseId" @change="onLeaseChange()" :disabled="!assetId || leasesForAsset.length === 0"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm disabled:opacity-60">
                    <option value="">— Optional —</option>
                    <template x-for="lease in leasesForAsset" :key="lease.id">
                        <option :value="String(lease.id)" x-text="lease.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer</label>
                @if (($tenantsForForm ?? collect())->isNotEmpty())
                    <select @change="onTenantPick($event.target.value)"
                            class="mb-2 w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm text-sm">
                        <option value="">— Pick from tenants (optional) —</option>
                        @foreach ($tenantsForForm as $tenant)
                            <option value="{{ $tenant['name'] }}">{{ $tenant['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                <input name="customer_name" x-model="customerName" required
                       class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
                <input name="reference" x-model="reference"
                       class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                <input name="currency" value="AUD" readonly
                       class="w-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
            </div>
            <input type="hidden" name="gst_percent" :value="gstApplicable ? gstPercent : 0">
            <div x-show="gstApplicable" x-cloak>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GST %</label>
                <input type="number" x-model.number="gstPercent" min="0" max="100" step="0.01"
                       class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
            </div>

            <div class="md:col-span-3">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST applicable</span>
                <input type="hidden" name="gst_basis" :value="gstApplicable ? gstBasis : 'none'">
                <div class="flex flex-wrap gap-3 mb-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="radio" name="gst_applicable_ui" value="1" x-model="gstApplicableRadio" class="rounded-sm border-gray-300" />
                        Yes — GST applies
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="radio" name="gst_applicable_ui" value="0" x-model="gstApplicableRadio" class="rounded-sm border-gray-300" />
                        No — GST not applicable
                    </label>
                </div>
                <div class="flex flex-wrap gap-3" x-show="gstApplicable" x-cloak>
                    <span class="block w-full text-sm font-medium text-gray-700 dark:text-gray-300">GST basis</span>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="radio" name="gst_basis_ui" value="inclusive" x-model="gstBasis" class="rounded-sm border-gray-300" />
                        Inclusive (unit price includes GST — same as taxable rent invoices)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="radio" name="gst_basis_ui" value="exclusive" x-model="gstBasis" class="rounded-sm border-gray-300" />
                        Exclusive (GST added on top)
                    </label>
                </div>
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <textarea name="notes" x-model="notes" rows="3"
                          class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm"></textarea>
            </div>
        </div>

        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-white">Lines</h3>
                <button type="button" @click="addLine()"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-100">
                    Add line
                </button>
            </div>

            <div class="hidden md:grid md:grid-cols-12 gap-2 mb-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                <div class="md:col-span-4">Description</div>
                <div class="md:col-span-1">Qty</div>
                <div class="md:col-span-2" x-text="unitPriceLabel"></div>
                <div class="md:col-span-3">Income account</div>
                <div class="md:col-span-1 text-right">Line total</div>
                <div class="md:col-span-1"></div>
            </div>

            <template x-for="(line, index) in lines" :key="index">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 mb-3 items-start">
                    <div class="md:col-span-4">
                        <label class="md:hidden text-xs text-gray-500">Description</label>
                        <input :name="'lines[' + index + '][description]'" x-model="line.description" required
                               class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                    </div>
                    <div class="md:col-span-1">
                        <label class="md:hidden text-xs text-gray-500">Qty</label>
                        <input type="number" step="0.0001" min="0.0001" :name="'lines[' + index + '][quantity]'" x-model.number="line.quantity" required
                               class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="md:hidden text-xs text-gray-500" x-text="unitPriceLabel"></label>
                        <input type="number" step="0.01" min="0" :name="'lines[' + index + '][unit_price]'" x-model.number="line.unit_price" required
                               class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="md:hidden text-xs text-gray-500">Income account</label>
                        <input type="hidden" :name="'lines[' + index + '][account_code]'" :value="line.account_code">
                        <select x-model="line.account_code"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm">
                            @foreach ($incomeAccounts as $account)
                                <option value="{{ $account->account_code }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1 text-right pt-2 text-sm font-medium text-gray-900 dark:text-gray-100" x-text="formatMoney(lineTotal(line))"></div>
                    <div class="md:col-span-1 flex md:justify-end">
                        <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 px-2 py-2">
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="p-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                <div class="flex justify-between gap-8"><span x-text="gstApplicable ? 'Subtotal (ex GST)' : 'Subtotal'"></span><span class="font-medium" x-text="formatMoney(totals.subtotal)"></span></div>
                <div class="flex justify-between gap-8"><span>GST</span><span class="font-medium" x-text="formatMoney(totals.gst)"></span></div>
                <div class="flex justify-between gap-8 text-base font-semibold text-gray-900 dark:text-white pt-1 border-t border-gray-200 dark:border-gray-700">
                    <span>Total</span><span x-text="formatMoney(totals.total)"></span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $cancelUrl }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    Cancel
                </a>
                <button type="submit" name="save_and_post" value="0"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Save draft
                </button>
                <button type="submit" name="save_and_post" value="1"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Save &amp; post
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function invoiceForm(config) {
        return {
            assets: config.assets || [],
            tenants: config.tenants || [],
            incomeAccounts: config.incomeAccounts || [],
            defaultAccountCode: config.defaultAccountCode || '',
            assetId: config.assetId ? String(config.assetId) : '',
            leaseId: config.leaseId ? String(config.leaseId) : '',
            customerName: config.customerName || '',
            reference: config.reference || '',
            notes: config.notes || '',
            gstApplicableRadio: (config.gstBasis && config.gstBasis !== 'none') ? '1' : '0',
            gstBasis: (config.gstBasis && config.gstBasis !== 'none') ? config.gstBasis : 'inclusive',
            gstPercent: Number(config.gstPercent ?? 10),
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
                quantity: Number(line.quantity ?? 1),
                unit_price: Number(line.unit_price ?? 0),
                account_code: line.account_code || config.defaultAccountCode || '',
            })),
            get leasesForAsset() {
                if (!this.assetId) {
                    return [];
                }
                const asset = this.assets.find((item) => String(item.id) === String(this.assetId));
                return asset ? asset.leases : [];
            },
            get gstApplicable() {
                return this.gstApplicableRadio === '1' || this.gstApplicableRadio === 1 || this.gstApplicableRadio === true;
            },
            get unitPriceLabel() {
                if (!this.gstApplicable) {
                    return 'Unit price';
                }
                return this.gstBasis === 'inclusive' ? 'Unit price (inc GST)' : 'Unit price (ex GST)';
            },
            get gstRate() {
                if (!this.gstApplicable) {
                    return 0;
                }
                const percent = Number(this.gstPercent);
                if (Number.isNaN(percent) || percent <= 0) {
                    return 0;
                }
                return percent / 100;
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
                const qty = Number(line.quantity) || 0;
                const price = Number(line.unit_price) || 0;
                const rate = this.gstRate;
                if (rate <= 0) {
                    const total = Math.round(qty * price * 100) / 100;
                    return { net: total, gst: 0, lineTotal: total };
                }
                if (this.gstBasis === 'inclusive') {
                    const lineTotal = Math.round(qty * price * 100) / 100;
                    const net = Math.round((lineTotal / (1 + rate)) * 100) / 100;
                    const gst = Math.round((lineTotal - net) * 100) / 100;
                    return { net, gst, lineTotal };
                }
                const net = Math.round(qty * price * 100) / 100;
                const gst = Math.round(net * rate * 100) / 100;
                const lineTotal = Math.round((net + gst) * 100) / 100;
                return { net, gst, lineTotal };
            },
            lineTotal(line) {
                return this.lineAmounts(line).lineTotal;
            },
            formatMoney(value) {
                return '$' + (Number(value) || 0).toFixed(2);
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
            onAssetChange() {
                this.leaseId = '';
                this.applyLeaseDefaults();
            },
            onLeaseChange() {
                this.applyLeaseDefaults();
            },
            onTenantPick(name) {
                if (name) {
                    this.customerName = name;
                }
            },
            applyLeaseDefaults() {
                const lease = this.leasesForAsset.find((item) => String(item.id) === String(this.leaseId));
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
                    if (lease.gst_applicable && this.gstBasis === 'none') {
                        this.gstBasis = 'inclusive';
                    }
                    if (!lease.gst_applicable) {
                        this.gstPercent = 0;
                    } else if (!this.gstPercent) {
                        this.gstPercent = 10;
                    }
                }
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
                    // ignore network errors; user can edit number manually
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
                this.$watch('gstApplicableRadio', (value) => {
                    const off = value === '0' || value === 0 || value === false;
                    if (off) {
                        this.gstPercent = 0;
                    } else if (!this.gstPercent) {
                        this.gstPercent = 10;
                    }
                });
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
