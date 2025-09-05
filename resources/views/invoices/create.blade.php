<x-app-layout>
	<div class="container mx-auto px-4 py-8">
		<h1 class="text-2xl font-bold mb-4">Create Invoice - {{ $businessEntity->legal_name }}</h1>
		@if($errors->any())
			<div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
				<ul>
					@foreach($errors->all() as $e)
					<li>{{ $e }}</li>
					@endforeach
				</ul>
			</div>
		@endif
		<form method="POST" action="{{ route('business-entities.invoices.store', $businessEntity) }}">
			@csrf
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
				<div>
					<label>Invoice Number</label>
					<input name="invoice_number" class="w-full border p-2 rounded" required />
				</div>
				<div>
					<label>Issue Date</label>
					<input type="date" name="issue_date" class="w-full border p-2 rounded" required />
				</div>
				<div>
					<label>Due Date</label>
					<input type="date" name="due_date" class="w-full border p-2 rounded" />
				</div>
			</div>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
				<div>
					<label>Customer</label>
					<input name="customer_name" class="w-full border p-2 rounded" required />
				</div>
				<div>
					<label>Reference</label>
					<input name="reference" class="w-full border p-2 rounded" />
				</div>
				<div>
					<label>Currency</label>
					<input name="currency" value="AUD" class="w-full border p-2 rounded" />
				</div>
			</div>

			<h2 class="font-semibold mb-2">Lines</h2>
			<div id="lines">
				<div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
					<input name="lines[0][description]" placeholder="Description" class="border p-2 rounded" required />
					<input name="lines[0][quantity]" type="number" step="0.0001" value="1" class="border p-2 rounded" required />
					<input name="lines[0][unit_price]" type="number" step="0.01" value="0" class="border p-2 rounded" required />
					<input name="lines[0][gst_rate]" type="number" step="0.0001" value="0.1" class="border p-2 rounded" />
					<input name="lines[0][account_code]" placeholder="Account Code (e.g. 4000)" class="border p-2 rounded" />
				</div>
			</div>
			<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded mt-3">Save</button>
		</form>
	</div>
</x-app-layout>
