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
            <div class="text-center py-16 text-gray-400">
                <p class="text-sm">No reporting entities are set up yet.</p>
            </div>
        @else
            {{-- Scope: shared by all report actions --}}
            <form id="financial-reports-hub-form" method="get" action="#" class="space-y-8"
                  x-data="{
                      scope: 'all',
                      validate(ev) {
                          const boxes = this.$el.querySelectorAll('input[name=\'entity_ids[]\']');
                          if (this.scope === 'all') {
                              boxes.forEach(cb => { cb.disabled = true; });
                              return true;
                          }
                          boxes.forEach(cb => { cb.disabled = false; });
                          const n = this.$el.querySelectorAll('input[name=\'entity_ids[]\']:checked').length;
                          if (n === 0) {
                              ev.preventDefault();
                              alert('Select at least one entity, or choose “All reporting entities”.');
                              return false;
                          }
                          return true;
                      }
                  }"
                  @submit="validate($event)">

                {{-- Report types (fixed on top) --}}
                <div class="sticky top-0 z-20 -mx-4 px-4 sm:mx-0 sm:px-0 pt-2 pb-3 bg-gray-50/95 backdrop-blur-sm border-b border-gray-200/80 sm:border-0 sm:bg-transparent sm:backdrop-blur-none">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Reports</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">

                        <button type="submit" formaction="{{ route('financial-reports.account-transactions') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow-sm transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-blue-50 flex items-center justify-center group-hover:bg-blue-100">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Account transactions</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Line-level movements by account</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.balance-sheet') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-indigo-400 hover:shadow-sm transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700">Balance sheet</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Assets, liabilities and equity</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.profit-loss') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-green-400 hover:shadow-sm transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-green-50 flex items-center justify-center group-hover:bg-green-100">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-green-700">Profit &amp; loss</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Income and expenses for a period</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.cash-flow') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-purple-400 hover:shadow-sm transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-purple-50 flex items-center justify-center group-hover:bg-purple-100">
                                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-purple-700">Cash flow</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Operating, investing and financing</p>
                                </div>
                            </div>
                        </button>

                        <button type="submit" formaction="{{ route('financial-reports.tracking-categories') }}"
                                form="financial-reports-hub-form"
                                class="group text-left bg-white border border-gray-200 rounded-lg p-4 hover:border-orange-400 hover:shadow-sm transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-9 h-9 rounded-md bg-orange-50 flex items-center justify-center group-hover:bg-orange-100">
                                    <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 group-hover:text-orange-700">Tracking categories</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Owner and property breakdowns</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Entity scope --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Entity scope</h2>

                    <div class="space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="radio" name="scope" value="all" x-model="scope" checked
                                   class="mt-1 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-gray-900">All reporting entities</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Consolidated figures across every entity that is included in reports ({{ $businessEntities->count() }}).</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="radio" name="scope" value="selected" x-model="scope"
                                   class="mt-1 h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="text-sm font-medium text-gray-800 group-hover:text-gray-900">Selected entities only</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Tick one or more entities, then open a report above.</span>
                            </span>
                        </label>

                        <div class="pl-7 sm:pl-8 border-l-2 border-gray-100 ml-1.5 space-y-2"
                             :class="scope === 'selected' ? '' : 'opacity-50 pointer-events-none'">
                            <p class="text-xs font-medium text-gray-500 mb-2">Entities</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-52 overflow-y-auto pr-1">
                                @foreach($businessEntities as $entity)
                                    <label class="flex items-center gap-2 rounded-md border border-gray-100 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="entity_ids[]" value="{{ $entity->id }}"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700 truncate">{{ $entity->legal_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
