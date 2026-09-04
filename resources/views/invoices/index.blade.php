<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                @isset($businessEntity)
                    Invoices — {{ $businessEntity->legal_name }}
                @else
                    All invoices
                @endisset
            </h2>
            @isset($businessEntity)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('business-entities.invoices.index', [$businessEntity, 'receivable' => 1]) }}"
                       class="inline-flex items-center px-4 py-2 {{ !empty($receivableOnly) ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' }} rounded-lg text-sm font-medium transition-colors">
                        Rent receivable (unpaid AR)
                    </a>
                    <a href="{{ route('business-entities.invoices.create', $businessEntity) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-xs transition-colors">
                        New invoice
                    </a>
                </div>
            @endisset
        </div>
    </x-slot>

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
        @endif

        @php
            $invoiceRoute = isset($businessEntity) ? 'business-entities.invoices.index' : 'invoices.index';
            $invoiceRouteParams = isset($businessEntity) ? ['business_entity' => $businessEntity->id] : [];
            $filterQuery = array_filter([
                'status' => $statusFilter ?? null,
                'receivable' => !empty($receivableOnly) ? 1 : null,
                'asset_id' => $assetIdFilter ?? null,
                'lease_id' => $leaseIdFilter ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        @endphp

        <form method="GET" action="{{ isset($businessEntity) ? route('business-entities.invoices.index', $businessEntity) : route('invoices.index') }}"
              class="mb-4 flex flex-wrap items-end gap-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 p-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm" @disabled(!empty($receivableOnly))>
                    <option value="">All</option>
                    @foreach (\App\Models\Invoice::$statuses as $code => $label)
                        <option value="{{ $code }}" @selected(($statusFilter ?? '') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 pb-1">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="receivable" value="1" @checked(!empty($receivableOnly)) class="rounded-sm border-gray-300" />
                    Unpaid AR only
                </label>
            </div>
            @isset($businessEntity)
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Asset</label>
                    <select name="asset_id" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm min-w-[12rem]">
                        <option value="">All assets</option>
                        @foreach ($filterAssets ?? [] as $asset)
                            <option value="{{ $asset->id }}" @selected((int) ($assetIdFilter ?? 0) === (int) $asset->id)>{{ $asset->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg">Filter</button>
            @if (!empty($filterQuery))
                <a href="{{ isset($businessEntity) ? route('business-entities.invoices.index', $businessEntity) : route('invoices.index') }}"
                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline pb-1">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <x-sortable-table-header :label="__('Number')" column="number" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            @unless(isset($businessEntity))
                                <x-sortable-table-header :label="__('Entity')" column="entity" :sort="$tableSort->column" :order="$tableSort->order" route="invoices.index" :route-params="$filterQuery" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            @endunless
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('Asset') }}</th>
                            <x-sortable-table-header :label="__('Customer')" column="customer" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Issue')" column="issue" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Due</th>
                            <x-sortable-table-header :label="__('Total')" column="total" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" align="right" class="px-4 py-3 text-right text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <x-sortable-table-header :label="__('Status')" column="status" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300" />
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($invoices as $inv)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                                @unless(isset($businessEntity))
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $inv->businessEntity->legal_name ?? '—' }}</td>
                                @endunless
                                <td class="px-4 py-3">
                                    @if ($inv->asset_id && ($beId = $inv->business_entity_id))
                                        <a href="{{ route('business-entities.assets.show', [$beId, $inv->asset_id]) }}#tab_invoices" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $inv->asset?->name ?? 'Property #'.$inv->asset_id }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $inv->customer_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $inv->issue_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap {{ $inv->status === 'approved' && $inv->due_date && $inv->due_date->isPast() ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                                    {{ $inv->due_date ? $inv->due_date->format('Y-m-d') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($inv->total_amount, 2) }}</td>
                                <td class="px-4 py-3">{{ ucfirst($inv->status) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('business-entities.invoices.show', [$inv->business_entity_id, $inv]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($businessEntity) ? 8 : 9 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
