<form
    class="bank-ws-form admin-users-ws-form"
    method="POST"
    action="{{ route('admin.users.store') }}"
    data-mode="create"
>
    @csrf

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="bank-form-section">
        <p class="bank-form-section-title">{{ __('Account details') }}</p>
        <p class="bank-form-section-desc">{{ __('New users can sign in immediately with the email and password you set.') }}</p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field bank-form-grid-full">
                <label for="admin_user_name" class="bank-field-label">{{ __('Full name') }}</label>
                <input
                    type="text"
                    id="admin_user_name"
                    name="name"
                    required
                    autocomplete="name"
                    class="bank-field-control"
                    value="{{ old('name') }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="admin_user_email" class="bank-field-label">{{ __('Email address') }}</label>
                <input
                    type="email"
                    id="admin_user_email"
                    name="email"
                    required
                    autocomplete="username"
                    class="bank-field-control"
                    value="{{ old('email') }}"
                />
            </div>

            <div class="bank-field">
                <label for="admin_user_password" class="bank-field-label">{{ __('Password') }}</label>
                <input
                    type="password"
                    id="admin_user_password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="bank-field-control"
                />
            </div>

            <div class="bank-field">
                <label for="admin_user_password_confirmation" class="bank-field-label">{{ __('Confirm password') }}</label>
                <input
                    type="password"
                    id="admin_user_password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="bank-field-control"
                />
            </div>

            <div class="bank-form-grid-full">
                <x-password-requirements-hint class="bank-field-hint" />
            </div>
        </div>
    </div>

    <div class="bank-form-actions">
        <button type="button" data-entity-panel-close class="bank-btn-secondary">{{ __('Cancel') }}</button>
        <button type="submit" data-ws-submit class="bank-btn-primary">{{ __('Create user') }}</button>
    </div>
</form>
