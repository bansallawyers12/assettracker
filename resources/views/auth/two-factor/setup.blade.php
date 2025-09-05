<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Setup Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Step 1: Scan QR Code
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Use your authenticator app to scan this QR code.
                        </p>
                    </div>

                    <div class="flex justify-center mb-6">
                        <div class="bg-white p-4 rounded-lg shadow">
                            {!! $qrCodeUrl !!}
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Step 2: Enter Verification Code
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Enter the 6-digit code from your authenticator app to complete setup.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <input type="hidden" name="secret" value="{{ $secret }}">
                        
                        <div>
                            <x-input-label for="code" :value="__('Verification Code')" />
                            <x-text-input id="code" 
                                         class="block mt-1 w-full" 
                                         type="text" 
                                         name="code" 
                                         maxlength="6"
                                         pattern="[0-9]{6}"
                                         placeholder="123456"
                                         required 
                                         autofocus />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('profile.edit') }}" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Enable Two-Factor Authentication') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
