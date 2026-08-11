@php
    $hasOldInput = session()->hasOldInput();
    $subjectToBasChecked = $hasOldInput
        ? old('subject_to_bas') !== null
        : (bool) ($subjectToBas ?? ($transaction->subject_to_bas ?? false));
    $isFlaggedChecked = $hasOldInput
        ? old('is_flagged') !== null
        : (bool) ($isFlagged ?? ($transaction->is_flagged ?? false));
@endphp

<div class="md:col-span-2 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Markers</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="subject_to_bas"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                @checked($subjectToBasChecked)
            >
            Subject to BAS
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="is_flagged"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                @checked($isFlaggedChecked)
            >
            Flagged
        </label>
    </div>
    <div class="mt-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comments</label>
        <textarea
            name="comments"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-xs"
            placeholder="Optional BAS/flag context"
        >{{ old('comments', $comments ?? ($transaction->comments ?? '')) }}</textarea>
        @error('comments') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
</div>
