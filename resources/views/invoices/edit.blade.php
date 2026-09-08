<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $businessEntity->legal_name }}
                </p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white truncate">
                    Edit Invoice {{ $invoice->invoice_number }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Draft invoices can be edited freely. Posted invoices stay read-only.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                    Back to invoice
                </a>
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    All invoices
                </a>
            </div>
        </div>
    </x-slot>

    @include('invoices.partials.form')
</x-app-layout>
