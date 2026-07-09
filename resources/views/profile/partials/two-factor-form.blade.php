<div class="p-5 sm:p-6">
    <div class="flex items-start gap-3">
        <div class="profile-section-icon profile-section-icon-emerald">
            <x-lucide-shield-check class="h-5 w-5" aria-hidden="true" />
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Two-factor authentication') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Add an extra layer of security when signing in to your account.') }}
            </p>
        </div>
    </div>

    <div class="mt-6">
        @if ($user->hasFullyEnabledTwoFactor())
            <div class="bank-form-section">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 shrink-0 text-emerald-500">
                            <x-lucide-circle-check class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Two-factor authentication is enabled') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Your account requires a verification code from your authenticator app at sign-in.') }}
                            </p>
                        </div>
                    </div>
                    <span class="profile-badge profile-badge-success shrink-0 self-start">{{ __('Protected') }}</span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-200/80 pt-4 dark:border-gray-700/80">
                    <a href="{{ route('two-factor.manage') }}" class="bank-btn-primary">
                        {{ __('Manage 2FA') }}
                    </a>
                    <a href="{{ route('two-factor.backup-codes') }}" class="bank-btn-secondary">
                        {{ __('Backup codes') }}
                    </a>
                </div>
            </div>
        @else
            <div class="bank-form-section">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 shrink-0 text-gray-400">
                            <x-lucide-lock class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Two-factor authentication is not enabled') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('We recommend enabling 2FA to protect access to your portfolio data.') }}
                            </p>
                        </div>
                    </div>
                    <span class="profile-badge profile-badge-warning shrink-0 self-start">{{ __('Not enabled') }}</span>
                </div>

                <div class="mt-5 border-t border-gray-200/80 pt-4 dark:border-gray-700/80">
                    <a href="{{ route('two-factor.setup') }}" class="bank-btn-primary">
                        {{ __('Enable two-factor authentication') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
