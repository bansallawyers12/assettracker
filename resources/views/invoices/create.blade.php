<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Create Invoice — {{ $businessEntity->legal_name }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    All invoices
                </a>
                <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200 rounded-lg text-sm font-medium transition-colors">
                    Rent invoices
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8"
         x-data="invoiceCreateForm(@js([
             'assets' => $assetsForForm,
             'incomeAccounts' => $incomeAccounts->map(fn ($a) => [
                 'code' => $a->account_code,
                 'label' => $a->account_code.' — '.$a->account_name,
             ])->values(),
             'defaultAccountCode' => $defaultAccountCode,
             'assetId' => old('asset_id'),
             'leaseId' => old('lease_id'),
             'customerName' => old('customer_name', ''),
             'reference' => old('reference', ''),
             'gstBasis' => old('gst_basis', 'inclusive'),
             'gstPercent' => (float) old('gst_percent', 10),
             'issueDate' => $issueDate,
             'dueDate' => $defaultDueDate,
             'lines' => old('lines', [[
                 'description' => '',
                 'quantity' => 1,
                 'unit_price' => 0,
                 'account_code' => $defaultAccountCode,
             ]]),
         ]))">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            For recurring monthly rent, prefer
            <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}" class="text-indigo-600 dark:text-indigo-400 underline">Rent invoices</a>
            so amounts and lease links are generated automatically. Use this form for one-off invoices.
        </p>

        <form method="POST" action="{{ route('business-entities.invoices.store', $businessEntity) }}"
              class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            @csrf

            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice number</label>
                    <input name="invoice_number" value="{{ $suggestedInvoiceNumber }}" required
                           class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                    <p class="mt-1 text-xs text-gray-500">Includes entity id {{ $businessEntity->id }} (INV{{ $businessEntity->id }}-YYYYMM###).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Issue date</label>
                    <x-date-input name="issue_date" x-model="issueDate" @change="syncDueDateFromIssue()" value="{{ $issueDate }}"
                                  class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due date</label>
                    <x-date-input name="due_date" x-model="dueDate" value="{{ $defaultDueDate }}"
                                  class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded-sm" />
                    <p class="mt-1 text-xs text-gray-500">Defaults to issue date + 30 days.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asset</label>
                    <select name="asset_id" x-model="assetId" @change="onAssetChange()"
                            class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm">
                        <option value="">— Optional —</option>
                        @foreach ($assetsForForm as $asset)
                            <option value="{{ $asset['id'] }}" @selected((string) old('asset_id') === (string) $asset['id'])>{{ $asset['name'] }}</option>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GST %</label>
                    <input type="number" name="gst_percent" x-model.number="gstPercent" min="0" max="100" step="0.01" required
                           class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                </div>

                <div class="md:col-span-3">
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST basis</span>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                            <input type="radio" name="gst_basis" value="inclusive" x-model="gstBasis" class="rounded-sm border-gray-300" />
                            Inclusive (unit price includes GST — same as rent invoices)
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                            <input type="radio" name="gst_basis" value="exclusive" x-model="gstBasis" class="rounded-sm border-gray-300" />
                            Exclusive (GST added on top)
                        </label>
                    </div>
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
                    <div class="md:col-span-2" x-text="gstBasis === 'inclusive' ? 'Unit price (inc GST)' : 'Unit price (ex GST)'"></div>
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
                            <label class="md:hidden text-xs text-gray-500" x-text="gstBasis === 'inclusive' ? 'Unit price (inc GST)' : 'Unit price (ex GST)'"></label>
                            <input type="number" step="0.01" min="0" :name="'lines[' + index + '][unit_price]'" x-model.number="line.unit_price" required
                                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="md:hidden text-xs text-gray-500">Income account</label>
                            <select :name="'lines[' + index + '][account_code]'" x-model="line.account_code" required
                                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-2 rounded-sm">
                                <template x-for="account in incomeAccounts" :key="account.code">
                                    <option :value="account.code" x-text="account.label"></option>
                                </template>
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
                    <div class="flex justify-between gap-8"><span>Subtotal (ex GST)</span><span class="font-medium" x-text="formatMoney(totals.subtotal)"></span></div>
                    <div class="flex justify-between gap-8"><span>GST</span><span class="font-medium" x-text="formatMoney(totals.gst)"></span></div>
                    <div class="flex justify-between gap-8 text-base font-semibold text-gray-900 dark:text-white pt-1 border-t border-gray-200 dark:border-gray-700">
                        <span>Total</span><span x-text="formatMoney(totals.total)"></span>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Save draft
                </button>
            </div>
        </form>
    </div>

    <script>
        function invoiceCreateForm(config) {
            return {
                assets: config.assets || [],
                incomeAccounts: config.incomeAccounts || [],
                defaultAccountCode: config.defaultAccountCode || '',
                assetId: config.assetId ? String(config.assetId) : '',
                leaseId: config.leaseId ? String(config.leaseId) : '',
                customerName: config.customerName || '',
                reference: config.reference || '',
                gstBasis: config.gstBasis || 'inclusive',
                gstPercent: Number(config.gstPercent ?? 10),
                issueDate: config.issueDate || '',
                dueDate: config.dueDate || '',
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
                get gstRate() {
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
                },
                syncDueDateFromIssue() {
                    if (!this.issueDate) {
                        return;
                    }
                    const parts = String(this.issueDate).split('-').map(Number);
                    if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
                        return;
                    }
                    const next = new Date(parts[0], parts[1] - 1, parts[2]);
                    next.setDate(next.getDate() + 30);
                    const yyyy = next.getFullYear();
                    const mm = String(next.getMonth() + 1).padStart(2, '0');
                    const dd = String(next.getDate()).padStart(2, '0');
                    this.dueDate = `${yyyy}-${mm}-${dd}`;
                    const dueInput = this.$root.querySelector('input[name="due_date"]');
                    if (dueInput) {
                        dueInput.value = this.dueDate;
                        dueInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                },
            };
        }
    </script>
</x-app-layout>
