{{-- Move asset from trustee company to trust (ownership correction). Loaded in the workspace panel. --}}
@php
    $preferredId = $preferredMoveToTrustId ?? null;
@endphp

<form
    class="move-to-trust-ws-form space-y-4"
    method="POST"
    action="{{ route('business-entities.assets.move-to-trust', [$businessEntity, $asset]) }}"
    data-move-to-trust-form
>
    @csrf

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="rounded-lg border border-amber-200 bg-amber-50/90 px-4 py-3 dark:border-amber-800/60 dark:bg-amber-950/30">
        <p class="text-sm text-amber-900 dark:text-amber-100">
            Reassigns this asset and related history from
            <strong>{{ $businessEntity->legal_name }}</strong> onto a trust (record correction, not a sale).
            Bank links that are not valid for the trust will be removed.
        </p>
    </div>

    <div>
        <label for="target_business_entity_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
            Destination trust
        </label>
        <select id="target_business_entity_id"
                name="target_business_entity_id"
                required
                class="block w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Select a trust…</option>
            @foreach ($moveToTrustTargets as $trust)
                <option value="{{ $trust->id }}"
                    @selected((string) old('target_business_entity_id', $preferredId) === (string) $trust->id)>
                    {{ $trust->trading_name ?: $trust->legal_name }}
                </option>
            @endforeach
        </select>
        @error('target_business_entity_id')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button"
                data-entity-panel-close
                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </button>
        <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                data-confirm-message="Move this asset and its related records to the selected trust?">
            Confirm move
        </button>
    </div>
</form>
