{{-- Move asset from trustee company to trust (ownership correction). Hidden until opened from the page toolbar. --}}
<div id="move-to-trust"
     x-show="showMoveToTrust"
     x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-1"
     class="mb-6 overflow-hidden rounded-xl border border-amber-200 bg-amber-50/90 shadow-xs dark:border-amber-800/60 dark:bg-amber-950/30">
    <div class="flex items-start justify-between gap-3 border-b border-amber-200/80 px-5 py-3 dark:border-amber-800/50">
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Move to trust</h3>
            <p class="mt-0.5 text-xs text-amber-800/90 dark:text-amber-200/90">
                Reassigns this asset and related history from
                <strong>{{ $businessEntity->legal_name }}</strong> onto a trust (record correction, not a sale).
                Bank links that are not valid for the trust will be removed.
            </p>
        </div>
        <button type="button"
                @click="closeMoveToTrust()"
                class="rounded-lg p-1.5 text-amber-700/70 hover:bg-amber-100 hover:text-amber-900 dark:text-amber-300 dark:hover:bg-amber-900/40"
                aria-label="Hide move to trust">
            <x-lucide-x class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>

    <form method="POST"
          action="{{ route('business-entities.assets.move-to-trust', [$businessEntity, $asset]) }}"
          class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-end"
          onsubmit="return confirm('Move this asset and its related records to the selected trust?');">
        @csrf
        <div class="min-w-0 flex-1">
            <label for="target_business_entity_id" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                Destination trust
            </label>
            <select id="target_business_entity_id"
                    name="target_business_entity_id"
                    required
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <option value="">Select a trust…</option>
                @foreach ($moveToTrustTargets as $trust)
                    <option value="{{ $trust->id }}"
                        @selected((string) old('target_business_entity_id', $preferredMoveToTrustId) === (string) $trust->id)>
                        {{ $trust->trading_name ?: $trust->legal_name }}
                    </option>
                @endforeach
            </select>
            @error('target_business_entity_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
            Confirm move
        </button>
    </form>
</div>
