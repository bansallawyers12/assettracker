@if ($unlinkedGroups->isNotEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-5 py-5 dark:border-amber-800 dark:bg-amber-950/20">
        <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-amber-900 dark:text-amber-200">{{ __('Resolve unlinked vendor names') }}</h2>
                <p class="mt-1 max-w-2xl text-sm text-amber-900/80 dark:text-amber-200/80">
                    {{ __('These transactions have vendor name text but are not linked to a vendor record yet. Link them once from here — after that, editing the vendor updates them everywhere.') }}
                </p>
            </div>
            @can('create', \App\Models\BusinessEntity::class)
                <div class="flex shrink-0 flex-wrap gap-2">
                    <form method="POST" action="{{ route('vendors.auto-link-all') }}" data-vendor-bulk-form>
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                            <x-lucide-sparkles class="h-4 w-4" aria-hidden="true" />
                            {{ __('Auto-link and create') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('vendors.sync-all-names') }}" data-vendor-bulk-form>
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <x-lucide-refresh-cw class="h-4 w-4" aria-hidden="true" />
                            {{ __('Refresh linked names') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="overflow-x-auto rounded-lg bg-white ring-1 ring-amber-200 dark:bg-gray-900 dark:ring-amber-900">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <x-sortable-table-header :label="__('Vendor name on transaction')" column="label" :sort="$unlinkedSort->column" :order="$unlinkedSort->order" route="vendors.index" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300" />
                        <x-sortable-table-header :label="__('Count')" column="count" :sort="$unlinkedSort->column" :order="$unlinkedSort->order" route="vendors.index" align="right" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300" />
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">{{ __('Link to vendor') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($unlinkedGroups as $group)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $group->label }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ (int) $group->transaction_count }}</td>
                            <td class="px-4 py-3">
                                @can('create', \App\Models\BusinessEntity::class)
                                    <form method="POST" action="{{ route('vendors.resolve-unlinked') }}" class="flex flex-wrap items-center gap-2" data-vendor-bulk-form>
                                        @csrf
                                        <input type="hidden" name="vendor_name_label" value="{{ $group->label }}">
                                        <select name="vendor_id" required
                                                class="min-w-[12rem] rounded-md border-gray-300 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                            <option value="">{{ __('Select vendor…') }}</option>
                                            @foreach ($vendors as $vendorOption)
                                                <option value="{{ $vendorOption->id }}" @selected(strcasecmp($vendorOption->name, $group->label) === 0)>
                                                    {{ $vendorOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                            <x-lucide-link class="h-3.5 w-3.5" aria-hidden="true" />
                                            {{ __('Link') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Read-only') }}</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
