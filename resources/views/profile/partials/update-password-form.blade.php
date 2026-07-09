<div class="p-5 sm:p-6">
    <div class="flex items-start gap-3">
        <div class="profile-section-icon profile-section-icon-indigo">
            <x-lucide-key-round class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Update password') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Use a strong, unique password to keep your account secure.') }}
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="bank-ws-form mt-6">
        @csrf
        @method('put')

        <div class="bank-form-section">
            <div class="bank-form-grid">
                <div class="bank-field bank-form-grid-full">
                    <label for="update_password_current_password" class="bank-field-label">{{ __('Current password') }}</label>
                    <input
                        id="update_password_current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        class="bank-field-control"
                    />
                    <x-input-error class="bank-field-error mt-1.5" :messages="$errors->updatePassword->get('current_password')" />
                </div>

                <div class="bank-field">
                    <label for="update_password_password" class="bank-field-label">{{ __('New password') }}</label>
                    <input
                        id="update_password_password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        class="bank-field-control"
                    />
                    <x-input-error class="bank-field-error mt-1.5" :messages="$errors->updatePassword->get('password')" />
                </div>

                <div class="bank-field">
                    <label for="update_password_password_confirmation" class="bank-field-label">{{ __('Confirm password') }}</label>
                    <input
                        id="update_password_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="bank-field-control"
                    />
                    <x-input-error class="bank-field-error mt-1.5" :messages="$errors->updatePassword->get('password_confirmation')" />
                </div>

                <div class="bank-form-grid-full">
                    <x-password-requirements-hint class="bank-field-hint" />
                </div>
            </div>
        </div>

        <div class="bank-form-actions">
            <button type="submit" class="bank-btn-primary">{{ __('Update password') }}</button>
        </div>
    </form>
</div>
