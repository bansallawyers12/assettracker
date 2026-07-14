<x-modal
    name="close-business-entity"
    :show="$errors->has('closed_date') || $errors->has('closed_reason')"
    focusable
    maxWidth="lg"
>
    <form
        method="POST"
        action="{{ route('business-entities.close', $businessEntity) }}"
        class="p-6"
    >
        @csrf

        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                <x-lucide-archive class="h-5 w-5" aria-hidden="true" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Close entity</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Record when <span class="font-medium text-gray-700 dark:text-gray-200">{{ $businessEntity->legal_name }}</span>
                    was closed and why. The entity status will be set to Inactive, hidden from active lists, and its assets marked as Sold.
                </p>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <div>
                <label for="closed_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Closed date</label>
                <x-date-input
                    name="closed_date"
                    id="closed_date"
                    value="{{ old('closed_date', now()->format('Y-m-d')) }}"
                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                    required
                />
                @error('closed_date')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="closed_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                <textarea
                    name="closed_reason"
                    id="closed_reason"
                    rows="4"
                    required
                    maxlength="2000"
                    placeholder="e.g. Company deregistered with ASIC, trust wound up, or no longer operating."
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-rose-500 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                >{{ old('closed_reason') }}</textarea>
                @error('closed_reason')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="button"
                x-on:click="$dispatch('close-modal', 'close-business-entity')"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-xs transition-colors hover:bg-gray-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-rose-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-rose-500"
            >
                Close entity
            </button>
        </div>
    </form>
</x-modal>
