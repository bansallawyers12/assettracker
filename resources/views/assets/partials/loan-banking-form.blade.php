<form
    class="loan-banking-ws-form space-y-4"
    method="POST"
    action="{{ route('business-entities.assets.loan-banking.update', [$businessEntity->id, $asset->id]) }}"
>
    @csrf
    @method('PATCH')

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    @include('assets.partials.loan-banking-fields', [
        'asset' => $asset,
        'rentPaidBySuggestions' => $rentPaidBySuggestions ?? [],
        'workspacePanel' => true,
    ])

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </button>
        <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Save Loan Details
        </button>
    </div>
</form>
