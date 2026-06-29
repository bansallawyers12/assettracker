<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Two-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add additional security to your account using two-factor authentication.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        @if($user->two_factor_enabled)
            <div class="flex items-center">
                <div class="shrink-0">
                    <x-lucide-circle-check class="h-5 w-5 text-green-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        Two-Factor Authentication is enabled
                    </p>
                </div>
            </div>

            <div class="flex space-x-4">
                <a href="{{ route('two-factor.manage') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Manage 2FA
                </a>
                
                <a href="{{ route('two-factor.backup-codes') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-xs text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    View Backup Codes
                </a>
            </div>
        @else
            <div class="flex items-center">
                <div class="shrink-0">
                    <x-lucide-lock class="h-5 w-5 text-gray-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        Two-Factor Authentication is not enabled
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Add an extra layer of security to your account
                    </p>
                </div>
            </div>

            <div>
                <a href="{{ route('two-factor.setup') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Enable Two-Factor Authentication
                </a>
            </div>
        @endif
    </div>
</section>
