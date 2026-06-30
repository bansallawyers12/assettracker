@php
    use App\Models\BankAccount;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ $person->first_name }} {{ $person->last_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-sm border border-green-400 bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-sm border border-red-400 bg-red-100 px-4 py-3 text-red-800">{{ session('error') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                <!-- Person Header -->
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

                <!-- Back to Dashboard -->
                <div class="mb-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors duration-200">
                        <x-lucide-arrow-left class="w-4 h-4 mr-2" />
                        Back to Dashboard
                    </a>
                </div>

                <!-- Roles Summary -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Roles Summary</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $entityPersons->count() }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-400">Total Roles</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-700">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $entityPersons->where('role_status', 'Active')->count() }}</div>
                            <div class="text-sm text-green-600 dark:text-green-400">Active Roles</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $entityPersons->where('role_status', 'Resigned')->count() }}</div>
                            <div class="text-sm text-yellow-600 dark:text-yellow-400">Resigned Roles</div>
                        </div>
                    </div>
                </div>

                <!-- All Roles by Entity -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">All Roles by Entity</h2>
                    
                    @if($groupedRoles->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <x-lucide-users class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                            <p class="text-lg">No roles found for this person.</p>
                        </div>
                    @else
                        @foreach($groupedRoles as $businessEntityId => $entityPersonGroup)
                            @php
                                $businessEntity = $entityPersonGroup->first()->businessEntity;
                            @endphp
                            
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('business-entities.show', $businessEntity->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $businessEntity->legal_name }}
                                        </a>
                                    </h3>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $businessEntity->entity_type ?? 'N/A' }}</span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($entityPersonGroup as $entityPerson)
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    @if($entityPerson->role_status === 'Active') 
                                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @else
                                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @endif">
                                                    {{ $entityPerson->role }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $entityPerson->role_status }}
                                                </span>
                                            </div>
                                            
                                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                @if($entityPerson->appointment_date)
                                                    <div>Appointed: {{ $entityPerson->appointment_date->format('d/m/Y') }}</div>
                                                @endif
                                                @if($entityPerson->resignation_date)
                                                    <div>Resigned: {{ $entityPerson->resignation_date->format('d/m/Y') }}</div>
                                                @endif
                                                @if($entityPerson->shares_percentage)
                                                    <div>Shares: {{ $entityPerson->shares_percentage }}%</div>
                                                @endif
                                                @if($entityPerson->authority_level)
                                                    <div>Authority: {{ $entityPerson->authority_level }}</div>
                                                @endif
                                                @if($entityPerson->asic_due_date)
                                                    <div class="text-red-600 dark:text-red-400 font-medium">
                                                        ASIC Due: {{ $entityPerson->asic_due_date->format('d/m/Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="mt-3 flex gap-2">
                                                <a href="{{ route('entity-persons.show', $entityPerson->id) }}" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline">
                                                    View Details
                                                </a>
                                                <a href="{{ route('entity-persons.edit', $entityPerson->id) }}" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 underline">
                                                    Edit
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Bank Accounts -->
                <div class="mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Bank Accounts</h2>
                        <a href="{{ BankAccount::createUrlForHolder(BankAccount::HOLDER_PERSON, $person->id) }}"
                           class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md transition-colors duration-200"
                           title="Add bank account for {{ $person->first_name }} {{ $person->last_name }}">
                            <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                            Add Account
                        </a>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Accounts held in this person's name. One person can have multiple bank accounts.
                    </p>
                    @include('bank-accounts.partials.holder-grouped-list', [
                        'holderGroups' => $heldBankAccountGroups ?? [],
                        'showScope' => true,
                        'emptyMessage' => 'No bank accounts registered for this person yet.',
                        'emptyCreateUrl' => BankAccount::createUrlForHolder(BankAccount::HOLDER_PERSON, $person->id),
                    ])
                </div>

                <!-- Quick Actions -->
                <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('entity-persons.create', 1) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200">
                            <x-lucide-plus class="w-4 h-4 mr-2" />
                            Add New Role
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
