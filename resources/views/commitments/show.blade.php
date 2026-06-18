<x-app-layout>
    <div class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $businessEntity->legal_name }}</p>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mt-0.5">{{ $commitment->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $commitment->commitment_type }} · {{ $commitment->status }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($commitment->isEditable())
                        <a href="{{ route('business-entities.commitments.edit', [$businessEntity, $commitment]) }}"
                           class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Edit</a>
                    @endif
                    <a href="{{ route('commitments.index', ['entity' => $businessEntity->id]) }}"
                       class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">All commitments</a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Contract price</p>
                <p class="text-lg font-bold tabular-nums">${{ number_format((float) $commitment->contract_price, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Total paid</p>
                <p class="text-lg font-bold tabular-nums text-green-700">${{ number_format($commitment->total_paid, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Balance due</p>
                <p class="text-lg font-bold tabular-nums text-rose-700">${{ number_format($commitment->balance_due, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Settlement</p>
                <p class="text-lg font-bold">{{ $commitment->settlement_date?->format('d/m/Y') ?? '—' }}</p>
                @if($commitment->contract_date)
                    <p class="text-xs text-gray-500 mt-1">Contract: {{ $commitment->contract_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>

        @if($commitment->notes)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500 mb-2">Notes</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $commitment->notes }}</p>
            </div>
        @endif

        @if($commitment->asset)
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                Settled as asset:
                <a href="{{ route('business-entities.assets.show', [$businessEntity, $commitment->asset]) }}" class="font-medium underline">
                    {{ $commitment->asset->name }}
                </a>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Payments</h2>
            </div>

            @if($commitment->payments->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-500">No payments recorded yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left">Date</th>
                                <th class="px-4 py-2 text-left">Type</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 text-left">Notes</th>
                                @if($commitment->isEditable())
                                    <th class="px-4 py-2 text-right"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($commitment->payments as $payment)
                                <tr>
                                    <td class="px-4 py-2">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2">{{ $payment->payment_type }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">${{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $payment->notes ?? '—' }}</td>
                                    @if($commitment->isEditable())
                                        <td class="px-4 py-2 text-right">
                                            <form method="POST" action="{{ route('business-entities.commitments.payments.destroy', [$businessEntity, $commitment, $payment]) }}" class="inline" onsubmit="return confirm('Remove this payment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($commitment->isEditable())
                <div class="px-4 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Record payment</h3>
                    <form method="POST" action="{{ route('business-entities.commitments.payments.store', [$businessEntity, $commitment]) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                            <input type="date" name="paid_at" required value="{{ date('Y-m-d') }}" class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Type</label>
                            <select name="payment_type" class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                                @foreach(\App\Models\CommitmentPayment::PAYMENT_TYPES as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" required class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                            <input type="text" name="notes" placeholder="Optional" class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                        </div>
                        <div class="sm:col-span-5 flex justify-end">
                            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Add payment</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        @if($commitment->status === 'Active')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Settle commitment</h2>
                <form method="POST" action="{{ route('business-entities.commitments.settle', [$businessEntity, $commitment]) }}" class="space-y-4">
                    @csrf
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="create_asset" value="1" checked class="rounded border-gray-300 text-blue-600">
                        Create asset on settlement
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Asset type</label>
                            <select name="asset_type" class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                                <option value="{{ $commitment->defaultAssetType() }}" selected>{{ $commitment->defaultAssetType() }}</option>
                                @foreach(['Car', 'House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate', 'Suite'] as $assetType)
                                    @if($assetType !== $commitment->defaultAssetType())
                                        <option value="{{ $assetType }}">{{ $assetType }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Acquisition date</label>
                            <input type="date" name="acquisition_date"
                                   value="{{ $commitment->settlement_date?->format('Y-m-d') ?? date('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 text-sm dark:bg-gray-900 dark:border-gray-600">
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700"
                            onclick="return confirm('Mark this commitment as settled?');">
                        Mark as settled
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
