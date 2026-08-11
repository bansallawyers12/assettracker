<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Add balance sheet entry — {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    @php
        $td = $transactionData;
        $returnContext = $returnContext ?? [];
        $oldChannel = old('payment_channel', $td['payment_channel'] ?? \App\Models\Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS);
        $oldType = old('transaction_type', $td['transaction_type'] ?? 'asset_purchase');
        $createGstBasis = old('gst_basis', $td['gst_basis'] ?? '');
        $returnTo = old('return_to', $returnContext['return_to'] ?? null);
        $returnBankAccountId = old('bank_account_id', $returnContext['bank_account_id'] ?? null);
        $returnBusinessEntityId = old('return_business_entity_id', $returnContext['return_business_entity_id'] ?? null);
    @endphp

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border-t-4 border-indigo-400 dark:border-indigo-600">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    Record capital and other balance-sheet items not paid through a bank account on this statement
                    (for example a property deposit or purchase paid from director funds — use Asset Purchase).
                    For day-to-day income and expenses, use Add transaction on the dashboard.
                    For advanced journals, use
                    <a href="{{ route('financial-reports.journal-entries.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">manual journal</a>.
                </p>

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
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                        <p class="font-semibold mb-2">Could not save this entry:</p>
                        <ul class="list-disc list-inside space-y-1.5 leading-snug">
                            @foreach ($errors->all() as $err)
                                <li class="whitespace-normal wrap-break-word">{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('business-entities.balance-sheet-entries.store', $businessEntity) }}"
                    enctype="multipart/form-data"
                    id="balance-sheet-entry-form"
                    data-transaction-paid-by-form
                    data-booking-entity-id="{{ $businessEntity->id }}"
                >
                    @csrf
                    <input type="hidden" name="payment_status" value="paid">
                    @if($returnTo)
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    @if($returnBusinessEntityId)
                        <input type="hidden" name="return_business_entity_id" value="{{ $returnBusinessEntityId }}">
                    @endif
                    @if($returnBankAccountId)
                        <input type="hidden" name="bank_account_id" value="{{ $returnBankAccountId }}">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <x-date-input name="date" value="{{ old('date', $td['date'] ?? now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required />
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" id="bs_entry_amount" step="0.01" min="0.01" value="{{ old('amount', $td['amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', $td['description'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" placeholder="e.g., Deposit / purchase for 3 Faul Street">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @include('partials.vendor-select', [
                            'vendors' => $vendors,
                            'selected' => old('vendor_id', $td['vendor_id'] ?? null),
                            'selectClass' => 'mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs',
                        ])
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Number <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="invoice_number" value="{{ old('invoice_number', $td['invoice_number'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs">
                            @error('invoice_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Entry type</label>
                            <select
                                name="transaction_type"
                                id="transaction_type"
                                required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs"
                                data-tomselect
                                data-tomselect-search="false"
                            >
                                <option value="">Select type</option>
                                @foreach (\App\Models\Transaction::balanceSheetTypeSelectGroups() as $groupLabel => $types)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($types as $value => $label)
                                            @php
                                                $optionDirection = array_key_exists($value, \App\Models\Transaction::$incomeTypes)
                                                    ? 'income'
                                                    : 'expense';
                                            @endphp
                                            <option
                                                value="{{ $value }}"
                                                data-direction="{{ $optionDirection }}"
                                                @selected((string) $oldType === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <x-tom-select name="asset_id" class="mt-1 rounded-md shadow-xs">
                                <option value="">None — entity only</option>
                                @foreach ($businessEntity->assets()->orderBy('name')->get() as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) old('asset_id', $td['asset_id'] ?? '') === (string) $asset->id)>{{ $asset->name }}</option>
                                @endforeach
                            </x-tom-select>
                            @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @include('partials.transaction-marker-fields')
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">GST (10%)</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="" class="rounded-full border-gray-300 text-indigo-600" {{ $createGstBasis === '' || $createGstBasis === null ? 'checked' : '' }}> No GST
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="inclusive" class="rounded-full border-gray-300 text-indigo-600" {{ $createGstBasis === 'inclusive' ? 'checked' : '' }}> GST inclusive — amount includes 10%
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="gst_basis" value="exclusive" class="rounded-full border-gray-300 text-indigo-600" {{ $createGstBasis === 'exclusive' ? 'checked' : '' }}> GST exclusive — 10% on top
                                </label>
                            </div>
                            @error('gst_basis') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST amount <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" name="gst_amount" id="bs_entry_gst_amount" step="0.01" value="{{ old('gst_amount', $td['gst_amount'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booking entity</label>
                            <p class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                {{ $businessEntity->legal_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supporting document <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-gray-700 dark:file:text-blue-300" accept="{{ config('documents.transaction_file_accept') }}">
                            @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document name</label>
                            <input type="text" name="document_name" value="{{ old('document_name') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs">
                            @error('document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Funding (non-bank)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment date</label>
                                <x-date-input name="paid_at" value="{{ old('paid_at', $td['paid_at'] ?? '') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" />
                                @error('paid_at') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment method</label>
                                <select name="payment_method" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs">
                                    <option value="">Select method</option>
                                    @foreach (\App\Models\Transaction::$paymentMethods as $val => $lbl)
                                        <option value="{{ $val }}" @selected(old('payment_method', $td['payment_method'] ?? '') == $val)>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment channel</label>
                                <select name="payment_channel" id="payment_channel" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs" required>
                                    @foreach (\App\Models\Transaction::nonBankPaymentChannels() as $value => $label)
                                        <option value="{{ $value }}" @selected($oldChannel === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_channel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @php
                                $pbSplit = \App\Support\TransactionPayerResolver::splitStoredForForm($td['paid_by'] ?? null);
                                $typeDirection = array_key_exists($oldType, \App\Models\Transaction::$incomeTypes) ? 'income' : 'expense';
                            @endphp
                            @include('partials.transaction-paid-by-fields', [
                                'payerOptions' => $payerOptions,
                                'paidBySelect' => $pbSplit['select'],
                                'paidByOther' => $pbSplit['other'],
                                'paidByLabelText' => $typeDirection === 'income' ? 'Received By' : 'Paid By',
                                'hideBankAccountField' => true,
                            ])
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment receipt <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="file" name="payment_document" class="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 dark:file:bg-gray-700 dark:file:text-green-300" accept="{{ config('documents.transaction_file_accept') }}">
                                @error('payment_document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment receipt name</label>
                                <input type="text" name="payment_document_name" value="{{ old('payment_document_name') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-xs">
                                @error('payment_document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="receipt_path" value="{{ old('receipt_path', $td['receipt_path'] ?? '') }}">

                    <div class="flex gap-4 mt-6">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-md shadow-xs transition duration-200 font-medium">
                            Save balance sheet entry
                        </button>
                        @php
                            $cancelHref = match ($returnTo) {
                                'bank-account' => route('bank-accounts.index'),
                                'transactions-page' => $returnBankAccountId
                                    ? route('bank-accounts.transactions.page', array_filter([
                                        'bankAccount' => $returnBankAccountId,
                                        'business_entity_id' => $returnBusinessEntityId,
                                    ]))
                                    : route('business-entities.show', $businessEntity),
                                'entity' => route('business-entities.show', $businessEntity),
                                default => route('business-entities.show', $businessEntity),
                            };
                        @endphp
                        <a href="{{ $cancelHref }}" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-md shadow-xs transition duration-200 font-medium">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('balance-sheet-entry-form');
            const transactionTypeSelect = document.getElementById('transaction_type');
            const paidByLabel = document.getElementById('paid_by_label');
            const paidBySelect = document.getElementById('paid_by_select');
            const paidByOtherWrap = document.getElementById('paid_by_other_wrap');

            function updatePaidByLabel() {
                if (!paidByLabel || !transactionTypeSelect) return;
                const opt = transactionTypeSelect.options[transactionTypeSelect.selectedIndex];
                const direction = opt?.dataset?.direction || 'expense';
                paidByLabel.textContent = direction === 'income' ? 'Received By' : 'Paid By';
            }

            transactionTypeSelect?.addEventListener('change', updatePaidByLabel);
            updatePaidByLabel();

            function syncPaidByOther() {
                if (!paidBySelect || !paidByOtherWrap) return;
                paidByOtherWrap.classList.toggle('hidden', paidBySelect.value !== 'other');
            }
            paidBySelect?.addEventListener('change', syncPaidByOther);
            syncPaidByOther();

            (function gstCalc() {
                const amtEl = document.getElementById('bs_entry_amount');
                const gstEl = document.getElementById('bs_entry_gst_amount');
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
