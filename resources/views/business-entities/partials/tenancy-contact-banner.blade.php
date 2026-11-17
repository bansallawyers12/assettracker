@if ($businessEntity->isTenancyContactOnly())
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 px-4 py-3 text-sm text-amber-900 dark:text-amber-100" role="status" data-tenancy-contact-banner>
        <p class="font-medium">{{ __('Tenancy / property manager contact') }}</p>
        <p class="mt-1 text-amber-800 dark:text-amber-200">
            {{ __('This company is a rental agency / property-manager contact, not one of your operating entities. That is why some sections are missing:') }}
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-amber-800 dark:text-amber-200">
            <li>{{ __('Profit & Loss, Balance Sheet, and Manual journals') }}</li>
            <li>{{ __('Creating invoices and linking bank accounts') }}</li>
            <li>{{ __('Company officer / trustee roles on the Persons tab') }}</li>
            <li>{{ __('Main entity list, financial reports, and accounting pickers') }}</li>
        </ul>
        <p class="mt-2 text-amber-800 dark:text-amber-200">
            {{ __('Prefer linking agencies when you add a tenant on a property asset. To treat this record as an operating entity instead, edit the company profile and turn off “Tenancy / property manager contact only”.') }}
        </p>
        <div class="mt-3">
            @can('update', $businessEntity)
                <button type="button" data-entity-profile-edit class="inline-flex items-center rounded-md bg-amber-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-800 dark:bg-amber-600 dark:hover:bg-amber-500">
                    <x-lucide-pencil class="mr-1.5 h-4 w-4" aria-hidden="true" />
                    {{ __('Edit company profile') }}
                </button>
            @endcan
        </div>
    </div>
@endif