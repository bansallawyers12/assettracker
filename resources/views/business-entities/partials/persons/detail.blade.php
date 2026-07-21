@php
    use App\Models\BankAccount;
@endphp

<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    @if ($entityPerson->person)
                        {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                    @elseif ($entityPerson->trusteeEntity)
                        {{ $entityPerson->trusteeEntity->legal_name }} (Trustee)
                    @elseif ($entityPerson->appointorEntity)
                        {{ $entityPerson->appointorEntity->legal_name }}
                    @endif
                </h4>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                        {{ $entityPerson->role }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entityPerson->role_status }}</span>
                </div>
            </div>
            @if ($entityPerson->person)
                @include('bank-accounts.partials.account-link-actions', [
                    'associateModal' => true,
                    'associateCreateUrl' => BankAccount::createUrlForHolder(BankAccount::HOLDER_PERSON, $entityPerson->person->id, $businessEntity->id),
                    'associateModalTab' => 'create',
                    'associateTitle' => 'Add bank account for '.$entityPerson->person->first_name.' '.$entityPerson->person->last_name,
                ])
            @endif
        </div>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm">
        @if ($entityPerson->person)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->person->email ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->person->phone_number ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">TFN</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->person->tfn ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">ABN</dt>
                <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->person->abn ?: 'N/A' }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Appointment Date</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->appointment_date?->format('d/m/Y') ?: 'N/A' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Resignation Date</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->resignation_date?->format('d/m/Y') ?: 'N/A' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Shares Percentage</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->shares_percentage ?? 'N/A' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Authority Level</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->authority_level ?? 'N/A' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-gray-500 dark:text-gray-400">ASIC Due Date</dt>
            <dd class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $entityPerson->asic_due_date?->format('d/m/Y') ?: 'N/A' }}</dd>
        </div>
    </dl>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        @if ($entityPerson->role !== 'Appointor')
            <button
                type="button"
                data-persons-action="delete"
                data-entity-person-id="{{ $entityPerson->id }}"
                class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
            >
                Remove Role
            </button>
        @endif
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Close
        </button>
        @if ($entityPerson->role !== 'Appointor')
            <button
                type="button"
                data-persons-action="edit"
                data-entity-person-id="{{ $entityPerson->id }}"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                Edit Role
            </button>
        @endif
    </div>
</div>
