<x-app-layout>
    <div class="two-factor-setup-page py-8 lg:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <x-lucide-arrow-left class="h-4 w-4" aria-hidden="true" />
                    {{ __('Back to profile') }}
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Set up two-factor authentication') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Protect your account with a 6-digit code from an authenticator app such as Google Authenticator or Authy.') }}
                </p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="profile-page-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
                    <div class="flex items-start gap-3">
                        <div class="profile-section-icon profile-section-icon-emerald shrink-0">
                            <x-lucide-shield-check class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <p class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Authenticator setup') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Complete both steps below to enable 2FA on your account.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-8 p-5 sm:p-6 lg:p-8">
                    <section aria-labelledby="two-factor-step-scan">
                        <div class="flex items-center gap-3">
                            <span class="two-factor-step-badge">1</span>
                            <div>
                                <h2 id="two-factor-step-scan" class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Scan the QR code') }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Open your authenticator app and scan this code.') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col items-center gap-5 sm:flex-row sm:items-start">
                            <div class="two-factor-qr-frame shrink-0">
                                {!! $qrCodeImage !!}
                            </div>

                            <div class="w-full min-w-0 sm:flex-1">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Can\'t scan the code?') }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Enter this secret key manually in your authenticator app:') }}</p>
                                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <code id="two-factor-secret" class="two-factor-secret-key">{{ $secret }}</code>
                                    <button type="button" id="copy-two-factor-secret" class="bank-btn-secondary shrink-0">
                                        {{ __('Copy secret') }}
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Account: :email', ['email' => $user->email]) }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="border-t border-gray-100 pt-8 dark:border-gray-700" aria-labelledby="two-factor-step-verify">
                        <div class="flex items-center gap-3">
                            <span class="two-factor-step-badge">2</span>
                            <div>
                                <h2 id="two-factor-step-verify" class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Verify your setup') }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter the 6-digit code shown in your authenticator app.') }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('two-factor.enable') }}" class="bank-ws-form mt-5">
                            @csrf

                            <div class="bank-form-section max-w-md">
                                <div class="bank-field">
                                    <label for="code" class="bank-field-label">{{ __('Verification code') }}</label>
                                    <input
                                        id="code"
                                        name="code"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        maxlength="6"
                                        pattern="[0-9]{6}"
                                        placeholder="000000"
                                        required
                                        autofocus
                                        class="bank-field-control text-center font-mono text-lg tracking-[0.35em]"
                                    />
                                    <x-input-error :messages="$errors->get('code')" class="bank-field-error mt-1.5" />
                                </div>
                            </div>

                            <div class="bank-form-actions !justify-end">
                                <a href="{{ route('profile.edit') }}" class="bank-btn-secondary">{{ __('Cancel') }}</a>
                                <button type="submit" class="bank-btn-primary inline-flex items-center gap-2">
                                    <x-lucide-shield-check class="h-4 w-4" aria-hidden="true" />
                                    {{ __('Enable two-factor authentication') }}
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('copy-two-factor-secret')?.addEventListener('click', async function () {
            const secret = document.getElementById('two-factor-secret')?.textContent?.trim();
            if (!secret) {
                return;
            }

            try {
                await navigator.clipboard.writeText(secret);
                this.textContent = @json(__('Copied!'));
                setTimeout(() => {
                    this.textContent = @json(__('Copy secret'));
                }, 2000);
            } catch {
                window.prompt(@json(__('Copy this secret:')), secret);
            }
        });
    </script>
    @endpush
</x-app-layout>
