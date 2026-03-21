<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Invoice {{ $invoice->invoice_number }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @if ($invoice->asset_id)
                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $invoice->asset_id]) }}#tab_invoices" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                        Back to asset
                    </a>
                @endif
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    All invoices
                </a>
                @if (!$invoice->is_posted)
                    <form method="POST" action="{{ route('business-entities.invoices.post', [$businessEntity, $invoice]) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">Post to ledger</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-200">{{ session('info') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 border-b border-gray-200 dark:border-gray-700">
                <div class="space-y-2 text-sm">
                    <div><span class="text-gray-500 dark:text-gray-400">Customer</span><br><span class="font-medium text-gray-900 dark:text-white">{{ $invoice->customer_name }}</span></div>
                    @if ($invoice->lease?->tenant)
                        <div><span class="text-gray-500 dark:text-gray-400">Tenant email</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->lease->tenant->email ?? '—' }}</span></div>
                    @endif
                    @if ($invoice->reference)
                        <div><span class="text-gray-500 dark:text-gray-400">Reference</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->reference }}</span></div>
                    @endif
                    <div><span class="text-gray-500 dark:text-gray-400">Issue date</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->issue_date->format('d/m/Y') }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Due date</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Status</span><br>
                        @php
                            $invStatusClass = match ($invoice->status) {
                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                'void' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                            };
                        @endphp
                        <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $invStatusClass }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">Posted</span><br><span class="text-gray-900 dark:text-white">{{ $invoice->is_posted ? 'Yes' : 'No' }}</span></div>
                </div>
                <div class="text-right space-y-1 text-sm">
                    <div class="text-gray-500 dark:text-gray-400">Subtotal</div>
                    <div class="text-lg text-gray-900 dark:text-white">${{ number_format($invoice->subtotal, 2) }}</div>
                    <div class="text-gray-500 dark:text-gray-400">GST</div>
                    <div class="text-gray-900 dark:text-white">${{ number_format($invoice->gst_amount, 2) }}</div>
                    <div class="text-gray-500 dark:text-gray-400 pt-2 font-semibold">Total ({{ $invoice->currency }})</div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($invoice->total_amount, 2) }}</div>
                </div>
            </div>

            @if ($invoice->status === 'paid' && $invoice->paid_at)
                <div class="px-6 py-4 bg-green-50 dark:bg-green-900/20 border-b border-green-100 dark:border-green-900/40 text-sm">
                    <h3 class="font-semibold text-green-900 dark:text-green-200 mb-2">Payment recorded</h3>
                    <p class="text-green-800 dark:text-green-300">Paid on {{ $invoice->paid_at->format('d/m/Y') }}
                        @if ($invoice->payment_method) — {{ $invoice->payment_method }} @endif
                        @if ($invoice->payment_reference) ({{ $invoice->payment_reference }}) @endif
                    </p>
                </div>
            @endif

            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Line items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4">Description</th>
                                <th class="py-2 pr-4">Qty</th>
                                <th class="py-2 pr-4">Unit</th>
                                <th class="py-2 pr-4">GST %</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($invoice->lines as $line)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ $line->description }}</td>
                                    <td class="py-2 pr-4">{{ $line->quantity }}</td>
                                    <td class="py-2 pr-4">${{ number_format($line->unit_price, 2) }}</td>
                                    <td class="py-2 pr-4">{{ (float) $line->gst_rate * 100 }}%</td>
                                    <td class="py-2 text-right font-medium">${{ number_format($line->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($invoice->notes)
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Notes</span>
                    <p class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $invoice->notes }}</p>
                </div>
            @endif

            @if ($invoice->status === 'approved' && !$invoice->paid_at)
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Record payment</h3>
                        <form method="POST" action="{{ route('business-entities.invoices.record-payment', [$businessEntity, $invoice]) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Paid date</label>
                                <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Payment method</label>
                                <input type="text" name="payment_method" placeholder="e.g. Bank transfer" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Reference</label>
                                <input type="text" name="payment_reference" placeholder="Receipt / transaction ID" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm text-sm" />
                            </div>
                            <button type="submit" class="w-full sm:w-auto inline-flex justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">Mark as paid</button>
                        </form>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Follow up</h3>
                        @if ($invoice->lease?->tenant?->email)
                            <form method="POST" action="{{ route('business-entities.invoices.remind', [$businessEntity, $invoice]) }}" onsubmit="return confirm('Send reminder email?');">
                                @csrf
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Send a payment reminder to <strong>{{ $invoice->lease->tenant->email }}</strong>.</p>
                                @if ($invoice->last_reminder_sent_at)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Last sent: {{ $invoice->last_reminder_sent_at->format('d/m/Y H:i') }} ({{ $invoice->reminder_count }} total)</p>
                                @endif
                                <button type="submit" class="inline-flex px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">Send reminder email</button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Add a tenant email on the lease to send reminders.</p>
                        @endif
                    </div>
                </div>
            @endif

            @if (!$invoice->is_posted)
                <div class="px-6 py-4 flex flex-wrap gap-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('business-entities.invoices.edit', [$businessEntity, $invoice]) }}" class="inline-flex px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">Edit</a>
                    <form method="POST" action="{{ route('business-entities.invoices.destroy', [$businessEntity, $invoice]) }}" onsubmit="return confirm('Delete this invoice?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
