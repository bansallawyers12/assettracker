<x-app-layout>
	<div class="container mx-auto px-4 py-8">
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-2xl font-bold">Invoices - {{ $businessEntity->legal_name }}</h1>
			<a href="{{ route('business-entities.invoices.create', $businessEntity) }}" class="bg-blue-600 text-white px-4 py-2 rounded">New Invoice</a>
		</div>
		@if(session('success'))
			<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
		@endif
		<div class="bg-white shadow rounded">
			<table class="min-w-full">
				<thead>
					<tr class="text-left border-b">
						<th class="p-3">Number</th>
						<th class="p-3">Customer</th>
						<th class="p-3">Issue Date</th>
						<th class="p-3">Total</th>
						<th class="p-3">Status</th>
						<th class="p-3"></th>
					</tr>
				</thead>
				<tbody>
					@foreach($invoices as $inv)
					<tr class="border-b">
						<td class="p-3">{{ $inv->invoice_number }}</td>
						<td class="p-3">{{ $inv->customer_name }}</td>
						<td class="p-3">{{ $inv->issue_date->format('Y-m-d') }}</td>
						<td class="p-3">${{ number_format($inv->total_amount, 2) }}</td>
						<td class="p-3">{{ ucfirst($inv->status) }}</td>
						<td class="p-3 text-right">
							<a href="{{ route('business-entities.invoices.show', [$businessEntity, $inv]) }}" class="text-blue-600">View</a>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
			<div class="p-3">{{ $invoices->links() }}</div>
		</div>
	</div>
</x-app-layout>
