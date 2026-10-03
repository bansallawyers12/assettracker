<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Delete {{ $businessEntity->legal_name }}
            </h2>
            <a href="{{ route('business-entities.edit', $businessEntity) }}" class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition-colors duration-200">
                Back to edit
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8 max-w-4xl">
            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600">
                        Nothing is removed until you confirm at the bottom of this page. Deleting <strong>{{ $businessEntity->legal_name }}</strong> removes the company and the records that belong to it.
                    </p>

                    <h3 class="mt-8 text-lg font-medium text-gray-900">What will be deleted</h3>
                    @if ($willDelete === [])
                        <p class="mt-2 text-sm text-gray-600">Only the company record itself. It has no assets, invoices, journals, or bank accounts.</p>
                    @else
                        <ul class="mt-3 space-y-4">
                            @foreach ($willDelete as $group)
                                <li>
                                    <p class="text-sm font-semibold text-gray-900">{{ $group['label'] }} ({{ $group['count'] }})</p>
                                    @if ($group['items'] !== [])
                                        <ul class="mt-1 list-disc pl-5 text-sm text-gray-700">
                                            @foreach ($group['items'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <h3 class="mt-8 text-lg font-medium text-gray-900">Links to other companies or assets</h3>
                    @if ($warnings === [])
                        <p class="mt-2 text-sm text-gray-600">None. Nothing else in the portfolio points at this company or its assets.</p>
                    @else
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-950">Review these before deleting. They belong to another company or asset.</p>
                            <ul class="mt-4 space-y-4">
                                @foreach ($warnings as $warning)
                                    <li>
                                        <p class="text-sm font-semibold text-amber-950">{{ $warning['heading'] }}</p>
                                        <p class="mt-1 text-sm text-amber-950">{{ $warning['effect'] }}</p>
                                        <ul class="mt-1 list-disc pl-5 text-sm text-amber-950">
                                            @foreach ($warning['items'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('business-entities.destroy', $businessEntity) }}" method="POST" class="mt-8 border-t border-gray-200 pt-6">
                        @csrf
                        @method('DELETE')
                        @if ($warnings !== [])
                            <label class="flex items-start gap-3 mb-4">
                                <input type="checkbox" name="acknowledge_links" value="1" class="mt-1 rounded-sm border-gray-300 text-red-600 focus:ring-red-500" required>
                                <span class="text-sm text-gray-800">I have read the links above. Delete this company anyway.</span>
                            </label>
                        @endif
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700">
                            Delete {{ $businessEntity->legal_name }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
