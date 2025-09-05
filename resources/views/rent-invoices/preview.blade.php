@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rent Invoice Preview</h1>
                <p class="text-gray-600 mt-2">{{ $businessEntity->legal_name }}</p>
            </div>
            <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Rent Invoices
            </a>
        </div>

        @if($existingInvoice)
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <strong>Note:</strong> An invoice already exists for this lease and period.
            </div>
        @endif

        <!-- Invoice Preview -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Preview</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Lease Information -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Lease Information</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Suite</dt>
                                <dd class="text-sm text-gray-900">{{ $lease->asset->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Address</dt>
                                <dd class="text-sm text-gray-900">{{ $lease->asset->address ?: 'Not specified' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                                <dd class="text-sm text-gray-900">{{ $lease->tenant ? $lease->tenant->name : 'No Tenant Assigned' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Rental Amount</dt>
                                <dd class="text-sm text-gray-900">${{ number_format($lease->rental_amount, 2) }} {{ $lease->payment_frequency }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Lease Period</dt>
                                <dd class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($lease->start_date)->format('M j, Y') }} - 
                                    {{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('M j, Y') : 'Ongoing' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Invoice Details -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Invoice Details</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                                <dd class="text-sm text-gray-900">{{ $currentMonth->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                <dd class="text-sm text-gray-900">{{ $currentMonth->copy()->addDays(30)->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Reference</dt>
                                <dd class="text-sm text-gray-900">Rent for {{ $lease->asset->name }} - {{ $currentMonth->format('F Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Invoice Line Items -->
                <div class="mt-6">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Line Items</h4>
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GST Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Rent for {{ $lease->asset->name }} - {{ $currentMonth->format('F Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($rentAmount, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">10%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($rentAmount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Invoice Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-64">
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Subtotal:</dt>
                                <dd class="text-sm text-gray-900">${{ number_format($rentAmount - ($rentAmount * 0.10), 2) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">GST (10%):</dt>
                                <dd class="text-sm text-gray-900">${{ number_format($rentAmount * 0.10, 2) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2">
                                <dt class="text-base font-medium text-gray-900">Total:</dt>
                                <dd class="text-base font-medium text-gray-900">${{ number_format($rentAmount, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                    @if(!$existingInvoice)
                        <form action="{{ route('business-entities.rent-invoices.generate-lease', [$businessEntity, $lease]) }}" 
                              method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Generate Invoice
                            </button>
                        </form>
                    @else
                        <a href="{{ route('business-entities.invoices.show', [$businessEntity, $existingInvoice]) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            View Existing Invoice
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
