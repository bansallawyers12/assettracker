<x-app-layout>
<div class="container mx-auto px-4 py-8">

    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Vendors</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Single source of truth for suppliers — edit a vendor here and linked transactions update everywhere.
            </p>
        </div>
        <a href="{{ route('vendors.create') }}"
           class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            + Add Vendor
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-sm border border-green-400 dark:border-green-700 bg-green-100 dark:bg-green-900/30 px-4 py-3 text-green-800 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-sm border border-red-400 dark:border-red-700 bg-red-100 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-sm border border-red-400 dark:border-red-700 bg-red-100 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($unlinkedGroups->isNotEmpty())
        <div class="mb-8 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-amber-900 dark:text-amber-200">Resolve unlinked vendor names</h2>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-200/80 max-w-2xl">
                        These transactions have vendor name text but are not linked to a vendor record yet. Link them once from here — after that, editing the vendor updates them everywhere.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <form method="POST" action="{{ route('vendors.auto-link-all') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                            Auto-link and create vendors
                        </button>
                    </form>
                    <form method="POST" action="{{ route('vendors.sync-all-names') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Refresh all linked names
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg ring-1 ring-amber-200 dark:ring-amber-900 bg-white dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Vendor name on transaction</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Count</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Link to vendor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($unlinkedGroups as $group)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $group->label }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ (int) $group->transaction_count }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('vendors.resolve-unlinked') }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="vendor_name_label" value="{{ $group->label }}">
                                        <select name="vendor_id" required
                                                class="min-w-[12rem] rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs">
                                            <option value="">Select vendor…</option>
                                            @foreach($vendors as $vendorOption)
                                                <option value="{{ $vendorOption->id }}" @selected(strcasecmp($vendorOption->name, $group->label) === 0)>
                                                    {{ $vendorOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                            Link
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($vendors->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 px-6 py-12 text-center">
            <p class="font-medium text-gray-700 dark:text-gray-300">No vendors yet.</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                Add vendors here first, then select them when creating transactions.
            </p>
            <a href="{{ route('vendors.create') }}"
               class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Add your first vendor
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg shadow-xs ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Contact</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Email</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Phone</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">ABN</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Transactions</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($vendors as $vendor)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $vendor->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->contact_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $vendor->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $vendor->abn ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-900 dark:text-gray-100">
                                    {{ (int) ($vendor->transactions_count ?? 0) }}
                                    @if(isset($unlinkedGroups))
                                        @php
                                            $unlinkedForVendor = $unlinkedGroups->first(fn ($g) => strcasecmp($g->label, $vendor->name) === 0);
                                        @endphp
                                        @if($unlinkedForVendor)
                                            <span class="block text-xs text-amber-600 dark:text-amber-400" title="Unlinked transactions with matching name">
                                                +{{ (int) $unlinkedForVendor->transaction_count }} unlinked
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('vendors.edit', $vendor) }}"
                                       class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mr-3">Edit</a>
                                    <form method="POST"
                                          action="{{ route('vendors.destroy', $vendor) }}"
                                          class="inline"
                                          onsubmit="return confirm('Delete this vendor? Linked transactions will keep the vendor name but lose the link.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ $vendors->count() }} vendor{{ $vendors->count() === 1 ? '' : 's' }}
            </div>
        </div>
    @endif

</div>
</x-app-layout>
