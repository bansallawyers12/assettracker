<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <nav class="mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <span>{{ $businessEntity->legal_name }}</span>
                    <span>&rsaquo;</span>
                    <a href="{{ route('business-entities.invoices.index', $businessEntity) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Invoices</a>
                    <span>&rsaquo;</span>
                    <span class="text-gray-800 dark:text-gray-200">New</span>
                </nav>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white truncate">
                        Create Invoice
                    </h2>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-200 dark:ring-indigo-900">
                        New Draft
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    One-off invoice. For recurring rent, use Rent invoices.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('business-entities.invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-2xs hover:bg-gray-50 hover:text-gray-900 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-lucide-list class="h-4 w-4" aria-hidden="true" />
                    All invoices
                </a>
                <a href="{{ route('business-entities.rent-invoices.index', $businessEntity) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3.5 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition-colors dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50">
                    <x-lucide-calendar-clock class="h-4 w-4" aria-hidden="true" />
                    Rent invoices
                </a>
            </div>
        </div>
    </x-slot>

    @include('invoices.partials.form')
</x-app-layout>
