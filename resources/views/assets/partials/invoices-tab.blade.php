<div id="tab_invoices" class="tab-content hidden">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Invoices</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Create rent invoices from a lease, post from the invoice page, then record payment or send reminders.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">YTD invoiced</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($invoiceSummary['ytd_invoiced'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ now()->year }} issue date</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Outstanding (posted)</p>
                <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-400">${{ number_format($invoiceSummary['outstanding'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Status: approved</p>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">YTD paid</p>
                <p class="mt-1 text-2xl font-semibold text-green-700 dark:text-green-400">${{ number_format($invoiceSummary['ytd_paid'] ?? 0, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ now()->year }} payment date</p>
            </div>
        </div>

        @if ($asset->leases->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 shadow border border-gray-100 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Create rent invoice</h4>
                <form method="POST" action="{{ route('assets.invoices.store-for-lease', [$businessEntity, $asset]) }}" class="flex flex-col lg:flex-row lg:flex-wrap lg:items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[200px]">
                        <label for="invoice_lease_id" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Lease / tenant</label>
                        <select name="lease_id" id="invoice_lease_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach ($asset->leases as $lease)
                                <option value="{{ $lease->id }}">{{ $lease->tenant?->name ?? 'Lease #'.$lease->id }} — ${{ number_format($lease->rental_amount, 2) }} {{ $lease->payment_frequency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-auto">
                        <label for="invoice_date" class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Billing date</label>
                        <input type="date" name="invoice_date" id="invoice_date" value="{{ now()->format('Y-m-d') }}" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition-colors">
                        Generate invoice
                    </button>
                </form>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Uses the same rules as entity rent invoices. One invoice per lease per calendar month.</p>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-4 text-sm text-gray-600 dark:text-gray-400">
                Add a lease on this asset to generate rent invoices.
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Invoice history</h4>
            </div>
            @if ($assetInvoices->isEmpty())
                <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">No invoices yet for this property.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/80">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Number</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tenant</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Issue</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Due</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($assetInvoices as $inv)
                                @php
                                    $statusStyles = [
                                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                        'void' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                    ];
                                    $badge = $statusStyles[$inv->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-2 font-mono text-xs">{{ $inv->invoice_number }}</td>
                                    <td class="px-4 py-2">{{ $inv->lease?->tenant?->name ?? $inv->customer_name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">{{ $inv->issue_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-2 text-right font-medium">${{ number_format($inv->total_amount, 2) }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($inv->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ route('business-entities.invoices.show', [$businessEntity, $inv]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">View</a>
                                            @if ($inv->status === 'approved')
                                                <form method="POST" action="{{ route('business-entities.invoices.record-payment', [$businessEntity, $inv]) }}" class="flex flex-wrap items-center gap-2">
                                                    @csrf
                                                    <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs w-32" />
                                                    <input type="text" name="payment_method" placeholder="Method" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs w-24" />
                                                    <input type="text" name="payment_reference" placeholder="Ref" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs w-24" />
                                                    <button type="submit" class="px-2 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded">Mark paid</button>
                                                </form>
                                                @if ($inv->lease?->tenant?->email)
                                                    <form method="POST" action="{{ route('business-entities.invoices.remind', [$businessEntity, $inv]) }}" onsubmit="return confirm('Send payment reminder email to {{ $inv->lease->tenant->email }}?');">
                                                        @csrf
                                                        <button type="submit" class="text-xs text-amber-700 dark:text-amber-400 hover:underline font-medium">Send reminder</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
