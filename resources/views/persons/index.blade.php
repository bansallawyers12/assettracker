<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                All Persons
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('persons.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Person
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-blue-50 dark:bg-blue-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($persons->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 text-center">
                    <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No persons found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by adding your first person to the system.</p>
                    <a href="{{ route('persons.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add First Person
                    </a>
                </div>
            @else
                <!-- Persons Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($persons as $person)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 ease-in-out transform hover:scale-105 border-l-4 border-indigo-500">
                            <div class="p-6">
                                <!-- Person Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                            {{ $person->first_name }} {{ $person->last_name }}
                                        </h3>
                                        <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                                            {{ $person->entityPersons->count() }} {{ Str::plural('Role', $person->entityPersons->count()) }}
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('persons.show', $person->id) }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Person Details -->
                                <div class="space-y-3 mb-4">
                                    @if($person->email)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Email</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $person->email }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($person->phone)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Phone</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $person->phone }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($person->date_of_birth)
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Date of Birth</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($person->date_of_birth)->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Business Entities Section -->
                                @if($person->entityPersons->isNotEmpty())
                                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Business Entities</h4>
                                        <div class="space-y-1">
                                            @foreach($person->entityPersons->take(3) as $entityPerson)
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-gray-500 dark:text-gray-400">{{ $entityPerson->businessEntity->legal_name ?? 'Unknown Entity' }}</span>
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">
                                                        {{ $entityPerson->role }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($person->entityPersons->count() > 3)
                                                <div class="text-xs text-gray-400 text-center pt-1">
                                                    +{{ $person->entityPersons->count() - 3 }} more
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('persons.show', $person->id) }}" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-center py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($persons->hasPages())
                    <div class="mt-8">
                        {{ $persons->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>



