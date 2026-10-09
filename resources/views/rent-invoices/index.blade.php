<x-app-layout>
<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <div class="w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rent Invoice Management</h1>
                <p class="text-gray-600 mt-2">{{ $businessEntity->legal_name }}</p>
                <p class="text-sm text-gray-500 mt-1">Taxable leases: GST-inclusive at 10%. GST-not-applicable leases: no GST on the rent amount. One invoice per lease per calendar month. Management commission is a separate unpaid bill, not a deduction from the tenant invoice.</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="document.getElementById('generate-all-modal').classList.remove('hidden')" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-sm">
                    Generate All Invoices
                </button>
                <a href="{{ route('business-entities.show', $businessEntity) }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-sm">
                    Back to Entity
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-sm mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-sm mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Leasable assets & leases -->
        <div class="bg-white shadow-xs sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Properties & leases</h3>
                
                @if($leaseableAssets->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($leaseableAssets as $asset)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900">{{ $asset->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $asset->address }}</p>
                                
                                @if($asset->leases->count() > 0)
                                    @foreach($asset->leases as $lease)
                                        <div class="mt-3 p-3 bg-gray-50 rounded-sm">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">
                                                        {{ $lease->tenant ? $lease->tenant->name : 'No Tenant' }}
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        ${{ number_format($lease->rental_amount, 2) }} {{ $lease->payment_frequency }}
                                                        · {{ $lease->gst_applicable ? 'GST 10% inc' : 'GST n/a' }}
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        {{ \Carbon\Carbon::parse($lease->start_date)->format('M j, Y') }} - 
                                                        {{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('M j, Y') : 'Ongoing' }}
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('business-entities.rent-invoices.preview', [$businessEntity, $lease]) }}" 
                                                       class="text-blue-600 hover:text-blue-900 text-sm">
                                                        Preview
                                                    </a>
                                                    <form action="{{ route('business-entities.rent-invoices.generate-lease', [$businessEntity, $lease]) }}" 
                                                          method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900 text-sm">
                                                            Generate
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500 mt-2">No active leases</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <x-lucide-building-2 />
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No leasable properties</h3>
                        <p class="mt-1 text-sm text-gray-500">Add a house, office, suite, or other leased asset with an active lease to generate rent invoices.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Current Month Invoices -->
        @if($existingInvoices->count() > 0)
            <div class="bg-white shadow-xs sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Current Month Invoices ({{ \Carbon\Carbon::now()->format('F Y') }})
                    </h3>
                    
                    <div class="overflow-hidden shadow-xs ring-1 ring-black/5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($existingInvoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $invoice->invoice_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $invoice->customer_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $invoice->reference }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($invoice->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($invoice->status === 'paid') bg-green-100 text-green-800
                                                @elseif($invoice->status === 'partial') bg-amber-100 text-amber-800
                                                @elseif($invoice->status === 'approved') bg-blue-100 text-blue-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ \App\Models\Invoice::$statuses[$invoice->status] ?? ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Upcoming Invoices -->
        @if(count($upcomingInvoices) > 0)
            <div class="bg-white shadow-xs sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Upcoming Rent Invoices (Next 6 Months)</h3>
                    
                    <div class="overflow-hidden shadow-xs ring-1 ring-black/5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($upcomingInvoices as $upcoming)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $upcoming['asset']->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $upcoming['tenant'] ? $upcoming['tenant']->name : 'No Tenant' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $upcoming['invoice_date']->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($upcoming['rent_amount'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($upcoming['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Generate All Invoices Modal -->
@php
    $openGenerate = $errors->hasAny(['from_month', 'to_month', 'asset_id', 'commission_percent', 'agent_name']);
@endphp
<div id="generate-all-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full {{ $openGenerate ? '' : 'hidden' }} z-50">
    <div class="relative top-16 mx-auto p-5 border border-gray-200 w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-1">Generate rent invoices</h3>
            <p class="text-sm text-gray-500 mb-4">One draft per lease for each month in the range. Months that already have an invoice are skipped. Commission, if entered, is a separate unpaid management-fee bill (account 5110, GST inclusive) and does not reduce the rent.</p>
            <form action="{{ route('business-entities.rent-invoices.preview-bulk', $businessEntity) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="from_month" class="block text-sm font-medium text-gray-700">From month</label>
                        <input type="month" name="from_month" id="from_month" required
                               value="{{ old('from_month', now()->format('Y-m')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 sm:text-sm" />
                        @error('from_month')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="to_month" class="block text-sm font-medium text-gray-700">To month</label>
                        <input type="month" name="to_month" id="to_month" required
                               value="{{ old('to_month', now()->format('Y-m')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 sm:text-sm" />
                        @error('to_month')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mb-4">
                    <label for="asset_id" class="block text-sm font-medium text-gray-700">Property</label>
                    <select name="asset_id" id="asset_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">All leased properties</option>
                        @foreach ($leaseableAssets as $asset)
                            <option value="{{ $asset->id }}" @selected((string) old('asset_id') === (string) $asset->id)>{{ $asset->name }}</option>
                        @endforeach
                    </select>
                    @error('asset_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="commission_percent" class="block text-sm font-medium text-gray-700">Management commission %</label>
                        <input type="number" name="commission_percent" id="commission_percent" min="0" max="100" step="0.01"
                               value="{{ old('commission_percent') }}"
                               placeholder="Leave blank for rent only"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 sm:text-sm" />
                        @error('commission_percent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="agent_name" class="block text-sm font-medium text-gray-700">Agent name</label>
                        <input type="text" name="agent_name" id="agent_name" maxlength="255"
                               value="{{ old('agent_name') }}"
                               placeholder="Optional"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-xs focus:ring-blue-500 focus:border-blue-500 sm:text-sm" />
                        @error('agent_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button"
                            onclick="document.getElementById('generate-all-modal').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-sm">
                        Preview
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
