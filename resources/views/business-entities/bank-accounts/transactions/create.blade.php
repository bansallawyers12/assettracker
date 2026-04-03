<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Add Transaction for {{ $bankAccount->bank_name }} ({{ $businessEntity->legal_name }})
        </h2>
    </x-slot>

    @php
        $td = $transactionData;
        $oldDir = old('direction', $td['direction'] ?? 'expense');
        $oldPay = old('payment_status', $td['payment_status'] ?? 'paid');
    @endphp

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-purple-300 dark:border-purple-600">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('business-entities.bank-accounts.extract-from-receipt', [$businessEntity->id, $bankAccount->id]) }}" enctype="multipart/form-data" class="mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
                    @csrf
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Pre-fill from receipt (optional)</p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Receipt / Invoice</label>
                        <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-gray-700 dark:file:text-purple-300" accept="{{ config('documents.transaction_file_accept') }}">
                        @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md shadow-sm transition duration-200 text-sm font-medium">Extract Data</button>
                </form>

                <form method="POST" action="{{ route('business-entities.bank-accounts.transactions.store', [$businessEntity->id, $bankAccount->id]) }}" enctype="multipart/form-data" id="bank-store-transaction-form">
                    @csrf

                    <div class="flex gap-3 mb-5">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="expense" class="sr-only peer" {{ $oldDir === 'expense' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 peer-checked:text-red-700 dark:peer-checked:text-red-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                Expense
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="income" class="sr-only peer" {{ $oldDir === 'income' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 peer-checked:text-green-700 dark:peer-checked:text-green-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                Income
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="date" name="date" value="{{ old('date', $td['date'] ?? now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" required>
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" id="bank_create_amount" step="0.01" value="{{ old('amount', $td['amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $td['description'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor name</label>
                            <input type="text" name="vendor_name" value="{{ old('vendor_name', $td['vendor_name'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" placeholder="Supplier or party name">
                            @error('vendor_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Number <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $td['invoice_number'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" placeholder="e.g., INV-0042">
                            @error('invoice_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Transaction::$incomeTypes as $value => $label)
                                    <option value="{{ $value }}" data-direction="income" {{ old('transaction_type', $td['transaction_type'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                                @foreach (\App\Models\Transaction::$expenseTypes as $value => $label)
                                    <option value="{{ $value }}" data-direction="expense" {{ old('transaction_type', $td['transaction_type'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <select name="related_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                <option value="">Select Related Entity</option>
                                @foreach(\App\Models\BusinessEntity::where('id', '!=', $businessEntity->id)->get() as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', $td['related_entity_id'] ?? '') == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <select name="asset_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                <option value="">None — entity only</option>
                                @foreach ($businessEntity->assets()->orderBy('name')->get() as $asset)
                                    <option value="{{ $asset->id }}" {{ (string) old('asset_id', $td['asset_id'] ?? '') === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
                                @endforeach
                            </select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @php $createGstBasis = old('gst_basis', $td['gst_basis'] ?? ''); @endphp
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST (10%)</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="" class="rounded-full border-gray-300 text-purple-600" {{ $createGstBasis === '' || $createGstBasis === null ? 'checked' : '' }}> No GST
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="inclusive" class="rounded-full border-gray-300 text-purple-600" {{ $createGstBasis === 'inclusive' ? 'checked' : '' }}> GST inclusive — amount includes 10%
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="exclusive" class="rounded-full border-gray-300 text-purple-600" {{ $createGstBasis === 'exclusive' ? 'checked' : '' }}> GST exclusive — 10% on top
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Leave GST amount blank to auto-calculate. Enter a value to override.</p>
                            @error('gst_basis') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST amount <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" name="gst_amount" id="bank_create_gst_amount" step="0.01" value="{{ old('gst_amount', $td['gst_amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Business Entity</label>
                            <select name="business_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" required>
                                @foreach ($businessEntities as $entity)
                                    <option value="{{ $entity->id }}" {{ old('business_entity_id', $businessEntity->id) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice / Bill <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-gray-700 dark:file:text-blue-300" accept="{{ config('documents.transaction_file_accept') }}">
                            @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice / Bill Name</label>
                            <input type="text" name="document_name" value="{{ old('document_name') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" placeholder="e.g., Invoice123">
                            @error('document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Payment Status</p>
                        <div class="flex gap-3 mb-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="payment_status" value="paid" id="payment_status_paid" class="sr-only peer" {{ $oldPay === 'paid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:text-blue-700 dark:peer-checked:text-blue-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">Paid</div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="payment_status" value="unpaid" id="payment_status_unpaid" class="sr-only peer" {{ $oldPay === 'unpaid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:text-amber-700 dark:peer-checked:text-amber-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">Unpaid</div>
                            </label>
                        </div>

                        <div id="unpaid_block" class="{{ $oldPay === 'unpaid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                <input type="date" name="due_date" value="{{ old('due_date', $td['due_date'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div id="paid_block" class="{{ $oldPay === 'paid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at', $td['paid_at'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                @error('paid_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                                <select name="payment_method" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                    <option value="">Select Method</option>
                                    @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('payment_method', $td['payment_method'] ?? '') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @php
                                $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($td['paid_by'] ?? null);
                            @endphp
                            @include('partials.transaction-paid-by-fields', [
                                'payerOptions' => $payerOptions,
                                'paidBySelect' => $pbSplit['select'],
                                'paidByOther' => $pbSplit['other'],
                                'paidByLabelText' => $oldDir === 'income' ? 'Received By / Account' : 'Paid By',
                            ])
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Receipt <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="file" name="payment_document" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 dark:file:bg-gray-700 dark:file:text-green-300" accept="{{ config('documents.transaction_file_accept') }}">
                                @error('payment_document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Receipt Name</label>
                                <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm" placeholder="e.g., Transfer confirmation">
                                @error('payment_document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="receipt_path" value="{{ $td['receipt_path'] ?? '' }}">

                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md shadow-sm transition duration-200 font-medium">Add Transaction</button>
                        <a href="{{ route('business-entities.show', ['business_entity' => $businessEntity->id, 'bank_account_id' => $bankAccount->id]) }}#tab_bank_accounts" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-md shadow-sm transition duration-200 font-medium">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const directionRadios = document.querySelectorAll('input[name="direction"]');
            const transactionTypeSelect = document.getElementById('transaction_type');
            const relatedEntityField = document.getElementById('related_entity_field');
            const paymentStatusPaid = document.getElementById('payment_status_paid');
            const paymentStatusUnpaid = document.getElementById('payment_status_unpaid');
            const unpaidBlock = document.getElementById('unpaid_block');
            const paidBlock = document.getElementById('paid_block');
            const paidByLabel = document.getElementById('paid_by_label');

            function getDirection() {
                const c = document.querySelector('input[name="direction"]:checked');
                return c ? c.value : 'expense';
            }

            function filterTypesByDirection(direction) {
                if (!transactionTypeSelect) return;
                Array.from(transactionTypeSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !opt.dataset.direction || opt.dataset.direction === direction;
                    opt.hidden = !match;
                    opt.disabled = !match;
                });
                const sel = transactionTypeSelect.options[transactionTypeSelect.selectedIndex];
                if (sel && sel.disabled) transactionTypeSelect.value = '';
            }

            function updatePaidByLabel(direction) {
                if (paidByLabel) paidByLabel.textContent = direction === 'income' ? 'Received By / Account' : 'Paid By';
            }

            directionRadios.forEach(r => r.addEventListener('change', function () {
                filterTypesByDirection(this.value);
                updatePaidByLabel(this.value);
            }));
            filterTypesByDirection(getDirection());
            updatePaidByLabel(getDirection());

            function syncPaymentBlocks() {
                const paid = paymentStatusPaid && paymentStatusPaid.checked;
                if (unpaidBlock) unpaidBlock.classList.toggle('hidden', paid);
                if (paidBlock) paidBlock.classList.toggle('hidden', !paid);
            }
            if (paymentStatusPaid) paymentStatusPaid.addEventListener('change', syncPaymentBlocks);
            if (paymentStatusUnpaid) paymentStatusUnpaid.addEventListener('change', syncPaymentBlocks);
            syncPaymentBlocks();

            const paidBySelect = document.getElementById('paid_by_select');
            const paidByOtherWrap = document.getElementById('paid_by_other_wrap');
            function syncPaidByOther() {
                if (!paidBySelect || !paidByOtherWrap) return;
                paidByOtherWrap.classList.toggle('hidden', paidBySelect.value !== 'other');
            }
            if (paidBySelect) paidBySelect.addEventListener('change', syncPaidByOther);
            syncPaidByOther();

            if (transactionTypeSelect && relatedEntityField) {
                const relatedPartyTypes = [
                    'director_loan_in',
                    'director_loan_out',
                    'director_loan_repayment',
                ];
                transactionTypeSelect.addEventListener('change', function () {
                    const show = relatedPartyTypes.includes(this.value);
                    relatedEntityField.style.display = show ? 'block' : 'none';
                    const rs = relatedEntityField.querySelector('select');
                    if (rs) { rs.required = show; if (!show) rs.value = ''; }
                });
                if (relatedPartyTypes.includes(transactionTypeSelect.value)) {
                    relatedEntityField.style.display = 'block';
                    const rs = relatedEntityField.querySelector('select');
                    if (rs) rs.required = true;
                }
            }

            (function bankCreateGstCalc() {
                const form = document.getElementById('bank-store-transaction-form');
                const amtEl = document.getElementById('bank_create_amount');
                const gstEl = document.getElementById('bank_create_gst_amount');
                if (!form || !amtEl || !gstEl) return;
                let gstTouched = @json(old('gst_amount') !== null && old('gst_amount') !== '');
                gstEl.addEventListener('input', () => { gstTouched = true; });
                function basis() {
                    const r = form.querySelector('input[name="gst_basis"]:checked');
                    return r ? r.value : '';
                }
                function recalc() {
                    if (gstTouched) return;
                    const a = parseFloat(amtEl.value);
                    const b = basis();
                    if (!b || Number.isNaN(a)) { gstEl.value = ''; return; }
                    if (b === 'inclusive') gstEl.value = (Math.round((a - a / 1.1) * 100) / 100).toFixed(2);
                    else if (b === 'exclusive') gstEl.value = (Math.round(a * 0.1 * 100) / 100).toFixed(2);
                }
                amtEl.addEventListener('input', recalc);
                form.querySelectorAll('input[name="gst_basis"]').forEach((r) => r.addEventListener('change', () => {
                    if (!r.checked) return;
                    gstTouched = false;
                    recalc();
                }));
            })();
        });
    </script>
</x-app-layout>
