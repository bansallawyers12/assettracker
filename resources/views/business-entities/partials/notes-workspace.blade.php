<div
    class="notes-workspace bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg"
    data-entity-id="{{ $businessEntity->id }}"
    data-store-url="{{ route('business-entities.notes.store', $businessEntity) }}"
    data-delete-url-template="{{ route('business-entities.notes.destroy', [$businessEntity, '__NOTE__']) }}"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300">Notes</h3>
        <button type="button" data-notes-action="toggle-form" class="inline-flex items-center self-start bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 px-3 py-1.5 rounded-md text-sm">
            <x-lucide-plus class="h-4 w-4 mr-1" />
            Add Note
        </button>
    </div>

    <form data-notes-form class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs border border-gray-200 dark:border-gray-700 space-y-3" method="POST" action="{{ route('business-entities.notes.store', $businessEntity) }}">
        <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
            <textarea name="content" rows="3" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-xs focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" data-notes-action="cancel-form" class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-300">Cancel</button>
            <button type="submit" data-ws-submit class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                Save Note
            </button>
        </div>
    </form>

    <div data-notes-list>
        @include('business-entities.partials.notes.list', [
            'businessEntity' => $businessEntity,
            'notes' => $notes ?? collect(),
        ])
    </div>
</div>
