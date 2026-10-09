<x-app-layout>
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Preview rent invoices</h1>
                <p class="text-gray-600 mt-2">{{ $businessEntity->legal_name }}</p>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $batch['from']->format('F Y') }} – {{ $batch['to']->format('F Y') }}
                    @if ($assetName)
                        · {{ $assetName }}
                    @else
                        · All leased properties
                    @endif
                    @if ($batch['commission_percent'])
                        · Commission {{ number_format($batch['commission_percent'], 2) }}%
                        @if ($batch['agent_name'])
                            ({{ $batch['agent_name'] }})
                        @endif
                    @endif
                </p>
            </div>
            <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}"
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-sm">
                Back
            </a>
        </div>

        <div class="bg-white shadow-xs sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <p class="text-sm text-gray-700 mb-4">
                    This will create <strong>{{ $createInvoices }}</strong> rent invoice{{ $createInvoices === 1 ? '' : 's' }}
                    @if ($batch['commission_percent'])
                        and <strong>{{ $createFees }}</strong> unpaid management-fee bill{{ $createFees === 1 ? '' : 's' }}
                    @endif
                    . Rent stays the full tenant amount. The fee is GST inclusive and is not taken off the invoice.
                </p>

                @if (count($rows) === 0)
                    <p class="text-sm text-gray-500">No leases cover this range.</p>
                @else
                    <div class="overflow-hidden shadow-xs ring-1 ring-black/5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rent</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    @if ($batch['commission_percent'])
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee bill</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['asset_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $row['tenant_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $row['month_label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($row['rent_amount'], 2) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($row['will_create_invoice'])
                                                <span class="text-green-700">Create</span>
                                            @else
                                                <span class="text-gray-500">Already exists</span>
                                            @endif
                                        </td>
                                        @if ($batch['commission_percent'])
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($row['commission_amount'], 2) }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @if ($row['will_create_commission'])
                                                    <span class="text-green-700">Create</span>
                                                @elseif ($row['commission_exists'])
                                                    <span class="text-gray-500">Already exists</span>
                                                @else
                                                    <span class="text-gray-500">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form action="{{ route('business-entities.rent-invoices.generate-all', $businessEntity) }}" method="POST" class="mt-6 flex justify-end space-x-3">
                    @csrf
                    <input type="hidden" name="from_month" value="{{ $batch['from']->format('Y-m') }}" />
                    <input type="hidden" name="to_month" value="{{ $batch['to']->format('Y-m') }}" />
                    <input type="hidden" name="asset_id" value="{{ $batch['asset_id'] }}" />
                    <input type="hidden" name="commission_percent" value="{{ $batch['commission_percent'] }}" />
                    <input type="hidden" name="agent_name" value="{{ $batch['agent_name'] }}" />
                    <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-sm">
                        Cancel
                    </a>
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-sm"
                            @if ($createInvoices === 0 && $createFees === 0) disabled @endif>
                        Create drafts
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
