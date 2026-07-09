@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ $person->first_name }} {{ $person->last_name }}
        </h2>
    </x-slot>

    <div
        class="person-show-workspace py-12"
        data-person-id="{{ $person->id }}"
        data-roles-url="{{ route('persons.workspace.roles', $person) }}"
        data-entity-picker-url="{{ route('persons.roles.form.create', $person) }}"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div id="person-workspace-alerts"></div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                            {{ $person->first_name }} {{ $person->last_name }}
                        </h1>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                            @if($person->email)
                                <div class="flex items-center">
                                    <x-lucide-mail class="w-4 h-4 mr-2" />
                                    {{ $person->email }}
                                </div>
                            @endif
                            @if($person->phone_number)
                                <div class="flex items-center">
                                    <x-lucide-phone class="w-4 h-4 mr-2" />
                                    {{ $person->phone_number }}
                                </div>
                            @endif
                            @if($person->tfn)
                                <div class="flex items-center">
                                    <x-lucide-file-text class="w-4 h-4 mr-2" />
                                    TFN: {{ $person->tfn }}
                                </div>
                            @endif
                            @if($person->abn)
                                <div class="flex items-center">
                                    <x-lucide-building-2 class="w-4 h-4 mr-2" />
                                    ABN: {{ $person->abn }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-6">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors duration-200">
                            <x-lucide-arrow-left class="w-4 h-4 mr-2" />
                            Back to Dashboard
                        </a>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Roles Summary</h2>
                        @include('persons.partials.summary-stats', ['entityPersons' => $entityPersons])
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">All Roles by Entity</h2>
                        <div data-person-roles-list>
                            @include('persons.partials.roles-list', [
                                'person' => $person,
                                'groupedRoles' => $groupedRoles,
                            ])
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Bank Accounts</h2>
                            <button
                                type="button"
                                data-open-add-bank-account
                                data-bank-modal-tab="create"
                                data-create-url="{{ route('persons.bank-accounts.form.create', $person) }}?holder_type={{ BankAccount::HOLDER_PERSON }}&holder_person_id={{ $person->id }}"
                                class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md transition-colors duration-200"
                                title="Add bank account for {{ $person->first_name }} {{ $person->last_name }}"
                            >
                                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                                Add Account
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Accounts held in this person's name. One person can have multiple bank accounts.
                        </p>
                        <div data-person-bank-accounts-list>
                            @include('persons.partials.bank-accounts.list', [
                                'person' => $person,
                                'holderGroups' => $heldBankAccountGroups ?? [],
                            ])
                        </div>
                    </div>

                    <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Quick Actions</h3>
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                data-person-add-role
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200"
                            >
                                <x-lucide-plus class="w-4 h-4 mr-2" />
                                Add New Role
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('persons.partials.person-bank-account-modal', ['person' => $person])
</x-app-layout>
