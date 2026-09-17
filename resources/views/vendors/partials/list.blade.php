@php
    $tableSort ??= \App\Support\TableSort::resolve(request(), ['name', 'email', 'phone', 'abn', 'transactions'], 'name', 'asc');
    $unlinkedGroups ??= collect();
@endphp

@if ($vendors->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-950/50">
            <x-lucide-building-2 class="h-7 w-7 text-indigo-500 dark:text-indigo-400" aria-hidden="true" />
        </div>
        <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('No vendors yet') }}</p>
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-gray-500 dark:text-gray-400">
            {{ __('Add vendors here first, then select them when creating transactions.') }}
        </p>
        @can('create', \App\Models\BusinessEntity::class)
            <button
                type="button"
                data-vendor-action="create"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                {{ __('Add vendor') }}
            </button>
        @endcan
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/60">
                <tr>
                    <x-sortable-table-header :label="__('Name')" column="name" :sort="$tableSort->column" :order="$tableSort->order" route="vendors.index" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300" />
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Contact') }}</th>
                    <x-sortable-table-header :label="__('Email')" column="email" :sort="$tableSort->column" :order="$tableSort->order" route="vendors.index" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300" />
                    <x-sortable-table-header :label="__('Phone')" column="phone" :sort="$tableSort->column" :order="$tableSort->order" route="vendors.index" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300" />
                    <x-sortable-table-header :label="__('ABN')" column="abn" :sort="$tableSort->column" :order="$tableSort->order" route="vendors.index" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300" />
                    <x-sortable-table-header :label="__('Transactions')" column="transactions" :sort="$tableSort->column" :order="$tableSort->order" route="vendors.index" align="right" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300" />
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($vendors as $vendor)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/60">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $vendor->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->contact_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->phone ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-400">{{ $vendor->abn ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-gray-900 dark:text-gray-100">
                            {{ (int) ($vendor->transactions_count ?? 0) }}
                            @php
                                $unlinkedForVendor = $unlinkedGroups->first(fn ($g) => strcasecmp($g->label, $vendor->name) === 0);
                            @endphp
                            @if ($unlinkedForVendor)
                                <span class="block text-xs text-amber-600 dark:text-amber-400" title="{{ __('Unlinked transactions with matching name') }}">
                                    +{{ (int) $unlinkedForVendor->transaction_count }} unlinked
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                            @can('create', \App\Models\BusinessEntity::class)
                                @include('vendors.partials.row-actions', ['vendor' => $vendor])
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        {{ $vendors->count() }} {{ Str::plural('vendor', $vendors->count()) }}
    </div>
@endif
