<x-app-layout>
	<div class="container mx-auto px-4 py-8">
		<div class="flex justify-between items-center mb-4">
			<h1 class="text-2xl font-bold">Invoice {{ $invoice->invoice_number }}</h1>
			<div class="space-x-2">
				<a class="bg-gray-600 text-white px-4 py-2 rounded" href="{{ route('business-entities.invoices.index', $businessEntity) }}">Back</a>
				@if(!$invoice->is_posted)
				<form method="POST" action="{{ route('business-entities.invoices.post', [$businessEntity, $invoice]) }}" class="inline">
					@csrf
					<button class="bg-green-600 text-white px-4 py-2 rounded">Post</button>
				</form>
				@endif
			</div>
		</div>
		@if(session('success'))
			<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
		@endif
		<div class="bg-white shadow rounded p-4">
			<div class="grid grid-cols-2 gap-4">
				<div>
					<div><strong>Customer:</strong> {{ $invoice->customer_name }}</div>
					<div><strong>Issue Date:</strong> {{ $invoice->issue_date->format('Y-m-d') }}</div>
					<div><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('Y-m-d') }}</div>
					<div><strong>Status:</strong> {{ ucfirst($invoice->status) }}</div>
				</div>
				<div class="text-right">
					<div><strong>Subtotal:</strong> ${{ number_format($invoice->subtotal, 2) }}</div>
					<div><strong>GST:</strong> ${{ number_format($invoice->gst_amount, 2) }}</div>
					<div><strong>Total:</strong> ${{ number_format($invoice->total_amount, 2) }}</div>
				</div>
			</div>
			<h2 class="font-semibold mt-4 mb-2">Lines</h2>
			<table class="min-w-full">
				<thead>
					<tr class="text-left border-b">
						<th class="p-2">Description</th>
						<th class="p-2">Qty</th>
						<th class="p-2">Unit Price</th>
						<th class="p-2">GST Rate</th>
						<th class="p-2 text-right">Total</th>
					</tr>
				</thead>
				<tbody>
					@foreach($invoice->lines as $line)
					<tr class="border-b">
						<td class="p-2">{{ $line->description }}</td>
						<td class="p-2">{{ $line->quantity }}</td>
						<td class="p-2">${{ number_format($line->unit_price, 2) }}</td>
						<td class="p-2">{{ $line->gst_rate * 100 }}%</td>
						<td class="p-2 text-right">${{ number_format($line->line_total, 2) }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
</x-app-layout>
