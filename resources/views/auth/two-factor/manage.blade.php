<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <div class="mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Two-Factor Authentication is Enabled
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Your account is protected with two-factor authentication.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Backup Codes Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Backup Codes
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Use these backup codes to access your account if you lose your authenticator device.
                            </p>
                            
                            <div class="flex space-x-2">
                                <a href="{{ route('two-factor.backup-codes') }}" 
                                   class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    View Backup Codes
                                </a>
                                
                                <form method="POST" action="{{ route('two-factor.regenerate-codes') }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            onclick="return confirm('Are you sure? This will invalidate all existing backup codes.')">
                                        Regenerate Codes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Disable 2FA Section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Disable Two-Factor Authentication
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Disabling two-factor authentication will make your account less secure.
                            </p>
                            
                            <button type="button" 
                                    onclick="document.getElementById('disable-form').classList.toggle('hidden')"
                                    class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 dark:text-red-300 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Disable Two-Factor Authentication
                            </button>

                            <form id="disable-form" method="POST" action="{{ route('two-factor.disable') }}" class="hidden mt-4">
                                @csrf
                                <div>
                                    <x-input-label for="disable_code" :value="__('Enter verification code or backup code')" />
                                    <x-text-input id="disable_code" 
                                                 class="block mt-1 w-full" 
                                                 type="text" 
                                                 name="code" 
                                                 maxlength="8"
                                                 placeholder="123456 or ABC12345"
                                                 required />
                                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                </div>
                                
                                <div class="flex items-center justify-end mt-4">
                                    <button type="button" 
                                            onclick="document.getElementById('disable-form').classList.add('hidden')"
                                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">
                                        Cancel
                                    </button>
                                    <x-danger-button>
                                        {{ __('Disable Two-Factor Authentication') }}
                                    </x-danger-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('profile.edit') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Back to Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
