<div class="p-5 sm:p-6">
    <div class="flex items-start gap-3">
        <div class="profile-section-icon profile-section-icon-indigo">
            <x-lucide-user class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Profile information') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __("Update your account's profile information and email address.") }}
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="bank-ws-form mt-6">
        @csrf
        @method('patch')

        <div class="bank-form-section">
            <div class="bank-form-grid">
                <div class="bank-field bank-form-grid-full">
                    <label for="name" class="bank-field-label">{{ __('Full name') }}</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        class="bank-field-control"
                        value="{{ old('name', $user->name) }}"
                    />
                    <x-input-error class="bank-field-error mt-1.5" :messages="$errors->get('name')" />
                </div>

                <div class="bank-field bank-form-grid-full">
                    <label for="email" class="bank-field-label">{{ __('Email address') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="username"
                        class="bank-field-control"
                        value="{{ old('email', $user->email) }}"
                    />
                    <x-input-error class="bank-field-error mt-1.5" :messages="$errors->get('email')" />
                </div>
            </div>
        </div>

        <div class="bank-form-actions">
            <button type="submit" class="bank-btn-primary">{{ __('Save changes') }}</button>
        </div>
    </form>
</div>
