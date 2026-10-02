{{--
    Allocation lines for the Dashboard add-transaction form (one bank-facing header + N P&L lines).
    Expects Alpine parent with: lines, addLine, removeLine, accountsFor, showRelatedEntity, recalcGst, totals, canAddLine
--}}
@php
    $txnLabel = $txnLabel ?? 'block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5';
    $txnInput = $txnInput ?? 'block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-2xs transition-colors placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-indigo-400';
    $hideExclusiveGst = $hideExclusiveGst ?? false;
    $lockLineAmount = $lockLineAmount ?? false;
@endphp

<section class="{{ $txnSection ?? 'rounded-2xl border border-gray-200/90 dark:border-gray-700/80 bg-white dark:bg-gray-800/95 p-5 sm:p-6 shadow-xs space-y-5' }}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/60">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 ring-1 ring-indigo-500/10">
                <x-lucide-receipt class="w-4 h-4" />
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Allocations</h4>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 ring-1 ring-indigo-500/20"
                          x-text="lines.length + (lines.length === 1 ? ' line' : ' lines')"></span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Multi-line items shared across the same entity and settlement date.
                </p>
            </div>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Net total matches bank remittance
        </div>
    </div>

    <div class="space-y-4">
        <template x-for="(line, index) in lines" :key="line._key">
            <div class="rounded-xl border border-gray-200/90 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-900/40 p-4 sm:p-5 space-y-4 relative transition-all">
                {{-- Line Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-200/70 dark:border-gray-700/60">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200/80 dark:border-gray-700 shadow-2xs"
                              x-text="'Line ' + (index + 1)"></span>

                        {{-- Direction Toggle --}}
                        <div class="inline-flex rounded-lg bg-gray-200/70 dark:bg-gray-800 p-0.5 border border-gray-200/60 dark:border-gray-700/60">
                            <label class="cursor-pointer">
                                <input type="radio" class="sr-only" value="expense"
                                       :name="'lines[' + index + '][direction]'"
                                       x-model="line.direction"
                                       @change="onDirectionChange(index)">
                                <div class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-all"
                                     :class="line.direction === 'expense'
                                        ? 'bg-white dark:bg-gray-700 text-rose-600 dark:text-rose-400 shadow-xs ring-1 ring-black/5 dark:ring-white/10'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                                    <x-lucide-arrow-down-right class="w-3.5 h-3.5" />
                                    Expense (−)
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" class="sr-only" value="income"
                                       :name="'lines[' + index + '][direction]'"
                                       x-model="line.direction"
                                       @change="onDirectionChange(index)">
                                <div class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-all"
                                     :class="line.direction === 'income'
                                        ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-xs ring-1 ring-black/5 dark:ring-white/10'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                                    <x-lucide-arrow-up-right class="w-3.5 h-3.5" />
                                    Income (+)
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="button"
                            class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                            :disabled="lines.length <= 1"
                            @click="removeLine(index)"
                            title="Remove allocation line"
                            aria-label="Remove line">
                        <x-lucide-trash-2 class="w-4 h-4" />
                    </button>
                </div>

                {{-- Fields Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $txnLabel }}">
                            Amount
                            @if ($lockLineAmount)
                                <span class="ml-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-normal text-amber-900 dark:bg-amber-950/60 dark:text-amber-200">Locked</span>
                            @endif
                        </label>
                        @if ($lockLineAmount)
                            <input type="hidden" :name="'lines[' + index + '][amount]'" x-model="line.amount">
                            <p class="{{ $txnInput }} pl-3 font-semibold tabular-nums text-gray-900 dark:text-gray-100" x-text="line.amount ? ('$' + parseFloat(line.amount).toFixed(2)) : '—'"></p>
                        @else
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-base font-semibold text-gray-400">$</span>
                                <input type="number" step="0.01" required
                                       :name="'lines[' + index + '][amount]'"
                                       x-model="line.amount"
                                       @input="recalcGst(index)"
                                       class="{{ $txnInput }} pl-8 font-semibold tabular-nums text-base"
                                       placeholder="0.00">
                            </div>
                        @endif
                    </div>

                    <div class="lg:col-span-2">
                        <label class="{{ $txnLabel }}">Description</label>
                        <input type="text"
                               :name="'lines[' + index + '][description]'"
                               x-model="line.description"
                               class="{{ $txnInput }}"
                               placeholder="What was this allocation for?">
                    </div>

                    <div>
                        <label class="{{ $txnLabel }}">Chart of Account</label>
                        <select :name="'lines[' + index + '][chart_of_account_id]'"
                                x-model="line.chart_of_account_id"
                                x-effect="syncAccountOptions($el, line.direction, line.chart_of_account_id)"
                                required
                                class="{{ $txnInput }}"
                                @change="if (!showRelatedEntity(line)) line.related_entity_id = ''">
                            <option value="">Select account</option>
                            {{-- Real <option> nodes, not <template x-for> inside <select> (invalid HTML; browsers drop those options). --}}
                            @foreach (($dashboardChartAccounts ?? collect()) as $account)
                                <option
                                    value="{{ $account['id'] }}"
                                    data-code="{{ $account['code'] }}"
                                    data-direction="{{ $account['direction'] }}"
                                >{{ $account['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            All active chart accounts. Direction toggles cash sign.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $txnLabel }}">Vendor <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                        <select :name="'lines[' + index + '][vendor_id]'"
                                x-model="line.vendor_id"
                                class="{{ $txnInput }}">
                            <option value="">Select vendor</option>
                            @foreach (($vendors ?? collect()) as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
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

                    <div class="md:col-span-2 lg:col-span-3" x-show="showRelatedEntity(line)" x-cloak>
                        <label class="{{ $txnLabel }}">Related Entity</label>
                        <select :name="'lines[' + index + '][related_entity_id]'"
                                x-model="line.related_entity_id"
                                :required="showRelatedEntity(line)"
                                class="{{ $txnInput }}">
                            <option value="">Select Related Entity</option>
                            @foreach (($dashboardRelatedEntitiesJson ?? collect()) as $entity)
                                <option value="{{ $entity['id'] }}">{{ $entity['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- GST per line --}}
                <div class="space-y-3 pt-3 border-t border-gray-200/70 dark:border-gray-700/60">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">GST (10%)</p>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">Australian GST treatment</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <label class="cursor-pointer group">
                            <input type="radio" class="sr-only" value="none"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-xl border p-2.5 sm:p-3 text-center transition-all flex flex-col justify-center items-center gap-0.5"
                                 :class="line.gst_basis === 'none'
                                    ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20 shadow-2xs font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'">
                                <span class="text-xs sm:text-sm font-semibold">No GST</span>
                                <span class="text-[11px] text-gray-400 dark:text-gray-500 font-normal">GST-free / BAS-free</span>
                            </div>
                        </label>

                        <label class="cursor-pointer group">
                            <input type="radio" class="sr-only" value="inclusive"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-xl border p-2.5 sm:p-3 text-center transition-all flex flex-col justify-center items-center gap-0.5"
                                 :class="line.gst_basis === 'inclusive'
                                    ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20 shadow-2xs font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'">
                                <span class="text-xs sm:text-sm font-semibold">Inclusive</span>
                                <span class="text-[11px] text-gray-400 dark:text-gray-500 font-normal">10% included</span>
                            </div>
                        </label>

                        @unless ($hideExclusiveGst)
                        <label class="cursor-pointer group">
                            <input type="radio" class="sr-only" value="exclusive"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = false; recalcGst(index)">
                            <div class="h-full rounded-xl border p-2.5 sm:p-3 text-center transition-all flex flex-col justify-center items-center gap-0.5"
                                 :class="line.gst_basis === 'exclusive'
                                    ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20 shadow-2xs font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'">
                                <span class="text-xs sm:text-sm font-semibold">Exclusive</span>
                                <span class="text-[11px] text-gray-400 dark:text-gray-500 font-normal">+10% on top</span>
                            </div>
                        </label>
                        @endunless

                        <label class="cursor-pointer group">
                            <input type="radio" class="sr-only" value="manual"
                                   :name="'lines[' + index + '][gst_basis]'"
                                   x-model="line.gst_basis"
                                   @change="line.gstTouched = true; line.gst_amount = ''; recalcGst(index)">
                            <div class="h-full rounded-xl border p-2.5 sm:p-3 text-center transition-all flex flex-col justify-center items-center gap-0.5"
                                 :class="line.gst_basis === 'manual'
                                    ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20 shadow-2xs font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'">
                                <span class="text-xs sm:text-sm font-semibold">Manual</span>
                                <span class="text-[11px] text-gray-400 dark:text-gray-500 font-normal">Mixed GST invoices</span>
                            </div>
                        </label>
                    </div>

                    <div class="max-w-xs space-y-1.5" x-show="line.gst_basis !== 'none'" x-cloak>
                        <label class="{{ $txnLabel }}">
                            GST Amount
                            <span class="normal-case font-normal text-gray-400"
                                  x-text="line.gst_basis === 'manual' ? '(required)' : '(calculated)'"></span>
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-medium text-gray-400">$</span>
                            <input type="number" step="0.01"
                                   :name="'lines[' + index + '][gst_amount]'"
                                   x-model="line.gst_amount"
                                   @input="line.gstTouched = true"
                                   :required="line.gst_basis === 'manual'"
                                   class="{{ $txnInput }} pl-8 tabular-nums font-medium"
                                   placeholder="0.00">
                        </div>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400" x-show="line.gst_basis === 'manual'" x-cloak>
                            Enter the invoice total GST when some lines are GST-free or mixed rates.
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
        <button type="button"
                @click="addLine()"
                :disabled="!canAddLine"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-dashed border-indigo-300 dark:border-indigo-700/80 bg-indigo-50/50 dark:bg-indigo-950/30 px-4 py-2.5 text-sm font-semibold text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-all hover:border-indigo-400 disabled:opacity-50 disabled:pointer-events-none">
            <x-lucide-plus class="w-4 h-4" />
            Add allocation line
        </button>
        <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm font-medium tabular-nums">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 px-3 py-1.5 border border-emerald-200/60 dark:border-emerald-800/40">
                <x-lucide-arrow-up-right class="w-3.5 h-3.5" />
                Income <strong class="font-bold" x-text="'$' + totals.income.toFixed(2)"></strong>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 px-3 py-1.5 border border-rose-200/60 dark:border-rose-800/40">
                <x-lucide-arrow-down-right class="w-3.5 h-3.5" />
                Expense <strong class="font-bold" x-text="'$' + totals.expense.toFixed(2)"></strong>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 px-3 py-1.5 shadow-2xs font-semibold">
                Net to bank: <strong class="font-bold" x-text="'$' + totals.net.toFixed(2)"></strong>
            </span>
        </div>
    </div>
</section>
