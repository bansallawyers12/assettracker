<form
    class="bank-ws-form admin-users-ws-form"
    method="POST"
    action="{{ route('admin.users.password', $user) }}"
    data-mode="password"
>
    @csrf
    @method('PATCH')

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="bank-form-section">
        <p class="bank-form-section-title">{{ __('Reset password') }}</p>
        <p class="bank-form-section-desc">
            {{ __('Set a new password for :name. Existing sessions for this user will be signed out.', ['name' => $user->name]) }}
        </p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field">
                <label for="admin_pw_password" class="bank-field-label">{{ __('New password') }}</label>
                <input
                    type="password"
                    id="admin_pw_password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="bank-field-control"
                />
            </div>

            <div class="bank-field">
                <label for="admin_pw_password_confirmation" class="bank-field-label">{{ __('Confirm password') }}</label>
                <input
                    type="password"
                    id="admin_pw_password_confirmation"
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
        <button type="submit" data-ws-submit class="bank-btn-primary">{{ __('Save password') }}</button>
    </div>
</form>
