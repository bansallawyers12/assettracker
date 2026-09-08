@php
    use App\Models\BankAccount;
@endphp

@if ($persons->isEmpty())
    <div class="persons-empty-state text-center py-8 px-4">
        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
            <x-lucide-users class="h-6 w-6 text-gray-400" aria-hidden="true" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No persons yet</p>
        @if ($businessEntity->isClosed())
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                {{ __('This entity is closed, so officers cannot be added.') }}
            </p>
        @elseif ($businessEntity->isTenancyContactOnly())
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                {{ __('Officer roles are not used on tenancy / property manager contacts.') }}
            </p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                Persons are directors, shareholders, trustees, or other roles linked to this entity.
            </p>
            <button type="button" data-persons-action="create" class="entity-btn-primary mt-4 inline-flex">
                <x-lucide-user-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                {{ $businessEntity->isTrust() ? 'Add your first person or company' : 'Add your first person' }}
            </button>
        @endif
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach ($persons as $entityPerson)
            @php $isLegacyAppointor = $entityPerson->role === 'Appointor'; @endphp
            <div
                @class([
                    'person-card p-3 rounded-lg border transition-colors',
                    'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50' => $isLegacyAppointor,
                    'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700/80' => ! $isLegacyAppointor,
                ])
                data-entity-person-id="{{ $entityPerson->id }}"
                @if ($isLegacyAppointor) data-legacy-appointor-card @endif
            >
                <div class="flex items-start justify-between gap-2">
                    @if ($isLegacyAppointor)
                        <div class="block flex-1 min-w-0 text-left">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if ($entityPerson->person)
                                    {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                                @elseif ($entityPerson->appointorEntity)
                                    {{ $entityPerson->appointorEntity->legal_name }}
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-100">
                                    Appointor (legacy)
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-amber-800 dark:text-amber-200">
                                {{ __('Managed on the trust company profile, not as an officer role.') }}
                            </p>
                            @can('update', $businessEntity)
                                <button type="button" data-entity-profile-edit class="mt-2 text-xs font-medium text-indigo-600 dark:text-indigo-400 underline">
                                    {{ __('Edit company profile') }}
                                </button>
                            @endcan
                        </div>
                    @else
                        <button
                            type="button"
                            data-persons-action="view"
                            data-entity-person-id="{{ $entityPerson->id }}"
                            class="block flex-1 min-w-0 text-left rounded-md focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                @if ($entityPerson->person)
                                    {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                                @elseif ($entityPerson->trusteeEntity)
                                    {{ $entityPerson->trusteeEntity->legal_name }} (Trustee)
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $entityPerson->role }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entityPerson->role_status }}</span>
                            </div>
                            @if ($entityPerson->asic_due_date)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ASIC Due: {{ $entityPerson->asic_due_date->format('d/m/Y') }}
                                </div>
                            @endif
                        </button>
                        @if ($entityPerson->person)
                            @include('bank-accounts.partials.account-link-actions', [
                                'associateModal' => true,
                                'associateCreateUrl' => BankAccount::createUrlForHolder(BankAccount::HOLDER_PERSON, $entityPerson->person->id, $businessEntity->id),
                                'associateModalTab' => 'create',
                                'associateTitle' => 'Add bank account for '.$entityPerson->person->first_name.' '.$entityPerson->person->last_name,
                            ])
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
