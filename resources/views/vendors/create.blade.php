<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Add Vendor</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Create a vendor to use when recording expenses and bills.
        </p>

        <form method="POST"
              action="{{ route('vendors.store') }}"
              class="bg-white dark:bg-gray-900 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4 ring-1 ring-gray-200 dark:ring-gray-700">
            @csrf
            @include('vendors._form')

            <div class="flex items-center gap-4 mt-8">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    Save Vendor
                </button>
                <a href="{{ route('vendors.index') }}"
                   class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
