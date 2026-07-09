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
                            <button
                                type="button"
                                data-person-role-action="view"
                                data-entity-person-id="{{ $entityPerson->id }}"
                                data-business-entity-id="{{ $businessEntity->id }}"
                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline"
                            >
                                View Details
                            </button>
                            <button
                                type="button"
                                data-person-role-action="edit"
                                data-entity-person-id="{{ $entityPerson->id }}"
                                data-business-entity-id="{{ $businessEntity->id }}"
                                class="text-xs text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 underline"
                            >
                                Edit
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
