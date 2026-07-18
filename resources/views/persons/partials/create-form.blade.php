<form
    class="bank-ws-form persons-index-ws-form space-y-5"
    method="POST"
    action="{{ route('persons.store') }}"
    data-mode="create"
>
    @csrf

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="bank-form-section">
        <p class="bank-form-section-title">Personal information</p>
        <p class="bank-form-section-desc">Basic contact details for this person.</p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field">
                <label for="person_first_name" class="bank-field-label">First name</label>
                <input type="text" id="person_first_name" name="first_name" required class="bank-field-control" value="{{ old('first_name') }}" autocomplete="given-name">
            </div>

            <div class="bank-field">
                <label for="person_last_name" class="bank-field-label">Last name</label>
                <input type="text" id="person_last_name" name="last_name" required class="bank-field-control" value="{{ old('last_name') }}" autocomplete="family-name">
            </div>

            <div class="bank-field">
                <label for="person_email" class="bank-field-label">Email</label>
                <input type="email" id="person_email" name="email" class="bank-field-control" value="{{ old('email') }}" autocomplete="email">
            </div>

            <div class="bank-field">
                <label for="person_phone" class="bank-field-label">Phone</label>
                <input type="text" id="person_phone" name="phone" class="bank-field-control" value="{{ old('phone') }}" autocomplete="tel">
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="person_address" class="bank-field-label">Address</label>
                <div class="au-address-field">
                    <x-google-address-input name="address" id="person_address" :value="old('address')" />
                </div>
            </div>
        </div>
    </div>

    <div class="bank-form-section">
        <p class="bank-form-section-title">First business role</p>
        <p class="bank-form-section-desc">Link this person to a business entity. You can add more roles from their profile later.</p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field bank-form-grid-full">
                <label for="person_business_entity_id" class="bank-field-label">Business entity</label>
                <x-tom-select id="person_business_entity_id" name="business_entity_id" class="bank-field-control" required>
                    <option value="">Select a business entity</option>
                    @foreach ($businessEntities as $entity)
                        <option value="{{ $entity->id }}" @selected(old('business_entity_id') == $entity->id)>
                            {{ $entity->legal_name }} ({{ $entity->entity_type }})
                        </option>
                    @endforeach
                </x-tom-select>
            </div>

            <div class="bank-field">
                <label for="person_role" class="bank-field-label">Role</label>
                <select id="person_role" name="role" required class="bank-field-control">
                    <option value="">Select a role</option>
                    @foreach (\App\Models\EntityPerson::ROLES as $roleOption)
                        <option value="{{ $roleOption }}" @selected(old('role') === $roleOption)>{{ $roleOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bank-field">
                <label for="person_role_status" class="bank-field-label">Role status</label>
                <select id="person_role_status" name="role_status" required class="bank-field-control">
                    @foreach (\App\Models\EntityPerson::ROLE_STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('role_status', 'Active') === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bank-field">
                <label for="person_appointment_date" class="bank-field-label">Appointment date</label>
                <x-date-input id="person_appointment_date" name="appointment_date" class="bank-field-control" required :value="old('appointment_date', now()->format('Y-m-d'))" />
            </div>

            <div class="bank-field">
                <label for="person_asic_due_date" class="bank-field-label">ASIC due date</label>
                <x-date-input id="person_asic_due_date" name="asic_due_date" class="bank-field-control" :value="old('asic_due_date')" />
            </div>
        </div>
    </div>

    <div class="bank-form-actions">
        <button type="button" data-entity-panel-close class="bank-btn-secondary">Cancel</button>
        <button type="submit" data-ws-submit class="bank-btn-primary inline-flex items-center gap-1.5">
            <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
            Create person
        </button>
    </div>
</form>
