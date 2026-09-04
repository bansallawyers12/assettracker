<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Edit Invoice {{ $invoice->invoice_number }} — {{ $businessEntity->legal_name }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    Back to invoice
                </a>
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded-lg text-sm font-medium transition-colors">
                    All invoices
                </a>
            </div>
        </div>
    </x-slot>

    @include('invoices.partials.form')
</x-app-layout>
