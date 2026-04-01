<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="font-semibold text-2xl text-gray-900 dark:text-white leading-tight">
                Transaction Details
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('business-entities.transactions.edit', [$businessEntity, $transaction]) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow transition-colors">
                    Edit
                </a>
                <a href="{{ route('business-entities.show', $businessEntity) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium shadow transition-colors">
                    Back to Entity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800">

            {{-- Header bar --}}
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Entity</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $businessEntity->legal_name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bank Account</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $bankAccount->bank_name }}{{ $bankAccount->nickname ? ' ('.$bankAccount->nickname.')' : '' }}
                    </p>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->date?->format('d/m/Y') ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Amount</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format((float) $transaction->amount, 2) }}</p>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->description ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Transaction Type</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ \App\Models\Transaction::$transactionTypes[$transaction->transaction_type] ?? '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Invoice Number</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->invoice_number ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Asset</p>
                    @if ($transaction->asset)
                        <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $transaction->asset_id]) }}#tab_transactions" class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">{{ $transaction->asset->name }}</a>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payment Status</p>
                    @if (($transaction->payment_status ?? 'paid') === 'unpaid')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Unpaid</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Paid</span>
                    @endif
                </div>

                @if (($transaction->payment_status ?? 'paid') === 'unpaid')
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Due Date</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->due_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                @else
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payment Date</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->paid_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payment Method</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ \App\Models\Transaction::$paymentMethods[$transaction->payment_method] ?? ($transaction->payment_method ? ucfirst($transaction->payment_method) : '—') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ $transaction->direction === 'income' ? 'Received By / Account' : 'Paid By' }}
                        </p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->paid_by ?? '—' }}</p>
                    </div>
                @endif

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Match Status</p>
                    @if ($transaction->bankStatementEntries()->exists())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Matched
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            Unmatched
                        </span>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">GST Amount</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $transaction->gst_amount ? '$'.number_format((float) $transaction->gst_amount, 2) : '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">GST Status</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ \App\Models\Transaction::$gstStatusLabels[$transaction->gst_status] ?? ($transaction->gst_status ? ucfirst($transaction->gst_status) : '—') }}
                    </p>
                </div>

                @if ($transaction->receipt_path)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Invoice / Bill</p>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($transaction->receipt_path, now()->addMinutes(30)) }}"
                           target="_blank"
                           class="inline-flex items-center text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 underline">
                            View Document
                        </a>
                    </div>
                @endif

                @if ($transaction->paymentDocument && $transaction->paymentDocument->path)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Payment Receipt</p>
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($transaction->paymentDocument->path, now()->addMinutes(30)) }}"
                           target="_blank"
                           class="inline-flex items-center text-sm text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 underline">
                            View Payment Receipt
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
