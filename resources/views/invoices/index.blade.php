<x-app-layout>
    @php
        $invoiceRoute = isset($businessEntity) ? 'business-entities.invoices.index' : 'invoices.index';
        $invoiceRouteParams = isset($businessEntity) ? ['business_entity' => $businessEntity->id] : [];
        $filterQuery = array_filter([
            'status' => $statusFilter ?? null,
            'receivable' => !empty($receivableOnly) ? 1 : null,
            'asset_id' => $assetIdFilter ?? null,
            'lease_id' => $leaseIdFilter ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        $statusBadge = function (string $status): string {
            return match ($status) {
                'draft' => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
                'approved' => 'bg-sky-100 text-sky-900 ring-sky-300 dark:bg-sky-950/60 dark:text-sky-100 dark:ring-sky-700',
                'paid' => 'bg-emerald-100 text-emerald-900 ring-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-100 dark:ring-emerald-700',
                'void' => 'bg-rose-100 text-rose-900 ring-rose-300 dark:bg-rose-950/60 dark:text-rose-100 dark:ring-rose-700',
                default => 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
            };
        };
        $colCount = isset($businessEntity) ? 8 : 9;
        $hasFilters = !empty($filterQuery);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                @isset($businessEntity)
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $businessEntity->legal_name }}
                    </p>
                @else
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Portfolio</p>
                @endisset
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Invoices</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $invoices->total() }} {{ \Illuminate\Support\Str::plural('invoice', $invoices->total()) }}
                    @if (!empty($receivableOnly))
                        · unpaid AR
                    @elseif (!empty($statusFilter))
                        · {{ \App\Models\Invoice::$statuses[$statusFilter] ?? $statusFilter }}
                    @endif
                </p>
            </div>
            @isset($businessEntity)
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('business-entities.invoices.index', [$businessEntity, 'receivable' => 1]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ !empty($receivableOnly) ? 'bg-amber-600 text-white shadow-xs hover:bg-amber-500' : 'border border-amber-200 bg-amber-50 text-amber-900 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200 dark:hover:bg-amber-900/40' }}">
                        <x-lucide-circle-dollar-sign class="h-4 w-4" aria-hidden="true" />
                        Unpaid AR
                    </a>
                    <a href="{{ route('business-entities.invoices.create', $businessEntity) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                        <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
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

        <form method="GET" action="{{ isset($businessEntity) ? route('business-entities.invoices.index', $businessEntity) : route('invoices.index') }}"
              class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Filters</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Narrow by status, receivable, or asset</p>
                </div>
                @if ($hasFilters)
                    <a href="{{ isset($businessEntity) ? route('business-entities.invoices.index', $businessEntity) : route('invoices.index') }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Clear all</a>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 {{ isset($businessEntity) ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} lg:items-end">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" @disabled(!empty($receivableOnly))>
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Invoice::$statuses as $code => $label)
                            <option value="{{ $code }}" @selected(($statusFilter ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @isset($businessEntity)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Asset</label>
                        <select name="asset_id" class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">All assets</option>
                            @foreach ($filterAssets ?? [] as $asset)
                                <option value="{{ $asset->id }}" @selected((int) ($assetIdFilter ?? 0) === (int) $asset->id)>{{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endisset
                <div class="flex items-end">
                    <label class="inline-flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                        <input type="checkbox" name="receivable" value="1" @checked(!empty($receivableOnly)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Unpaid AR only
                    </label>
                </div>
                <div class="flex items-end sm:col-span-2 {{ isset($businessEntity) ? 'lg:col-span-1' : '' }}">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        <x-lucide-filter class="h-4 w-4" aria-hidden="true" />
                        Apply filters
                    </button>
                </div>
            </div>
            @if (!empty($receivableOnly))
                <div class="border-t border-amber-100 bg-amber-50 px-4 py-2.5 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                    Showing unpaid rent receivable (approved, not paid).
                </div>
            @endif
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Invoice list</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($invoices->total() === 0)
                            No matching invoices
                        @else
                            Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}
                        @endif
                    </p>
                </div>
                @if ($hasFilters)
                    <div class="flex flex-wrap gap-1.5">
                        @if (!empty($statusFilter))
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">
                                Status: {{ \App\Models\Invoice::$statuses[$statusFilter] ?? $statusFilter }}
                            </span>
                        @endif
                        @if (!empty($receivableOnly))
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800">
                                Unpaid AR
                            </span>
                        @endif
                        @if (!empty($assetIdFilter) && isset($filterAssets))
                            @php $selectedAsset = collect($filterAssets)->firstWhere('id', (int) $assetIdFilter); @endphp
                            @if ($selectedAsset)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-800 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900">
                                    {{ $selectedAsset->name }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50/90 dark:bg-gray-800/60">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <x-sortable-table-header :label="__('Number')" column="number" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            @unless(isset($businessEntity))
                                <x-sortable-table-header :label="__('Entity')" column="entity" :sort="$tableSort->column" :order="$tableSort->order" route="invoices.index" :route-params="$filterQuery" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            @endunless
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Asset') }}</th>
                            <x-sortable-table-header :label="__('Customer')" column="customer" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            <x-sortable-table-header :label="__('Issue')" column="issue" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due</th>
                            <x-sortable-table-header :label="__('Total')" column="total" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" align="right" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            <x-sortable-table-header :label="__('Status')" column="status" :sort="$tableSort->column" :order="$tableSort->order" :route="$invoiceRoute" :route-params="array_merge($invoiceRouteParams, $filterQuery)" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" />
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($invoices as $inv)
                            @php
                                $isOverdue = in_array($inv->status, ['approved', 'partial'], true) && $inv->due_date && $inv->due_date->isPast();
                            @endphp
                            <tr class="transition-colors hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20">
                                <td class="px-4 py-3.5">
                                    <a href="{{ route('business-entities.invoices.show', [$inv->business_entity_id, $inv]) }}" class="font-mono text-xs font-semibold text-indigo-700 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                                        {{ $inv->invoice_number }}
                                    </a>
                                    @if ($inv->is_posted)
                                        <span class="mt-1 block text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Posted</span>
                                    @endif
                                </td>
                                @unless(isset($businessEntity))
                                    <td class="px-4 py-3.5 text-gray-700 dark:text-gray-300">{{ $inv->businessEntity->legal_name ?? '—' }}</td>
                                @endunless
                                <td class="px-4 py-3.5 max-w-[14rem]">
                                    @if ($inv->asset_id && ($beId = $inv->business_entity_id))
                                        <a href="{{ route('business-entities.assets.show', [$beId, $inv->asset_id]) }}#tab_invoices" class="line-clamp-2 text-sm text-indigo-600 hover:underline dark:text-indigo-400">{{ $inv->asset?->name ?? 'Property #'.$inv->asset_id }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 font-medium text-gray-900 dark:text-gray-100">{{ $inv->customer_name ?: '—' }}</td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $inv->issue_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3.5 whitespace-nowrap {{ $isOverdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-gray-600 dark:text-gray-300' }}">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}</span>
                                        @if ($isOverdue)
                                            <span class="inline-flex rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900">Overdue</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-semibold tabular-nums text-gray-900 dark:text-white">${{ number_format($inv->total_amount, 2) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusBadge($inv->status) }}">
                                        {{ \App\Models\Invoice::$statuses[$inv->status] ?? ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('business-entities.invoices.show', [$inv->business_entity_id, $inv]) }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                           title="View invoice"
                                           aria-label="View invoice {{ $inv->invoice_number }}">
                                            <x-lucide-eye class="h-4 w-4" aria-hidden="true" />
                                        </a>
                                        <a href="{{ route('business-entities.invoices.download', [$inv->business_entity_id, $inv]) }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
                                           title="Download / print invoice"
                                           aria-label="Download invoice {{ $inv->invoice_number }}">
                                            <x-lucide-download class="h-4 w-4" aria-hidden="true" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colCount }}" class="px-4 py-14 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                            <x-lucide-file-text class="h-6 w-6 text-gray-400" aria-hidden="true" />
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">No invoices found</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Try clearing filters{{ isset($businessEntity) ? ' or create a new invoice' : '' }}.</p>
                                        @isset($businessEntity)
                                            <a href="{{ route('business-entities.invoices.create', $businessEntity) }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">
                                                <x-lucide-plus class="h-3.5 w-3.5" aria-hidden="true" />
                                                New invoice
                                            </a>
                                        @endisset
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($invoices->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
