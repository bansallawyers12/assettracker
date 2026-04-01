<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Edit Transaction for {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-blue-300 dark:border-blue-600">
                <form method="POST" id="bank-edit-transaction-form" action="{{ route('business-entities.transactions.update', [$businessEntity->id, $transaction->id]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @php
                        $dir = $transaction->direction;
                        $oldDir = old('direction', $dir);
                        $oldStatus = old('payment_status', $transaction->payment_status ?? 'paid');
                    @endphp

                    {{-- Direction toggle --}}
                    <div class="flex gap-3 mb-5">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="expense" class="sr-only peer" {{ $oldDir === 'expense' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 peer-checked:text-red-700 dark:peer-checked:text-red-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                                Expense
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="direction" value="income" class="sr-only peer" {{ $oldDir === 'income' ? 'checked' : '' }}>
                            <div class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 peer-checked:text-green-700 dark:peer-checked:text-green-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                                Income
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="date" name="date" value="{{ old('date', $transaction->date->toDateString()) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" id="bank_edit_amount" step="0.01" value="{{ old('amount', $transaction->amount) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $transaction->description) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendor name</label>
                            <input type="text" name="vendor_name" value="{{ old('vendor_name', $transaction->vendor_name) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="Supplier or party name">
                            @error('vendor_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Number <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $transaction->invoice_number) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="e.g., INV-0042">
                            @error('invoice_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Transaction::$incomeTypes as $value => $label)
                                    <option value="{{ $value }}" data-direction="income" {{ old('transaction_type', $transaction->transaction_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                                @foreach (\App\Models\Transaction::$expenseTypes as $value => $label)
                                    <option value="{{ $value }}" data-direction="expense" {{ old('transaction_type', $transaction->transaction_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <select name="related_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Related Entity</option>
                                @foreach(\App\Models\BusinessEntity::where('id', '!=', $transaction->business_entity_id)->get() as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', $transaction->related_entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <select name="asset_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">None — entity only</option>
                                @foreach ($businessEntity->assets()->orderBy('name')->get() as $asset)
                                    <option value="{{ $asset->id }}" {{ (string) old('asset_id', $transaction->asset_id) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
                                @endforeach
                            </select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

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
                                    <input type="radio" name="gst_basis" value="inclusive" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === 'inclusive' ? 'checked' : '' }}> GST inclusive — amount includes 10%
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="exclusive" class="rounded-full border-gray-300 text-blue-600" {{ $editGstBasis === 'exclusive' ? 'checked' : '' }}> GST exclusive — 10% on top
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Leave GST amount blank to auto-calculate. Enter a value to override.</p>
                            @error('gst_basis') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST amount <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" name="gst_amount" id="bank_edit_gst_amount" step="0.01" value="{{ old('gst_amount', $transaction->gst_amount) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    {{-- Payment Status --}}
                    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Payment Status</p>
                        <div class="flex gap-3 mb-4">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="payment_status" value="paid" id="payment_status_paid" class="sr-only peer" {{ $oldStatus === 'paid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:text-blue-700 dark:peer-checked:text-blue-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Paid
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="payment_status" value="unpaid" id="payment_status_unpaid" class="sr-only peer" {{ $oldStatus === 'unpaid' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:text-amber-700 dark:peer-checked:text-amber-300 text-gray-600 dark:text-gray-400 font-semibold text-sm transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Unpaid
                                </div>
                            </label>
                        </div>

                        {{-- Unpaid block --}}
                        <div id="unpaid_block" class="{{ $oldStatus === 'unpaid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                <input type="date" name="due_date" value="{{ old('due_date', $transaction->due_date?->toDateString()) }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Paid block --}}
                        <div id="paid_block" class="{{ $oldStatus === 'paid' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at', $transaction->paid_at?->toDateString()) }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('paid_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                                <select name="payment_method" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select Method</option>
                                    @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('payment_method', $transaction->payment_method) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" id="paid_by_label">
                                    {{ $dir === 'income' ? 'Received By / Account' : 'Paid By' }}
                                </label>
                                <input type="text" name="paid_by" value="{{ old('paid_by', $transaction->paid_by) }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="e.g., ANZ Cheque, Director">
                                @error('paid_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
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
                                       accept="image/*,application/pdf">
                                @error('payment_document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Receipt Name</label>
                                <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}"
                                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="e.g., Bank Transfer Confirmation">
                                @error('payment_document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-5">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Update Transaction</button>
                        <a href="{{ route('business-entities.show', $businessEntity->id) }}#tab_transactions" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Cancel</a>
                    </div>
                </form>
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

            function getDirection() {
                const checked = document.querySelector('input[name="direction"]:checked');
                return checked ? checked.value : 'expense';
            }

            function filterTypesByDirection(direction) {
                if (!transactionTypeSelect) return;
                Array.from(transactionTypeSelect.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !opt.dataset.direction || opt.dataset.direction === direction;
                    opt.hidden   = !match;
                    opt.disabled = !match;
                });
                if (transactionTypeSelect.options[transactionTypeSelect.selectedIndex]?.disabled) {
                    transactionTypeSelect.value = '';
                }
            }

            function updatePaidByLabel(direction) {
                if (paidByLabel) {
                    paidByLabel.textContent = direction === 'income' ? 'Received By / Account' : 'Paid By';
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

            if (transactionTypeSelect && relatedEntityField) {
                const relatedPartyTypes = [
                    'director_loan_in',
                    'director_loan_out',
                    'director_loan_repayment',
                ];
                transactionTypeSelect.addEventListener('change', function () {
                    const show = relatedPartyTypes.includes(this.value);
                    relatedEntityField.style.display = show ? 'block' : 'none';
                    relatedEntityField.querySelector('select').required = show;
                    if (!show) relatedEntityField.querySelector('select').value = '';
                });
                if (relatedPartyTypes.includes(transactionTypeSelect.value)) {
                    relatedEntityField.style.display = 'block';
                    relatedEntityField.querySelector('select').required = true;
                }
            }

            (function bankEditGstCalc() {
                const form = document.getElementById('bank-edit-transaction-form');
                const amtEl = document.getElementById('bank_edit_amount');
                const gstEl = document.getElementById('bank_edit_gst_amount');
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
