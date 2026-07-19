{{--
    Multi-line transaction rows for the Dashboard add-transaction form.
    Expects Alpine parent with: lines, addLine, removeLine, filteredTypes, showRelatedEntity, recalcGst, totals, canAddLine
    And config props already on the Alpine component (typeGroups, vendors, relatedEntities, maxLines).
--}}
@php
    $txnLabel = $txnLabel ?? 'block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5';
    $txnInput = $txnInput ?? 'block w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/80 px-3 py-2.5 text-sm text-gray-900 dark:text-gray-100 shadow-xs placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:focus:border-blue-400 transition-colors';
@endphp

<section class="{{ $txnSection ?? 'rounded-xl border border-gray-100 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-900/30 p-5 space-y-4' }}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-1">
        <div class="flex items-center gap-2">
            <x-lucide-receipt class="w-4 h-4 text-indigo-500 dark:text-indigo-400" />
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Transaction lines</h4>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Shared entity, asset, date, and payment apply to every line.
        </p>
    </div>

    <div class="space-y-4">
        <template x-for="(line, index) in lines" :key="line._key">
            <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/50 p-4 space-y-4 relative">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                          x-text="'Line ' + (index + 1)"></span>
                    <button type="button"
                            class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition-colors disabled:opacity-40 disabled:pointer-events-none"
                            :disabled="lines.length <= 1"
                            @click="removeLine(index)"
                            aria-label="Remove line">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>

                {{-- Direction --}}
                <div class="rounded-xl bg-gray-100/80 dark:bg-gray-900/50 p-1 grid grid-cols-2 gap-1 max-w-md">
                    <label class="cursor-pointer">
                        <input type="radio" class="sr-only" value="expense"
                               :name="'lines[' + index + '][direction]'"
                               x-model="line.direction"
                               @change="onDirectionChange(index)">
                        <div class="flex items-center justify-center gap-1.5 rounded-lg py-2 px-3 text-xs font-semibold transition-all"
                             :class="line.direction === 'expense'
                                ? 'bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 shadow-sm ring-1 ring-red-200 dark:ring-red-900/50'
                                : 'text-gray-600 dark:text-gray-400'">
                            <x-lucide-arrow-down-right class="w-3.5 h-3.5" />
                            Expense (−)
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" class="sr-only" value="income"
                               :name="'lines[' + index + '][direction]'"
                               x-model="line.direction"
                               @change="onDirectionChange(index)">
                        <div class="flex items-center justify-center gap-1.5 rounded-lg py-2 px-3 text-xs font-semibold transition-all"
                             :class="line.direction === 'income'
                                ? 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 shadow-sm ring-1 ring-emerald-200 dark:ring-emerald-900/50'
                                : 'text-gray-600 dark:text-gray-400'">
                            <x-lucide-arrow-up-right class="w-3.5 h-3.5" />
                            Income (+)
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $txnLabel }}">Amount</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-base font-semibold text-gray-400">$</span>
                            <input type="number" step="0.01" required
                                   :name="'lines[' + index + '][amount]'"
                                   x-model="line.amount"
                                   @input="recalcGst(index)"
                                   class="{{ $txnInput }} pl-8 text-lg font-semibold tabular-nums"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="{{ $txnLabel }}">Description</label>
                        <input type="text"
                               :name="'lines[' + index + '][description]'"
                               x-model="line.description"
                               class="{{ $txnInput }}"
                               placeholder="What was this line for?">
                    </div>

                    <div>
                        <label class="{{ $txnLabel }}">Transaction Type</label>
                        <select :name="'lines[' + index + '][transaction_type]'"
                                x-model="line.transaction_type"
                                required
                                class="{{ $txnInput }}"
                                @change="if (!showRelatedEntity(line.transaction_type)) line.related_entity_id = ''">
                            <option value="">Select Type</option>
                            <template x-for="opt in flatTypes(line.direction)" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $txnLabel }}">Vendor <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                        <select :name="'lines[' + index + '][vendor_id]'"
                                x-model="line.vendor_id"
                                class="{{ $txnInput }}">
                            <option value="">Select vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option :value="String(vendor.id)" x-text="vendor.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="{{ $txnLabel }}">Invoice Number <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                        <input type="text"
                               :name="'lines[' + index + '][invoice_number]'"
                               x-model="line.invoice_number"
                               class="{{ $txnInput }}"
                               placeholder="e.g., INV-0042">
                    </div>

                    <div class="md:col-span-2 lg:col-span-3" x-show="showRelatedEntity(line.transaction_type)" x-cloak>
                        <label class="{{ $txnLabel }}">Related Entity</label>
                        <select :name="'lines[' + index + '][related_entity_id]'"
                                x-model="line.related_entity_id"
                                :required="showRelatedEntity(line.transaction_type)"
                                class="{{ $txnInput }}">
                            <option value="">Select Related Entity</option>
                            <template x-for="entity in relatedEntities" :key="entity.id">
                                <option :value="String(entity.id)" x-text="entity.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- GST per line --}}
                <div class="space-y-3 pt-1 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">GST (10%)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" class="sr-only" value=""
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-lg border px-3 py-2.5 text-sm transition-all"
                                 :class="(line.gst_basis === '' || line.gst_basis === null)
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-500/30'
                                    : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60'">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">No GST</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" class="sr-only" value="inclusive"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-lg border px-3 py-2.5 text-sm transition-all"
                                 :class="line.gst_basis === 'inclusive'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-500/30'
                                    : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60'">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">Inclusive</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" class="sr-only" value="exclusive"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-lg border px-3 py-2.5 text-sm transition-all"
                                 :class="line.gst_basis === 'exclusive'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-500/30'
                                    : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900/60'">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">Exclusive</span>
                            </div>
                        </label>
                    </div>
                    <div class="max-w-xs">
                        <label class="{{ $txnLabel }}">GST amount <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-gray-400">$</span>
                            <input type="number" step="0.01"
                                   :name="'lines[' + index + '][gst_amount]'"
                                   x-model="line.gst_amount"
                                   @input="line.gstTouched = true"
                                   class="{{ $txnInput }} pl-8 tabular-nums">
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
        <button type="button"
                @click="addLine()"
                :disabled="!canAddLine"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-dashed border-indigo-300 dark:border-indigo-700 bg-indigo-50/50 dark:bg-indigo-900/20 px-4 py-2.5 text-sm font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors disabled:opacity-50 disabled:pointer-events-none">
            <x-lucide-plus class="w-4 h-4" />
            Add line
        </button>
        <div class="text-sm text-gray-600 dark:text-gray-300 tabular-nums space-x-3">
            <span>Income <strong class="text-emerald-600 dark:text-emerald-400" x-text="'$' + totals.income.toFixed(2)"></strong></span>
            <span>Expense <strong class="text-red-600 dark:text-red-400" x-text="'$' + totals.expense.toFixed(2)"></strong></span>
            <span>Net <strong class="text-gray-900 dark:text-white" x-text="'$' + totals.net.toFixed(2)"></strong></span>
        </div>
    </div>
</section>
