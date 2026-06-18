<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">Add commitment</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Entity: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $businessEntity->legal_name }}</span></p>

                <form method="POST" action="{{ route('business-entities.commitments.store', $businessEntity) }}" class="space-y-5">
                    @csrf
                    @include('commitments.partials.form')

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('business-entities.show', $businessEntity) }}" class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-rose-600 text-white hover:bg-rose-700">Create commitment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
