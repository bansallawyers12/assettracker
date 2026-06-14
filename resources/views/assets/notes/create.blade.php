<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Note') }} - {{ $asset->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('business-entities.assets.notes.store', [$businessEntity->id, $asset->id]) }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="content" :value="__('Note Content')" />
                            <x-text-area id="content" name="content" class="mt-1 block w-full" required>{{ old('content') }}</x-text-area>
                            <x-input-error :messages="$errors->get('content')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <label for="is_reminder" class="inline-flex items-center">
                                <input id="is_reminder" type="checkbox" name="is_reminder" value="1" class="rounded-sm border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500" {{ old('is_reminder') ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">{{ __('Set as Reminder') }}</span>
                            </label>
                        </div>

                        <div class="mt-4" id="reminder_date_container" style="display: none;">
                            <x-input-label for="reminder_date" :value="__('Reminder Date')" />
                            <x-date-input id="reminder_date" name="reminder_date" class="mt-1 block w-full" value="{{ old('reminder_date') }}" />
                            <x-input-error :messages="$errors->get('reminder_date')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save Note') }}</x-primary-button>
                            <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-hidden focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isReminderCheckbox = document.getElementById('is_reminder');
            const reminderDateContainer = document.getElementById('reminder_date_container');

            function toggleReminderDate() {
                reminderDateContainer.style.display = isReminderCheckbox.checked ? 'block' : 'none';
            }

            isReminderCheckbox.addEventListener('change', toggleReminderDate);
            toggleReminderDate(); // Initial state
        });
    </script>
    @endpush
</x-app-layout> 