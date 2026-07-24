<x-app-layout>
    <div class="py-6 lg:py-8 bg-linear-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero / Greeting --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-blue-600 via-blue-700 to-indigo-700 p-6 lg:p-8 text-white shadow-xl">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-1/2 -mb-8 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold">Welcome back, {{ Auth::user()->name }}</h1>
                        <p class="mt-1 text-blue-100 text-sm lg:text-base">{{ now()->format('l, F j, Y') }} &mdash; Here's your overview.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('business-entities.create') }}"
                           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-xs text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <x-lucide-plus class="w-4 h-4" />
                            New Entity
                        </a>
                        <button id="add-transaction-btn"
                                class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-xs text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <x-lucide-clipboard class="w-4 h-4" />
                            Add Transaction
                        </button>
                        <a href="{{ route('bills-tasks.index') }}"
                           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-xs text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <x-lucide-clipboard-list class="w-4 h-4" />
                            Bills &amp; tasks
                        </a>
                        <a href="{{ route('emails.index') }}"
                           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-xs text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <x-lucide-mail class="w-4 h-4" />
                            Emails
                        </a>
                    </div>
                </div>
            </div>

            {{-- Add Transaction Section (Collapsible) --}}
            @php
                $oldStatus = old('payment_status', session('transactionData.payment_status', 'paid'));
                $txnLabel = 'block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5';
                $txnInput = 'block w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/80 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-100 shadow-xs placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:border-blue-400 transition-colors';
                $txnSelect = 'block w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/80 text-sm text-gray-900 dark:text-gray-100 shadow-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:border-blue-400 transition-colors';
                $txnSection = 'rounded-xl border border-gray-100 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-900/30 p-5 space-y-4';
            @endphp
            <div id="add-transaction-section" class="{{ ($errors->any() || session('error') || session('keep_open')) ? '' : 'hidden' }} overflow-visible rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl transition-all duration-300">
                <div class="relative border-b border-gray-100 dark:border-gray-700 bg-linear-to-r from-blue-50 via-white to-indigo-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800/90 px-6 py-5">
                    <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-blue-500 via-indigo-500 to-violet-500"></div>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600/10 dark:bg-blue-500/15 ring-1 ring-blue-600/20 dark:ring-blue-400/30">
                                    <x-lucide-clipboard class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </span>
                                Add Transactions
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Record one or more income/expense lines for the same entity, asset, and payment details.</p>
                        </div>
                        <button type="button" id="cancel-transaction-btn" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors" aria-label="Close form">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div class="p-6">
                @php
                    $dashboardTransactionEntityId = $businessEntities->first()?->id ?? 0;
                    $dashboardTxnErrorToast = null;
                    if ($errors->any()) {
                        $dashboardTxnErrorToast = $errors->count() === 1
                            ? $errors->first()
                            : "Could not save these transactions:\n" . implode("\n", $errors->all());
                    }

                    $dashboardTypeGroups = [];
                    foreach (\App\Models\Transaction::typeSelectGroups() as $groupLabel => $types) {
                        $options = [];
                        foreach ($types as $value => $label) {
                            $options[] = [
                                'value' => $value,
                                'label' => $label,
                                'direction' => array_key_exists($value, \App\Models\Transaction::$incomeTypes) ? 'income' : 'expense',
                            ];
                        }
                        $dashboardTypeGroups[] = ['label' => $groupLabel, 'options' => $options];
                    }

                    $dashboardVendorsJson = $vendors->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values();
                    $dashboardRelatedEntitiesJson = $businessEntities->sortBy('legal_name')->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->legal_name,
                    ])->values();

                    $oldLines = old('lines');
                    if (! is_array($oldLines) || count($oldLines) === 0) {
                        $seedType = old('transaction_type', session('transactionData.transaction_type', ''));
                        $seedDirection = old('direction', session('transactionData.direction'));
                        if (! in_array($seedDirection, ['income', 'expense'], true)) {
                            $seedDirection = $seedType && array_key_exists($seedType, \App\Models\Transaction::$incomeTypes)
                                ? 'income'
                                : 'expense';
                        }
                        $oldLines = [[
                            'direction' => $seedDirection,
                            'amount' => old('amount', session('transactionData.amount', '')),
                            'description' => old('description', session('transactionData.description', '')),
                            'vendor_id' => (string) old('vendor_id', session('transactionData.vendor_id', '')),
                            'transaction_type' => $seedType,
                            'invoice_number' => old('invoice_number', session('transactionData.invoice_number', '')),
                            'related_entity_id' => (string) old('related_entity_id', session('transactionData.related_entity_id', '')),
                            'gst_basis' => old('gst_basis', session('transactionData.gst_basis', 'none')) ?: 'none',
                            'gst_amount' => old('gst_amount', session('transactionData.gst_amount', '')),
                        ]];
                    } else {
                        $oldLines = array_values(array_map(function ($line) {
                            $type = (string) ($line['transaction_type'] ?? '');
                            $direction = $line['direction'] ?? null;
                            if (! in_array($direction, ['income', 'expense'], true)) {
                                $direction = $type && array_key_exists($type, \App\Models\Transaction::$incomeTypes)
                                    ? 'income'
                                    : 'expense';
                            }
                            $gstBasis = $line['gst_basis'] ?? 'none';
                            if ($gstBasis === '' || $gstBasis === null) {
                                $gstBasis = 'none';
                            }

                            return [
                                'direction' => $direction,
                                'amount' => $line['amount'] ?? '',
                                'description' => $line['description'] ?? '',
                                'vendor_id' => isset($line['vendor_id']) && $line['vendor_id'] !== null ? (string) $line['vendor_id'] : '',
                                'transaction_type' => $type,
                                'invoice_number' => $line['invoice_number'] ?? '',
                                'related_entity_id' => isset($line['related_entity_id']) && $line['related_entity_id'] !== null ? (string) $line['related_entity_id'] : '',
                                'gst_basis' => $gstBasis,
                                'gst_amount' => $line['gst_amount'] ?? '',
                            ];
                        }, $oldLines));
                    }

                    $dashboardTxnBatchConfig = [
                        'initialLines' => $oldLines,
                        'typeGroups' => $dashboardTypeGroups,
                        'vendors' => $dashboardVendorsJson->all(),
                        'relatedEntities' => $dashboardRelatedEntitiesJson->all(),
                        'maxLines' => 20,
                        'relatedPartyTypes' => [
                            'director_loan_in',
                            'director_loan_out',
                            'director_loan_repayment',
                            'directors_loans_to_company',
                            'repayment_directors_loans',
                            'company_loans_to_directors',
                        ],
                    ];
                @endphp
                <script>
                    window.dashboardTxnBatch = function (config) {
                        const buildFlatTypes = (typeGroups, direction) => {
                            const rows = [];
                            (typeGroups || []).forEach((group) => {
                                (group.options || []).forEach((opt) => {
                                    if (opt.direction !== direction) return;
                                    rows.push({
                                        value: opt.value,
                                        label: opt.label + ' — ' + group.label,
                                    });
                                });
                            });
                            return rows;
                        };

                        const blankLine = (seed = {}) => {
                            let gstBasis = seed.gst_basis ?? 'none';
                            if (gstBasis === '' || gstBasis === null || gstBasis === undefined) {
                                gstBasis = 'none';
                            }
                            return {
                                _key: 'l_' + Math.random().toString(36).slice(2, 10),
                                direction: seed.direction || 'expense',
                                amount: seed.amount ?? '',
                                description: seed.description ?? '',
                                vendor_id: seed.vendor_id != null && seed.vendor_id !== '' ? String(seed.vendor_id) : '',
                                transaction_type: seed.transaction_type ?? '',
                                invoice_number: seed.invoice_number ?? '',
                                related_entity_id: seed.related_entity_id != null && seed.related_entity_id !== '' ? String(seed.related_entity_id) : '',
                                gst_basis: gstBasis,
                                gst_amount: seed.gst_amount ?? '',
                                gstTouched: !!(seed.gst_amount !== null && seed.gst_amount !== undefined && String(seed.gst_amount) !== ''),
                            };
                        };

                        const typeGroups = config.typeGroups || [];

                        return {
                            lines: (config.initialLines || []).map((line) => blankLine(line)),
                            vendors: config.vendors || [],
                            relatedEntities: config.relatedEntities || [],
                            maxLines: config.maxLines || 20,
                            relatedPartyTypes: config.relatedPartyTypes || [],
                            incomeTypes: buildFlatTypes(typeGroups, 'income'),
                            expenseTypes: buildFlatTypes(typeGroups, 'expense'),
                            get canAddLine() {
                                return this.lines.length < this.maxLines;
                            },
                            get totals() {
                                let income = 0;
                                let expense = 0;
                                this.lines.forEach((line) => {
                                    const amount = parseFloat(line.amount);
                                    if (Number.isNaN(amount)) return;
                                    let cash = amount;
                                    const gst = parseFloat(line.gst_amount);
                                    if (line.gst_basis === 'exclusive' && !Number.isNaN(gst) && gst > 0) {
                                        cash = Math.round((amount + gst) * 100) / 100;
                                    }
                                    if (line.direction === 'income') income += cash;
                                    else expense += cash;
                                });
                                return {
                                    income: Math.round(income * 100) / 100,
                                    expense: Math.round(expense * 100) / 100,
                                    net: Math.round((income - expense) * 100) / 100,
                                };
                            },
                            get submitLabel() {
                                return 'Save transaction';
                            },
                            typesFor(direction) {
                                return direction === 'income' ? this.incomeTypes : this.expenseTypes;
                            },
                            showRelatedEntity(type) {
                                return this.relatedPartyTypes.includes(type);
                            },
                            onDirectionChange(index) {
                                const line = this.lines[index];
                                if (!line) return;
                                const allowed = new Set(this.typesFor(line.direction).map((o) => o.value));
                                if (line.transaction_type && !allowed.has(line.transaction_type)) {
                                    line.transaction_type = '';
                                }
                                if (!this.showRelatedEntity(line.transaction_type)) {
                                    line.related_entity_id = '';
                                }
                                this.updatePaidByLabel();
                            },
                            updatePaidByLabel() {
                                const label = document.getElementById('paid_by_label');
                                if (!label) return;
                                const dirs = new Set(this.lines.map((l) => l.direction));
                                if (dirs.size > 1) {
                                    label.textContent = 'Paid / received by';
                                } else if (dirs.has('income')) {
                                    label.textContent = 'Received By';
                                } else {
                                    label.textContent = 'Paid By';
                                }
                            },
                            recalcGst(index) {
                                const line = this.lines[index];
                                if (!line || line.gstTouched) return;
                                const amount = parseFloat(line.amount);
                                const basis = line.gst_basis;
                                if (!basis || basis === 'none' || Number.isNaN(amount)) {
                                    line.gst_amount = '';
                                    return;
                                }
                                if (basis === 'inclusive') {
                                    line.gst_amount = (Math.round((amount - amount / 1.1) * 100) / 100).toFixed(2);
                                } else if (basis === 'exclusive') {
                                    line.gst_amount = (Math.round(amount * 0.1 * 100) / 100).toFixed(2);
                                }
                            },
                            addLine() {
                                if (!this.canAddLine) return;
                                const prev = this.lines[this.lines.length - 1];
                                this.lines.push(blankLine({
                                    direction: prev?.direction || 'expense',
                                    gst_basis: prev?.gst_basis || 'none',
                                }));
                                this.updatePaidByLabel();
                            },
                            removeLine(index) {
                                if (this.lines.length <= 1) return;
                                this.lines.splice(index, 1);
                                this.updatePaidByLabel();
                            },
                            init() {
                                this.updatePaidByLabel();
                            },
                        };
                    };
                </script>
                <form method="POST"
                      action="/business-entities/{{ $dashboardTransactionEntityId }}/transactions"
                      data-store-action-template="/business-entities/__ID__/transactions"
                      id="store-transaction-form"
                      data-transaction-paid-by-form
                      enctype="multipart/form-data"
                      class="dashboard-txn-form space-y-6"
                      x-data="window.dashboardTxnBatch(@js($dashboardTxnBatchConfig))"
                      x-init="init()">
                    @csrf

                    {{-- Context --}}
                    <section class="{{ $txnSection }}">
                        <div class="flex items-center gap-2 pb-1">
                            <x-lucide-building-2 class="w-4 h-4 text-blue-500 dark:text-blue-400" />
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Where</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                        {{-- Business Entity --}}
                        <div>
                            <label class="{{ $txnLabel }}">Business Entity</label>
                            <x-tom-select name="business_entity_id" id="business_entity_id" class="{{ $txnSelect }}" required>
                                <option value="">Select Entity</option>
                                @foreach ($businessEntities as $entity)
                                    <option value="{{ $entity->id }}" {{ old('business_entity_id', session('transactionData.business_entity_id')) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('business_entity_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Asset --}}
                        <div>
                            <label class="{{ $txnLabel }}">Asset <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                            <x-tom-select name="asset_id" id="transaction_asset_id" class="{{ $txnSelect }}">
                                <option value="">None — entity only</option>
                                @foreach ($assets as $asset)
                                    <option value="{{ $asset->id }}" data-entity-id="{{ $asset->business_entity_id }}"
                                        {{ (string) old('asset_id', session('transactionData.asset_id')) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('asset_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date --}}
                        <div>
                            <label class="{{ $txnLabel }}">Date</label>
                            <x-date-input name="date" value="{{ old('date', session('transactionData.date', now()->toDateString())) }}"
                                   class="{{ $txnInput }}" required />
                            @error('date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        </div>
                    </section>

                    @include('partials.dashboard-transaction-lines', [
                        'txnLabel' => $txnLabel,
                        'txnInput' => $txnInput,
                        'txnSection' => $txnSection,
                    ])
                    @error('lines') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    @foreach ($errors->getMessages() as $errKey => $errMsgs)
                        @if (str_starts_with($errKey, 'lines.'))
                            @foreach ($errMsgs as $errMsg)
                                <span class="text-red-500 text-xs mt-1 block">{{ $errMsg }}</span>
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Documents --}}
                    <section class="{{ $txnSection }}">
                        <div class="flex items-center gap-2 pb-1">
                            <x-lucide-paperclip class="w-4 h-4 text-violet-500 dark:text-violet-400" />
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Attachments</h4>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">Attached once and linked to every line in this batch.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Invoice / Bill upload --}}
                        <div>
                            <label class="{{ $txnLabel }}">Invoice / Bill <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                            <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/40 px-4 py-3">
                                <input type="file" name="document"
                                       class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-500 dark:file:bg-blue-600"
                                       accept="{{ config('documents.transaction_file_accept') }}">
                            </div>
                            @php $dashDocMaxKb = max(1, (int) config('documents.max_kilobytes', 10240)); @endphp
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Up to {{ number_format($dashDocMaxKb / 1024, 1) }} MB. Check PHP <span class="font-mono">upload_max_filesize</span> if uploads fail.</p>
                            @error('document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Document Name --}}
                        <div>
                            <label class="{{ $txnLabel }}">Invoice / Bill Name</label>
                            <input type="text" name="document_name" value="{{ old('document_name') }}"
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
                        <div class="rounded-xl bg-gray-100/80 dark:bg-gray-900/50 p-1.5 grid grid-cols-2 gap-1.5 max-w-md">
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="paid" id="payment_status_paid" class="sr-only peer" {{ $oldStatus === 'paid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 rounded-lg py-2.5 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400 transition-all peer-checked:bg-white dark:peer-checked:bg-gray-800 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-blue-200 dark:peer-checked:ring-blue-900/50">
                                    <x-lucide-check class="w-4 h-4" />
                                    Paid
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_status" value="unpaid" id="payment_status_unpaid" class="sr-only peer" {{ $oldStatus === 'unpaid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 rounded-lg py-2.5 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400 transition-all peer-checked:bg-white dark:peer-checked:bg-gray-800 peer-checked:text-amber-600 dark:peer-checked:text-amber-400 peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-amber-200 dark:peer-checked:ring-amber-900/50">
                                    <x-lucide-clock class="w-4 h-4" />
                                    Unpaid
                                </div>
                            </label>
                        </div>

                        {{-- Unpaid block --}}
                        <div id="unpaid_block" class="{{ $oldStatus === 'unpaid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="{{ $txnLabel }}">Due Date</label>
                                <x-date-input name="due_date" value="{{ old('due_date', session('transactionData.due_date')) }}"
                                       class="{{ $txnInput }}" />
                                @error('due_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Paid block --}}
                        <div id="paid_block" class="{{ $oldStatus === 'paid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="{{ $txnLabel }}">Payment Date</label>
                                <x-date-input name="paid_at" value="{{ old('paid_at', session('transactionData.paid_at')) }}"
                                       class="{{ $txnInput }}" />
                                @error('paid_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $txnLabel }}">Payment Method</label>
                                <x-tom-select name="payment_method" class="{{ $txnSelect }} px-3 py-2.5">
                                    <option value="">Select Method</option>
                                    @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('payment_method', session('transactionData.payment_method')) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </x-tom-select>
                                @error('payment_method') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2 lg:col-span-1">
                                @php
                                    $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm(session('transactionData.paid_by'));
                                @endphp
                                @include('partials.transaction-paid-by-fields', [
                                    'payerOptions' => $payerOptions,
                                    'paidBySelect' => $pbSplit['select'],
                                    'paidByOther' => $pbSplit['other'],
                                    'bankAccountId' => old('bank_account_id', session('transactionData.bank_account_id')),
                                    'paidByLabelText' => 'Paid / received by',
                                    'labelClass' => $txnLabel,
                                    'selectClass' => $txnSelect . ' px-3 py-2.5',
                                    'errorClass' => 'text-xs mt-1',
                                ])
                            </div>
                            <div>
                                <label class="{{ $txnLabel }}">Payment Receipt <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                                <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/40 px-4 py-3">
                                    <input type="file" name="payment_document"
                                           class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-500"
                                           accept="{{ config('documents.transaction_file_accept') }}">
                                </div>
                                @error('payment_document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="{{ $txnLabel }}">Payment Receipt Name</label>
                                <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}"
                                       class="{{ $txnInput }}"
                                       placeholder="e.g., Bank Transfer Confirmation">
                                @error('payment_document_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </section>

                    <input type="hidden" name="receipt_path" value="{{ old('receipt_path', session('transactionData.receipt_path')) }}">

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" id="cancel-transaction-footer-btn"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-colors">
                            <x-lucide-check class="w-4 h-4" />
                            <span x-text="submitLabel">Save transaction</span>
                        </button>
                    </div>
                </form>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <x-lucide-building-2 class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $businessEntities->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Entities</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <x-lucide-package class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $assets->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Assets</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <x-lucide-users class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $persons->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Persons</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <x-lucide-bell class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $allReminders->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Reminders</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <x-lucide-clock class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $assetDueDateItems->count() + $entityDueDates->count() + $asicRenewalDueDates->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Due Soon</div>
                </div>
                <a href="{{ route('commitments.index') }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow block">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                            <x-lucide-file-text class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $commitmentSummary['active_count'] }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Commitments</div>
                    <div class="text-xs text-rose-600 dark:text-rose-400 mt-1 tabular-nums">${{ number_format($commitmentSummary['total_balance_due'], 0) }} due</div>
                </a>
            </div>

            {{-- Main Content: Two-Column Layout --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                {{-- Left Column (2/3 width) --}}
                <div class="xl:col-span-2 space-y-6">

                    {{-- Reminders --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-lucide-bell class="w-5 h-5 text-amber-500" />
                                Reminders
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Overdue/Next 15 days</span>
                            </h3>
                            <div class="flex items-center gap-2">
                            <a href="{{ route('bills-tasks.index', ['tab' => 'due']) }}" class="inline-flex text-xs font-semibold text-amber-700 dark:text-amber-300 hover:underline shrink-0">Full list</a>
                            <button type="button" id="toggle-reminder-form" class="inline-flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:hover:bg-amber-900/30 dark:text-amber-300 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                                <x-lucide-plus class="w-3.5 h-3.5" />
                                Add Reminder
                            </button>
                            </div>
                        </div>

                        {{-- Reminder Form --}}
                        <form id="reminder-form" class="hidden p-5 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700" method="POST" action="{{ route('reminders.store') }}">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Entity</label>
                                    <x-tom-select name="business_entity_id" id="reminder_business_entity_id" class="rounded-xl focus:ring-amber-500 focus:border-amber-500">
                                        <option value="">Select Entity (Optional)</option>
                                        @foreach ($businessEntities as $entity)
                                            <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                        @endforeach
                                    </x-tom-select>
                                    @error('business_entity_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asset</label>
                                    <x-tom-select name="asset_id" id="reminder_asset_id" class="rounded-xl focus:ring-amber-500 focus:border-amber-500" disabled>
                                        <option value="">Select Asset (Optional)</option>
                                        @foreach ($assets as $asset)
                                            <option value="{{ $asset->id }}" data-entity-id="{{ $asset->business_entity_id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                                        @endforeach
                                    </x-tom-select>
                                    @error('asset_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reminder</label>
                                    <textarea name="content" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-xs focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" rows="2" required>{{ old('content') }}</textarea>
                                    @error('content') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                                    <x-date-input  name="reminder_date" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-xs focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" min="{{ now()->format('Y-m-d') }}" value="{{ old('reminder_date', old('next_due_date')) }}" required />
                                    @error('reminder_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Repeat</label>
                                    <select name="repeat_type" id="repeat_type" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-xs focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm">
                                        <option value="none">One-off (No repeat)</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                    @error('repeat_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div id="repeat_end_date_container" style="display: none;">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date (Optional)</label>
                                    <x-date-input  name="repeat_end_date" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-xs focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" min="{{ now()->format('Y-m-d') }}" value="{{ old('repeat_end_date') }}" />
                                    @error('repeat_end_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold shadow-xs transition-colors">
                                    <x-lucide-check class="w-4 h-4" />
                                    Save Reminder
                                </button>
                            </div>
                        </form>

                        <div class="p-5">
                            @if ($allReminders->isEmpty())
                                <div class="text-center py-6">
                                    <x-lucide-bell class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No overdue reminders and nothing due in the next 15 days.</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($allReminders as $reminder)
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-600 hover:border-amber-200 dark:hover:border-amber-700 transition-colors">
                                            <div class="shrink-0 w-2 h-2 mt-2 rounded-full bg-amber-400"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                    {{ $reminder->content }}
                                                    @if (!empty($reminder->is_transaction))
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 shrink-0">BILL DUE</span>
                                                    @elseif (!empty($reminder->is_note))
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 shrink-0">NOTE</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $reminder->asset?->name ?? ($reminder->businessEntity?->legal_name ?? 'Unknown') }}
                                                    &middot; {{ $reminder->user?->name ?? 'Unknown' }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        <x-lucide-clock class="w-3 h-3" />
                                                        {{ $reminder->next_due_date ? $reminder->next_due_date->format('d/m/Y') : 'N/A' }}
                                                    </span>
                                                    @if($reminder->repeat_type && $reminder->repeat_type !== 'none')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                            <x-lucide-refresh-cw class="w-3 h-3" />
                                                            {{ ucfirst($reminder->repeat_type) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="shrink-0 flex gap-1.5">
                                                @if (!empty($reminder->is_transaction))
                                                    <a href="{{ route('business-entities.show', [$reminder->business_entity_id, 'transaction_id' => $reminder->transaction_id]) }}#tab_transactions" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 dark:text-indigo-400 transition-colors text-xs font-medium px-2" title="Open transaction">View</a>
                                                @else
                                                    <form action="{{ !empty($reminder->is_note) ? route('notes.finalize', $reminder->id) : route('reminders.complete', $reminder->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400 transition-colors" title="Finalize">
                                                            <x-lucide-check class="w-4 h-4" />
                                                        </button>
                                                    </form>
                                                    <form action="{{ !empty($reminder->is_note) ? route('notes.extend', $reminder->id) : route('reminders.extend', $reminder->id) }}" method="POST">
                                                        @csrf
                                                        @if (empty($reminder->is_note))
                                                            <input type="hidden" name="days" value="3">
                                                        @endif
                                                        <button type="submit" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 transition-colors" title="Extend">
                                                            <x-lucide-plus class="w-4 h-4" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Due Dates --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-lucide-calendar class="w-5 h-5 text-red-500" />
                                Upcoming Due Dates
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Next 15 days</span>
                            </h3>
                            <a href="{{ route('bills-tasks.index', ['tab' => 'due']) }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">Full list</a>
                        </div>
                        <div class="p-5">
                            @if ($assetDueDateItems->isNotEmpty() || $entityDueDates->isNotEmpty() || $asicRenewalDueDates->isNotEmpty() || $companiesMissingAsicRenewalDate->isNotEmpty())
                                <div class="space-y-3">
                                    @if ($companiesMissingAsicRenewalDate->isNotEmpty())
                                        <div class="rounded-xl border border-amber-200 bg-amber-50/80 dark:border-amber-900/40 dark:bg-amber-900/10 p-4">
                                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                                {{ $companiesMissingAsicRenewalDate->count() }} {{ Str::plural('company', $companiesMissingAsicRenewalDate->count()) }} missing {{ \App\Models\BusinessEntity::asicRenewalDateLabel() }}
                                            </p>
                                            <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">Add the anniversary so ASIC annual review reminders can track correctly.</p>
                                        </div>
                                    @endif

                                    @foreach ($assetDueDateItems as $item)
                                        @php
                                            $asset = $item->asset;
                                            $dashboardColorClasses = [
                                                'red' => 'bg-red-50/50 dark:bg-red-900/10 border-red-100 dark:border-red-900/30',
                                                'orange' => 'bg-orange-50/50 dark:bg-orange-900/10 border-orange-100 dark:border-orange-900/30',
                                                'blue' => 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/30',
                                                'purple' => 'bg-purple-50/50 dark:bg-purple-900/10 border-purple-100 dark:border-purple-900/30',
                                                'green' => 'bg-green-50/50 dark:bg-green-900/10 border-green-100 dark:border-green-900/30',
                                                'yellow' => 'bg-yellow-50/50 dark:bg-yellow-900/10 border-yellow-100 dark:border-yellow-900/30',
                                            ];
                                            $dotColorClasses = [
                                                'red' => 'bg-red-400',
                                                'orange' => 'bg-orange-400',
                                                'blue' => 'bg-blue-400',
                                                'purple' => 'bg-purple-400',
                                                'green' => 'bg-green-400',
                                                'yellow' => 'bg-yellow-400',
                                            ];
                                        @endphp
                                        <div class="flex items-start gap-4 p-4 rounded-xl border {{ $dashboardColorClasses[$item->color] ?? 'bg-gray-50/50 dark:bg-gray-900/10 border-gray-100 dark:border-gray-700' }}">
                                            <div class="shrink-0 w-2 h-2 mt-2 rounded-full {{ $dotColorClasses[$item->color] ?? 'bg-gray-400' }}"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->label }} Due &mdash; {{ $asset->name }} ({{ $asset->asset_type }})</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $asset->businessEntity->legal_name ?? 'Unknown Entity' }} &middot; {{ $item->date->format('d/m/Y') }}</p>
                                                @if ($asset->business_entity_id && $asset->businessEntity)
                                                    <div class="mt-2 flex gap-2">
                                                        <form action="{{ route('assets.finalize-due-date', [$asset->business_entity_id, $asset->id, $item->finalize_type]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                        </form>
                                                        <form action="{{ route('assets.extend-due-date', [$asset->business_entity_id, $asset->id, $item->finalize_type]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend (3 days)</button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    @foreach ($companiesMissingAsicRenewalDate as $missingCompany)
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30">
                                            <div class="shrink-0 w-2 h-2 mt-2 rounded-full bg-amber-400"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    Missing {{ \App\Models\BusinessEntity::asicRenewalDateLabel() }} &mdash; {{ $missingCompany->legal_name }}
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 align-middle">SETUP</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Required for ASIC annual review tracking</p>
                                                <div class="mt-2">
                                                    <a href="{{ route('business-entities.edit', $missingCompany) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Add anniversary</a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @foreach ($asicRenewalDueDates as $asicRow)
                                        @php $asicEntity = $asicRow->entity; @endphp
                                            <div class="flex items-start gap-4 p-4 rounded-xl bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30">
                                                <div class="shrink-0 w-2 h-2 mt-2 rounded-full bg-red-400"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ \App\Models\BusinessEntity::asicRenewalDateLabel() }} due &mdash; {{ $asicEntity->legal_name }}
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200 align-middle">ASIC</span>
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $asicRow->due_date->format('d/m/Y') }}</p>
                                                    <div class="mt-2">
                                                        <a href="{{ route('business-entities.show', $asicEntity) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">View entity</a>
                                                    </div>
                                                </div>
                                            </div>
                                    @endforeach

                                    @foreach ($entityDueDates as $entityDueDate)
                                        @if ($entityDueDate->asic_due_date)
                                            <div class="flex items-start gap-4 p-4 rounded-xl bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30">
                                                <div class="shrink-0 w-2 h-2 mt-2 rounded-full bg-red-400"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">ASIC Due &mdash; {{ $entityDueDate->businessEntity?->legal_name ?? 'Unknown Entity' }} ({{ $entityDueDate->role ?? 'Unknown Role' }})</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $entityDueDate->asic_due_date->format('d/m/Y') }}</p>
                                                    <div class="mt-2 flex gap-2">
                                                        <form action="{{ route('entity-persons.finalize-due-date', $entityDueDate->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                        </form>
                                                        <form action="{{ route('entity-persons.extend-due-date', $entityDueDate->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend (3 days)</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-6">
                                    <x-lucide-calendar class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming due dates.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Recent Items --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Business Entities --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Entities</h3>
                                <a href="{{ route('business-entities.index') }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($businessEntities->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No entities yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($businessEntities->take(3) as $entity)
                                            <a href="{{ route('business-entities.show', $entity->id) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $entity->legal_name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entity->entity_type ?? 'N/A' }}</p>
                                                </div>
                                                <x-lucide-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-blue-500 shrink-0 transition-colors" />
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Assets --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Assets</h3>
                                <a href="{{ route('assets.index') }}" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($assets->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No assets yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($assets->take(3) as $asset)
                                            <a href="{{ route('business-entities.assets.show', [$asset->business_entity_id, $asset->id]) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $asset->name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->asset_type }}</p>
                                                </div>
                                                <x-lucide-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-emerald-500 shrink-0 transition-colors" />
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Persons --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Persons</h3>
                                <a href="{{ route('persons.index') }}" class="text-xs font-medium text-violet-600 dark:text-violet-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($uniquePersons->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No persons yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($uniquePersons->take(3) as $personData)
                                            <a href="{{ route('persons.show', $personData['person']->id) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $personData['person']->first_name }} {{ $personData['person']->last_name }}</p>
                                                    <div class="flex gap-1.5 mt-1">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $personData['totalRoles'] }} role{{ $personData['totalRoles'] != 1 ? 's' : '' }}</span>
                                                        @if($personData['activeRoles'] > 0)
                                                            <span class="text-xs text-emerald-600 dark:text-emerald-400">&middot; {{ $personData['activeRoles'] }} active</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <x-lucide-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-violet-500 shrink-0 transition-colors" />
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column (1/3 width) --}}
                <div class="space-y-6">

                    {{-- Quick Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-1.5">
                            <a href="{{ route('business-entities.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-plus class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">Add Entity</span>
                            </a>
                            <a href="{{ route('business-entities.assets.create', $businessEntities->first()?->id ?? 0) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-package class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">Add Asset</span>
                            </a>
                            <a href="{{ route('persons.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-user class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-violet-700 dark:group-hover:text-violet-300 transition-colors">Add Person</span>
                            </a>
                            <a href="{{ route('emails.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-mail class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">Emails</span>
                            </a>
                        </div>
                    </div>

                    {{-- Accounting & Finance --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xs border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Accounting & Finance</h3>
                        </div>
                        <div class="p-4 space-y-1.5">
                            <a href="{{ route('chart-of-accounts.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-calculator class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">Chart of Accounts</span>
                            </a>
                            <a href="{{ route('bank-accounts.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-credit-card class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">Bank Accounts</span>
                            </a>
                            <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-clipboard class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">Transactions</span>
                            </a>
                            <a href="{{ route('vendors.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-truck class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors">Vendors</span>
                            </a>
                            <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center shrink-0">
                                    <x-lucide-file-text class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-700 dark:group-hover:text-orange-300 transition-colors">Invoices</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transactionBtn = document.getElementById('add-transaction-btn');
            const transactionSection = document.getElementById('add-transaction-section');
            const cancelTransactionBtn = document.getElementById('cancel-transaction-btn');
            const cancelTransactionFooterBtn = document.getElementById('cancel-transaction-footer-btn');
            const entitySelect = document.getElementById('business_entity_id');
            const transactionAssetSelect = document.getElementById('transaction_asset_id');

            function hideTransactionForm() {
                transactionSection?.classList.add('hidden');
            }

            function showTransactionForm() {
                transactionSection?.classList.remove('hidden');
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        afterTransactionFormVisible();
                    });
                });
                transactionSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function reinitTransactionTomSelects() {
                const form = document.getElementById('store-transaction-form');
                if (!form) {
                    return;
                }

                form.querySelectorAll('select[data-tomselect]').forEach((select) => {
                    delete select.dataset.tomselectDeferred;
                    window.reinitTomSelect?.(select);
                });

                window.refreshTransactionPaidByBankAccount?.(form);
            }

            function readDashboardSelectValue(select) {
                return window.getSelectValue?.(select) ?? select?.value ?? '';
            }

            function syncTransactionFormFromEntitySelect() {
                if (!entitySelect) {
                    return;
                }

                const entityId = readDashboardSelectValue(entitySelect);
                const storeForm = document.getElementById('store-transaction-form');
                const actionTemplate = storeForm?.dataset.storeActionTemplate;

                if (entityId && storeForm && actionTemplate) {
                    storeForm.action = actionTemplate.replace('__ID__', entityId);
                }

                if (transactionAssetSelect) {
                    const keepValue = readDashboardSelectValue(transactionAssetSelect);

                    Array.from(transactionAssetSelect.options).forEach(opt => {
                        if (!opt.value) return;

                        if (!entityId) {
                            opt.hidden = false;
                            opt.disabled = false;

                            return;
                        }

                        const match = String(opt.dataset.entityId) === String(entityId);
                        opt.hidden = !match;
                        opt.disabled = !match;
                    });

                    if (entityId) {
                        const stillValid = Array.from(transactionAssetSelect.options).some(
                            o => String(o.value) === String(keepValue) && !o.disabled
                        );

                        if (keepValue && !stillValid) {
                            window.setSelectValue(transactionAssetSelect, '');
                        }
                    }

                    window.setSelectDisabled?.(transactionAssetSelect, !entityId);
                    window.refreshTomSelect?.(transactionAssetSelect);
                }
            }

            function afterTransactionFormVisible() {
                reinitTransactionTomSelects();
                syncTransactionFormFromEntitySelect();
            }

            if (transactionBtn && transactionSection) {
                transactionBtn.addEventListener('click', () => {
                    const wasHidden = transactionSection.classList.contains('hidden');
                    if (wasHidden) {
                        showTransactionForm();
                    } else {
                        hideTransactionForm();
                    }
                });
            }

            if (cancelTransactionBtn && transactionSection) {
                cancelTransactionBtn.addEventListener('click', hideTransactionForm);
            }

            if (cancelTransactionFooterBtn && transactionSection) {
                cancelTransactionFooterBtn.addEventListener('click', hideTransactionForm);
            }

            if (entitySelect) {
                entitySelect.addEventListener('change', syncTransactionFormFromEntitySelect);

                if (!transactionSection?.classList.contains('hidden')) {
                    syncTransactionFormFromEntitySelect();
                }

                document.getElementById('store-transaction-form')?.addEventListener('submit', () => {
                    syncTransactionFormFromEntitySelect();
                });
            }

            @if (session('error') || $errors->any())
                if (transactionSection) {
                    showTransactionForm();
                }
            @endif

            @if (session('success'))
                window.showToast?.(@json(session('success')), 'success', {
                    title: 'Transactions saved',
                    duration: 7000,
                });
                hideTransactionForm();
            @elseif (session('error'))
                window.showToast?.(@json(session('error')), 'error', {
                    title: 'Could not save transactions',
                    duration: 9000,
                });
            @elseif ($dashboardTxnErrorToast)
                window.showToast?.(@json($dashboardTxnErrorToast), 'error', {
                    title: 'Could not save transactions',
                    duration: 9000,
                });
            @endif

            function initializeReminderLogic() {
                const reminderForm = document.getElementById('reminder-form');
                const reminderFormToggle = document.getElementById('toggle-reminder-form');

                reminderFormToggle?.addEventListener('click', function() {
                    if (!reminderForm) {
                        return;
                    }

                    const wasHidden = reminderForm.classList.contains('hidden');
                    reminderForm.classList.toggle('hidden');

                    if (wasHidden) {
                        window.reinitTomSelect?.(document.getElementById('reminder_business_entity_id'));
                        window.reinitTomSelect?.(document.getElementById('reminder_asset_id'));
                    }
                });

                const repeatTypeSelect = document.getElementById('repeat_type');
                const repeatEndDateContainer = document.getElementById('repeat_end_date_container');
                const entitySelect = document.getElementById('reminder_business_entity_id');
                const assetSelect = document.getElementById('reminder_asset_id');

                if (repeatTypeSelect && repeatEndDateContainer) {
                    if (repeatTypeSelect.value !== 'none') {
                        repeatEndDateContainer.style.display = 'block';
                    } else {
                        repeatEndDateContainer.style.display = 'none';
                    }

                    repeatTypeSelect.addEventListener('change', function() {
                        repeatEndDateContainer.style.display = this.value !== 'none' ? 'block' : 'none';
                    });
                }

                if (entitySelect && assetSelect) {
                    entitySelect.addEventListener('change', function() {
                        window.setSelectDisabled?.(assetSelect, !this.value);
                        Array.from(assetSelect.options).forEach(option => {
                            if (!option.value || !option.dataset.entityId) {
                                return;
                            }
                            const match = option.dataset.entityId === entitySelect.value;
                            option.hidden = !match;
                            option.disabled = !match;
                        });
                        window.rebuildTomSelectFromNative?.(assetSelect);
                    });
                }
            }

            initializeReminderLogic();

            // ── Payment status toggle ──────────────────────────────────────────
            const unpaidBlock       = document.getElementById('unpaid_block');
            const paidBlock         = document.getElementById('paid_block');
            const paymentStatusPaid = document.getElementById('payment_status_paid');
            const paymentStatusUnpaid = document.getElementById('payment_status_unpaid');

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
        });
    </script>
</x-app-layout>
