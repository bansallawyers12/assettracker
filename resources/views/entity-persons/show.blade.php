<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $businessEntity->legal_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Left Side: Company Details -->
                <div class="w-full md:w-1/3 bg-white shadow-xs rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Business Entity Details</h3>
                    <div class="mb-4">
                        <p><strong>Legal Name:</strong> {{ $businessEntity->legal_name }}</p>
                        <p><strong>Trading Name:</strong> {{ $businessEntity->trading_name ?? 'N/A' }}</p>
                        <p><strong>Entity Type:</strong> {{ $businessEntity->entity_type }}</p>
                        <p><strong>ABN:</strong> {{ $businessEntity->abn ?? 'N/A' }}</p>
                        <p><strong>ACN:</strong> {{ $businessEntity->acn ?? 'N/A' }}</p>
                        <p><strong>TFN:</strong> {{ $businessEntity->tfn ?? 'N/A' }}</p>
                        <p><strong>Corporate Key:</strong> {{ $businessEntity->corporate_key ?? 'N/A' }}</p>
                        <p><strong>Registered Address:</strong> {{ $businessEntity->registered_address }}</p>
                        <p><strong>Registered Email:</strong> {{ $businessEntity->registered_email }}</p>
                        <p><strong>Phone Number:</strong> {{ $businessEntity->phone_number }}</p>
                        @if ($businessEntity->isCompany())
                        <p><strong>ASIC Renewal Date:</strong> {{ $businessEntity->asic_renewal_date instanceof \Carbon\Carbon ? $businessEntity->asic_renewal_date->format('d/m/Y') : ($businessEntity->asic_renewal_date ?? 'N/A') }}</p>
                        @endif
                        <p><strong>Created by:</strong> {{ $businessEntity->user->name ?? 'Unknown' }}</p>
                    </div>
                    <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Edit</a>
                </div>

                <!-- Right Side: Entity Person Details -->
                <div class="w-full md:w-2/3 bg-white shadow-xs rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Person Details</h3>
                    <div class="mb-4">
                        <p><strong>Name:</strong> 
                            @if ($entityPerson->person)
                                {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                            @elseif ($entityPerson->trusteeEntity)
                                {{ $entityPerson->trusteeEntity->legal_name }} (Trustee)
                            @endif
                        </p>
                        <p><strong>Email:</strong> {{ $entityPerson->person ? $entityPerson->person->email : 'N/A' }}</p>
                        <p><strong>Phone Number:</strong> {{ $entityPerson->person ? $entityPerson->person->phone_number : 'N/A' }}</p>
                        <p><strong>TFN:</strong> {{ $entityPerson->person ? $entityPerson->person->tfn : 'N/A' }}</p>
                        <p><strong>ABN:</strong> {{ $entityPerson->person ? $entityPerson->person->abn : 'N/A' }}</p>
                        <p><strong>Role:</strong> {{ $entityPerson->role }}</p>
                        <p><strong>Appointment Date:</strong> {{ $entityPerson->appointment_date ? $entityPerson->appointment_date->format('d/m/Y') : 'N/A' }}</p>
                        <p><strong>Resignation Date:</strong> {{ $entityPerson->resignation_date ? $entityPerson->resignation_date->format('d/m/Y') : 'N/A' }}</p>
                        <p><strong>Role Status:</strong> {{ $entityPerson->role_status }}</p>
                        <p><strong>Shares Percentage:</strong> {{ $entityPerson->shares_percentage ?? 'N/A' }}</p>
                        <p><strong>Authority Level:</strong> {{ $entityPerson->authority_level ?? 'N/A' }}</p>
                        <p><strong>ASIC Due Date:</strong> {{ $entityPerson->asic_due_date ? $entityPerson->asic_due_date->format('d/m/Y') : 'N/A' }}</p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="{{ route('entity-persons.edit', $entityPerson->id) }}" class="bg-blue-500 text-white px-4 py-2 rounded-sm">Edit</a>
                        <a href="{{ url()->previous() }}" class="bg-gray-500 text-white px-4 py-2 rounded-sm">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>