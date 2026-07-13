@php
    $isTrust = $businessEntity->isTrust();
    $isEdit = ($mode ?? 'create') === 'edit';
    $isAppointor = $isEdit && $entityPerson && $entityPerson->role === 'Appointor';
    $isCorporateTrustee = $isEdit && $entityPerson && (bool) $entityPerson->entity_trustee_id;
    $linkType = old('link_type', $isCorporateTrustee ? 'company' : 'person');
    $preselectedPersonId = $preselectedPersonId ?? null;
    $formId = 'persons-ws-form';
    $storeUrl = route('entity-persons.store');
    $updateUrl = $isEdit ? route('entity-persons.update', $entityPerson->id) : null;
@endphp

<form
    id="{{ $formId }}"
    class="persons-ws-form space-y-4"
    method="POST"
    action="{{ $isEdit ? $updateUrl : $storeUrl }}"
    data-mode="{{ $isEdit ? 'edit' : 'create' }}"
    @if ($isEdit) data-entity-person-id="{{ $entityPerson->id }}" @endif
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="business_entity_id" value="{{ $businessEntity->id }}">

    @if (! $isEdit)
        <div class="rounded-lg border border-blue-200/80 bg-blue-50/80 px-3 py-2.5 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-200">
            You can assign multiple roles to the same person. For example, a person can be both a Director and a Shareholder.
        </div>
    @endif

    @if ($isTrust && ! $isAppointor)
        <div class="rounded-lg border border-green-200/80 bg-green-50/80 px-3 py-2.5 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-200">
            <strong>Appointor:</strong> Set on the trust record via
            <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="underline">Edit company profile</a>.
        </div>
    @endif

    @if ($isAppointor)
        <div class="rounded-lg border border-amber-200/80 bg-amber-50/80 px-3 py-2.5 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
            <strong>Legacy appointor record.</strong> Appointor should be managed on the trust record via
            <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="underline">Edit company profile</a>.
        </div>
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div>
        <label for="persons_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
        @if ($isAppointor)
            <input type="hidden" name="role" value="Appointor">
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">Appointor (legacy)</p>
        @else
            <select name="role" id="persons_role" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                @if (! $isEdit)
                    <option value="">Select Role</option>
                @endif
                @foreach (['Director', 'Secretary', 'Shareholder', 'Trustee', 'Beneficiary', 'Settlor', 'Owner'] as $roleOption)
                    <option value="{{ $roleOption }}" @selected(old('role', $entityPerson?->role) === $roleOption)>{{ $roleOption }}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if (! $isAppointor)
        <div id="persons_trustee_link_type_fields" class="hidden">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trustee Type</span>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                    <input type="radio" name="link_type" value="person" class="mr-2 rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($linkType === 'person')>
                    Person
                </label>
                <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                    <input type="radio" name="link_type" value="company" class="mr-2 rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($linkType === 'company')>
                    Company (corporate trustee)
                </label>
            </div>
        </div>

        <div id="persons_trustee_company_selection" class="hidden">
            <label for="persons_entity_trustee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Trustee Company*</label>
            <x-tom-select name="entity_trustee_id" id="persons_entity_trustee_id" data-tomselect-dropdown-parent="body" class="mt-1 rounded-lg">
                <option value="">Select a company</option>
                @foreach ($businessEntities as $entity)
                    <option value="{{ $entity->id }}" @selected((string) old('entity_trustee_id', $entityPerson?->entity_trustee_id) === (string) $entity->id)>{{ $entity->legal_name }} ({{ $entity->entity_type }})</option>
                @endforeach
            </x-tom-select>
        </div>

        <div id="persons_person_link_fields">
            @if (! $isEdit && ! $preselectedPersonId)
                <div>
                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="create_new_person" id="persons_create_new_person" value="1" class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Create New Person
                    </label>
                </div>
            @endif

            @if ($preselectedPersonId)
                <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                    @php
                        $preselectedPerson = $persons->firstWhere('id', $preselectedPersonId);
                    @endphp
                    Assigning role to:
                    <span class="font-medium">{{ $preselectedPerson ? $preselectedPerson->first_name.' '.$preselectedPerson->last_name : 'Selected person' }}</span>
                </div>
            @endif

            <div id="persons_existing_person" class="mt-3">
                <label for="persons_person_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $isEdit ? 'Select Person' : 'Select Existing Person' }}
                </label>
                <x-tom-select name="person_id" id="persons_person_id" data-tomselect-dropdown-parent="body" class="mt-1 rounded-lg">
                    <option value="">Select a person</option>
                    @foreach ($persons as $person)
                        <option value="{{ $person->id }}" @selected((string) old('person_id', $preselectedPersonId ?? $entityPerson?->person_id) === (string) $person->id)>{{ $person->first_name }} {{ $person->last_name }}</option>
                    @endforeach
                </x-tom-select>
            </div>

            @if (! $isEdit && ! $preselectedPersonId)
                <div id="persons_new_person_fields" class="hidden mt-3 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="persons_first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                            <input type="text" name="first_name" id="persons_first_name" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('first_name') }}">
                        </div>
                        <div>
                            <label for="persons_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                            <input type="text" name="last_name" id="persons_last_name" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('last_name') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="persons_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="persons_email" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('email') }}">
                        </div>
                        <div>
                            <label for="persons_phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                            <input type="text" name="phone_number" id="persons_phone_number" maxlength="15" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('phone_number') }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="persons_tfn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">TFN</label>
                            <input type="text" name="tfn" id="persons_tfn" maxlength="9" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('tfn') }}">
                        </div>
                        <div>
                            <label for="persons_abn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ABN</label>
                            <input type="text" name="abn" id="persons_abn" maxlength="11" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('abn') }}">
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        @if ($entityPerson->person_id)
            <input type="hidden" name="person_id" value="{{ $entityPerson->person_id }}">
        @endif
        @if ($entityPerson->appointor_entity_id)
            <input type="hidden" name="appointor_entity_id" value="{{ $entityPerson->appointor_entity_id }}">
        @endif
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300">
            @if ($entityPerson->person)
                {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
            @elseif ($entityPerson->appointorEntity)
                {{ $entityPerson->appointorEntity->legal_name }}
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="persons_appointment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Appointment Date</label>
            <x-date-input name="appointment_date" id="persons_appointment_date" class="mt-1 block w-full" value="{{ old('appointment_date', $entityPerson?->appointment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" />
        </div>
        <div>
            <label for="persons_resignation_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resignation Date</label>
            <x-date-input name="resignation_date" id="persons_resignation_date" class="mt-1 block w-full" value="{{ old('resignation_date', $entityPerson?->resignation_date?->format('Y-m-d')) }}" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="persons_role_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Status</label>
            <select name="role_status" id="persons_role_status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                <option value="Active" @selected(old('role_status', $entityPerson?->role_status ?? 'Active') === 'Active')>Active</option>
                <option value="Resigned" @selected(old('role_status', $entityPerson?->role_status) === 'Resigned')>Resigned</option>
            </select>
        </div>
        <div>
            <label for="persons_shares_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shares Percentage</label>
            <input type="number" name="shares_percentage" id="persons_shares_percentage" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs" value="{{ old('shares_percentage', $entityPerson?->shares_percentage) }}">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="persons_authority_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Authority Level</label>
            <select name="authority_level" id="persons_authority_level" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">None</option>
                <option value="Full" @selected(old('authority_level', $entityPerson?->authority_level) === 'Full')>Full</option>
                <option value="Limited" @selected(old('authority_level', $entityPerson?->authority_level) === 'Limited')>Limited</option>
            </select>
        </div>
        <div>
            <label for="persons_asic_due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ASIC Due Date</label>
            <x-date-input name="asic_due_date" id="persons_asic_due_date" class="mt-1 block w-full" @if(! $isEdit) min="{{ now()->format('Y-m-d') }}" @endif value="{{ old('asic_due_date', $entityPerson?->asic_due_date?->format('Y-m-d')) }}" />
        </div>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
        <button type="button" data-entity-panel-close class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </button>
        <button type="submit" data-ws-submit class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
            {{ $isEdit ? 'Update' : 'Save' }}
        </button>
    </div>
</form>
