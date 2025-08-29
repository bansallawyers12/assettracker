@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <!-- Person Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        {{ $person->first_name }} {{ $person->last_name }}
                    </h1>
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                        @if($person->email)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                {{ $person->email }}
                            </div>
                        @endif
                        @if($person->phone_number)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $person->phone_number }}
                            </div>
                        @endif
                        @if($person->tfn)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                TFN: {{ $person->tfn }}
                            </div>
                        @endif
                        @if($person->abn)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                ABN: {{ $person->abn }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Back to Dashboard -->
                <div class="mb-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
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
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
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

                <!-- Quick Actions -->
                <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Quick Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('entity-persons.create', 1) }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add New Role
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
