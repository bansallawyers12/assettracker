<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $businessEntity->legal_name }}
                </p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white truncate">
                    Create Invoice
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    One-off invoice. For recurring rent, use Rent invoices.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    All invoices
                </a>
                <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-200 dark:hover:bg-indigo-900/50">
                    Rent invoices
                </a>
            </div>
        </div>
    </x-slot>

    @include('invoices.partials.form')
</x-app-layout>
