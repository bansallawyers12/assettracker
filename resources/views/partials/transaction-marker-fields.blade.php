@php
    $hasOldInput = session()->hasOldInput();
    $subjectToBasChecked = $hasOldInput
        ? old('subject_to_bas') !== null
        : (bool) ($subjectToBas ?? ($transaction->subject_to_bas ?? false));
    $isFlaggedChecked = $hasOldInput
        ? old('is_flagged') !== null
        : (bool) ($isFlagged ?? ($transaction->is_flagged ?? false));
@endphp

<div class="rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/40 p-4 space-y-3.5">
    <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Compliance & Review Markers
        </span>
        <span class="text-xs text-gray-400 dark:text-gray-500">Optional</span>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <label class="group relative inline-flex items-center gap-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 shadow-2xs hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer transition-all has-checked:border-indigo-500 has-checked:bg-indigo-50/70 dark:has-checked:bg-indigo-950/40 dark:has-checked:border-indigo-500 has-checked:text-indigo-900 dark:has-checked:text-indigo-200">
            <input
                type="checkbox"
                name="subject_to_bas"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                @checked($subjectToBasChecked)
            >
            <span class="inline-flex items-center gap-1.5">
                <x-lucide-receipt class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-500 has-checked:text-indigo-600" />
                Subject to BAS
            </span>
        </label>

        <label class="group relative inline-flex items-center gap-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 shadow-2xs hover:bg-gray-50 dark:hover:bg-gray-700/60 cursor-pointer transition-all has-checked:border-amber-500 has-checked:bg-amber-50/70 dark:has-checked:bg-amber-950/40 dark:has-checked:border-amber-500 has-checked:text-amber-900 dark:has-checked:text-amber-200">
            <input
                type="checkbox"
                name="is_flagged"
                value="1"
                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 w-4 h-4"
                @checked($isFlaggedChecked)
            >
            <span class="inline-flex items-center gap-1.5">
                <x-lucide-flag class="w-3.5 h-3.5 text-gray-400 group-hover:text-amber-500 has-checked:text-amber-600" />
                Flagged for review
            </span>
        </label>
    </div>

    <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">
            Comments & Context <span class="normal-case font-normal text-gray-400">(optional)</span>
        </label>
        <textarea
            name="comments"
            rows="2"
            class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 shadow-2xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition-colors"
            placeholder="Add context for BAS calculations, accountant notes, or review flag..."
        >{{ old('comments', $comments ?? ($transaction->comments ?? '')) }}</textarea>
        @error('comments') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
    </div>
</div>
