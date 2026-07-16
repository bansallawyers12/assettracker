<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Edit Tenant for {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
                @include('assets.partials.tenants.form')
            </div>
        </div>
    </div>
</x-app-layout>
