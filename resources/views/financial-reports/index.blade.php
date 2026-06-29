<x-app-layout>
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900">Financial reports</h1>
            <p class="text-sm text-gray-500 mt-1">Pick a report, then choose all reporting entities or a custom set.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if($businessEntities->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                No reporting entities are set up yet. Entity-scoped GL reports need at least one operating entity.
                You can still open <a href="{{ route('financial-reports.car-register') }}" class="font-medium underline">Car Register</a>,
                <a href="{{ route('financial-reports.commitments') }}" class="font-medium underline">Future commitments</a>,
                or <a href="{{ route('commitments.index') }}" class="font-medium underline">manage commitments</a>.
            </div>
        @endif

        {{-- Scope: shared by entity-scoped report actions --}}
        <form id="financial-reports-hub-form" method="get" action="#" class="space-y-8"
              x-init="
                  const syncHubEntitySelects = () => {
                      document.querySelectorAll('#financial-reports-hub-form [data-report-entity-scope-picker]').forEach((picker) => {
                          const scope = picker.querySelector('input[name=scope]:checked')?.value
                              ?? picker.querySelector('select[name=scope]')?.value
                              ?? 'all';
                          const sel = picker.querySelector('select[name=\'entity_ids[]\']');
                          window.setSelectDisabled?.(sel, scope === 'all');
                      });
                  };
                  window.addEventListener('pageshow', syncHubEntitySelects);
              ">

            {{-- Report types (fixed on top) --}}
            <div class="sticky top-0 z-20 -mx-4 px-4 sm:mx-0 sm:px-0 pt-2 pb-3 bg-gray-50/95 backdrop-blur-xs border-b border-gray-200/80 sm:border-0 sm:bg-transparent sm:backdrop-blur-none">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Reports</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">

                        <button type="submit" formaction="{{ route('financial-reports.entity-summary') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-amber-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-amber-50 flex items-center justify-center group-hover:bg-amber-100">
                                    <x-lucide-align-justify class="h-5 w-5 text-amber-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-amber-700">Entity summary</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Cross-entity sales, tax, profit &amp; loans</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.account-transactions') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow-xs transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-blue-50 flex items-center justify-center group-hover:bg-blue-100">
                                    <x-lucide-clipboard class="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Account transactions</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Line-level movements by account</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.balance-sheet') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-indigo-400 hover:shadow-xs transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100">
                                    <x-lucide-calculator class="h-5 w-5 text-indigo-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700">Balance sheet</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Assets, liabilities and equity</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.profit-loss') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-green-400 hover:shadow-xs transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-green-50 flex items-center justify-center group-hover:bg-green-100">
                                    <x-lucide-bar-chart-3 class="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-green-700">Profit &amp; loss</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Income and expenses for a period</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.cash-flow') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-purple-400 hover:shadow-xs transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-purple-50 flex items-center justify-center group-hover:bg-purple-100">
                                    <x-lucide-trending-up class="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700">Cash flow</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Operating, investing and financing</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.tracking-categories') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-orange-400 hover:shadow-xs transition-all">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-orange-50 flex items-center justify-center group-hover:bg-orange-100">
                                    <x-lucide-archive class="h-5 w-5 text-orange-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-orange-700">Tracking categories</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Owner and property breakdowns</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('portfolio.index') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-teal-50 flex items-center justify-center group-hover:bg-teal-100">
                                    <x-lucide-house class="h-5 w-5 text-teal-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-teal-700">Property portfolio</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Per-property P&amp;L and yield vs acquisition cost</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.asset-summary') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-emerald-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-emerald-50 flex items-center justify-center group-hover:bg-emerald-100">
                                    <x-lucide-table class="h-5 w-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-emerald-700">Asset summary</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Property register — ownership, tenants &amp; loan details</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.car-register') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-sky-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-sky-50 flex items-center justify-center group-hover:bg-sky-100">
                                    <x-lucide-truck class="h-5 w-5 text-sky-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-sky-700">Car Register</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Rego, insurance &amp; service due dates for all cars</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.commitments') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-rose-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-rose-50 flex items-center justify-center group-hover:bg-rose-100">
                                    <x-lucide-file-text class="h-5 w-5 text-rose-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-rose-700">Future commitments</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Pending contracts, deposits &amp; settlement dates</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.compliance-gaps') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-violet-400 hover:shadow-xs transition-all w-full">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-9 h-9 rounded-md bg-violet-50 flex items-center justify-center group-hover:bg-violet-100">
                                    <x-lucide-shield-check class="h-5 w-5 text-violet-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-violet-700">Compliance gaps</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Entities missing ITR for selected FY</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                @if($businessEntities->isNotEmpty())
                <div class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-xs">
                    <x-report-entity-scope-picker
                        :business-entities="$businessEntities"
                        layout="card"
                        scope-style="radio"
                    />
                </div>
                @endif
            </form>
    </div>
</x-app-layout>
