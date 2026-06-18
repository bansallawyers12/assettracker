<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">Edit commitment</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-xs sm:rounded-lg p-6">
                <form method="POST" action="{{ route('business-entities.commitments.update', [$businessEntity, $commitment]) }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    @include('commitments.partials.form', ['commitment' => $commitment])

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('business-entities.commitments.show', [$businessEntity, $commitment]) }}" class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200">Cancel</a>
                        <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
