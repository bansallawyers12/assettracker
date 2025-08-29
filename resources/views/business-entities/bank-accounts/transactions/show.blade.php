<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ __('Transaction Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="text-2xl font-bold mb-6">Transaction Details</h1>
                    <div class="space-y-4">
                        <p><strong>Date:</strong> {{ $transaction->date }}</p>
                        <p><strong>Amount:</strong> {{ $transaction->amount }}</p>
                        <p><strong>Description:</strong> {{ $transaction->description }}</p>
                        <p><strong>Transaction Type:</strong> {{ \App\Models\Transaction::$transactionTypes[$transaction->transaction_type] ?? 'N/A' }}</p>
                        <p><strong>GST Amount:</strong> {{ $transaction->gst_amount }}</p>
                        <p><strong>GST Status:</strong> {{ $transaction->gst_status }}</p>
                        @if ($transaction->receipt_path)
                            <p><strong>Receipt:</strong> 
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($transaction->receipt_path, now()->addMinutes(30)) }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline">View Receipt</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>