{{-- Move asset from trustee company to trust (ownership correction). --}}
<div id="move-to-trust"
     class="mb-6 rounded-xl border border-amber-200 bg-amber-50/80 px-5 py-4 dark:border-amber-800/60 dark:bg-amber-950/30">
    <h3 class="text-base font-semibold text-amber-900 dark:text-amber-100">
        Move to trust
    </h3>
    <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-200/90">
        Reassigns this asset and its related history (transactions, invoices, documents, etc.) from
        <strong>{{ $businessEntity->legal_name }}</strong> onto a trust. This is a record correction, not a sale.
        Bank links that are not valid for the trust will be removed.
    </p>

    <form method="POST"
          action="{{ route('business-entities.assets.move-to-trust', [$businessEntity, $asset]) }}"
          class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
          onsubmit="return confirm('Move this asset and its related records to the selected trust?');">
        @csrf
        <div class="min-w-0 flex-1">
            <label for="target_business_entity_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Destination trust
            </label>
            <select id="target_business_entity_id"
                    name="target_business_entity_id"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
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
                class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
            Confirm move
        </button>
    </form>
</div>
