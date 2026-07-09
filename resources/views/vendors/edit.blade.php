<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Edit Vendor</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Update this vendor once — linked transactions update everywhere automatically.
        </p>

        @if(session('success'))
            <div class="mb-4 rounded-sm border border-green-400 dark:border-green-700 bg-green-100 dark:bg-green-900/30 px-4 py-3 text-green-800 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-sm border border-red-400 dark:border-red-700 bg-red-100 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @include('vendors._usage-panel', [
            'vendor' => $vendor,
            'usage' => $usage,
            'recentTransactions' => $recentTransactions,
            'referenceAreas' => $referenceAreas,
        ])

        <form method="POST"
              action="{{ route('vendors.update', $vendor) }}"
              class="bg-white dark:bg-gray-900 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4 ring-1 ring-gray-200 dark:ring-gray-700">
            @csrf
            @method('PUT')
            @include('vendors._form', ['vendor' => $vendor])

            <div class="flex items-center gap-4 mt-8">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    Update Vendor
                </button>
                <a href="{{ route('vendors.index') }}"
                   class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    Back to vendors
                </a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
