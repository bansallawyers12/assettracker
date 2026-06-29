<x-app-layout>
    <div class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Future commitments</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Signed contracts for property, cars, and other purchases not yet settled.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('financial-reports.commitments') }}"
                   class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    View report
                </a>
                @if($businessEntities->isNotEmpty())
                    @php
                        $createEntityId = request('entity') ?: $businessEntities->first()->id;
                    @endphp
                    <a href="{{ route('business-entities.commitments.create', $createEntityId) }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                        Add commitment
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="Active" {{ $status === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Settled" {{ $status === 'Settled' ? 'selected' : '' }}>Settled</option>
                    <option value="Cancelled" {{ $status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Entity</label>
                <select name="entity" data-tomselect class="rounded-md border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600 min-w-[12rem]">
                    <option value="">All entities</option>
                    @foreach($businessEntities as $entity)
                        <option value="{{ $entity->id }}" {{ request('entity') == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                <select name="type" class="rounded-md border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="">All types</option>
                    @foreach(\App\Models\Commitment::TYPES as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Filter</button>
        </form>

        @if($commitments->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <p class="text-sm">No commitments found.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Entity</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-right">Price</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Balance</th>
                                <th class="px-4 py-3 text-left">Settlement</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($commitments as $commitment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('business-entities.commitments.show', [$commitment->business_entity_id, $commitment->id]) }}"
                                           class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                            {{ $commitment->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $commitment->businessEntity?->legal_name }}</td>
                                    <td class="px-4 py-3">{{ $commitment->commitment_type }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $commitment->contract_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">${{ number_format($commitment->total_paid, 2) }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums font-medium">${{ number_format($commitment->balance_due, 2) }}</td>
                                    <td class="px-4 py-3">{{ $commitment->settlement_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                                            @if($commitment->status === 'Active') bg-rose-100 text-rose-800
                                            @elseif($commitment->status === 'Settled') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ $commitment->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $commitments->links() }}</div>
        @endif
    </div>
</x-app-layout>
