@if ($businessEntity->isClosed())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 dark:border-rose-900/60 dark:bg-rose-950/40 px-4 py-3 text-sm text-rose-900 dark:text-rose-100" role="status" data-closed-entity-banner>
        <p class="font-medium">{{ __('This entity is closed') }}</p>
        <p class="mt-1 text-rose-800 dark:text-rose-200">
            {{ __('Changes are blocked while it stays closed, including:') }}
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-rose-800 dark:text-rose-200">
            <li>{{ __('Adding or editing assets, persons, bank accounts, and invoices') }}</li>
            <li>{{ __('Bank imports, transactions, and other accounting updates') }}</li>
        </ul>
        @if ($businessEntity->closed_date)
            <p class="mt-2 text-rose-800 dark:text-rose-200">
                <span class="font-medium">{{ __('Closed') }}:</span>
                {{ $businessEntity->closed_date->format('d/m/Y') }}
                @if (filled($businessEntity->closed_reason))
                    — {{ $businessEntity->closed_reason }}
                @endif
            </p>
        @endif
        <p class="mt-2 text-rose-800 dark:text-rose-200">
            {{ __('To make changes again, edit the company profile and set Status to Active. That clears the closed date and reopens the entity.') }}
        </p>
        <div class="mt-3">
            @can('update', $businessEntity)
                <button type="button" data-entity-profile-edit class="inline-flex items-center rounded-md bg-rose-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-800 dark:bg-rose-600 dark:hover:bg-rose-500">
                    <x-lucide-pencil class="mr-1.5 h-4 w-4" aria-hidden="true" />
                    {{ __('Reopen via company profile') }}
                </button>
            @else
                <p class="text-xs text-rose-700 dark:text-rose-300">{{ __('Ask a staff user to reopen this entity from the company profile.') }}</p>
            @endcan
        </div>
    </div>
@endif