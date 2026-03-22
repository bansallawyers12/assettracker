<x-guest-layout>
    <div class="text-center mb-6">
        <div class="flex justify-center mb-3">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Two-Factor Authentication</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Open your authenticator app and enter the 6-digit code.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.totp-verify') }}" id="totp-form">
        @csrf

        {{-- TOTP code input --}}
        <div id="totp-section">
            <x-input-label for="code" :value="__('Verification Code')" />
            <input
                id="code"
                name="code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
            placeholder="000000"
                autofocus
                class="block mt-1.5 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 shadow-sm text-sm placeholder-gray-400 dark:placeholder-gray-500 text-center tracking-widest text-lg font-mono"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-1.5" />
        </div>

        {{-- Backup code section (hidden by default) --}}
        <div id="backup-section" class="hidden mt-4">
            <x-input-label for="backup_code" :value="__('Backup Code')" />
            <input
                id="backup_code"
                name="backup_code"
                type="text"
                autocomplete="off"
                maxlength="8"
                placeholder="XXXXXXXX"
                class="block mt-1.5 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 shadow-sm text-sm placeholder-gray-400 dark:placeholder-gray-500 text-center tracking-widest font-mono uppercase"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-1.5" />
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Enter one of your 8-character backup codes.</p>
        </div>

        <div class="mt-5">
            <x-primary-button class="w-full justify-center py-2.5">
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-4 space-y-2 text-center">
        <button
            type="button"
            id="toggle-backup"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
        >
            Use a backup code instead
        </button>

        <div>
            <a href="{{ route('login') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                &larr; Back to login
            </a>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggle-backup');
        const totpSection = document.getElementById('totp-section');
        const backupSection = document.getElementById('backup-section');
        const totpInput = document.getElementById('code');
        const backupInput = document.getElementById('backup_code');

        let usingBackup = false;

        toggleBtn.addEventListener('click', function () {
            usingBackup = !usingBackup;

            if (usingBackup) {
                totpSection.classList.add('hidden');
                totpInput.removeAttribute('name');
                backupSection.classList.remove('hidden');
                backupInput.setAttribute('name', 'code');
                backupInput.focus();
                toggleBtn.textContent = 'Use authenticator app instead';
            } else {
                backupSection.classList.add('hidden');
                backupInput.removeAttribute('name');
                totpSection.classList.remove('hidden');
                totpInput.setAttribute('name', 'code');
                totpInput.focus();
                toggleBtn.textContent = 'Use a backup code instead';
            }
        });
    </script>
</x-guest-layout>
