@php
    use App\Models\Transaction;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $asset->name }} ({{ $asset->asset_type }})
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('business-entities.assets.edit', [$asset->business_entity_id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit Asset
                </a>
                <a href="{{ route('business-entities.show', $asset->business_entity_id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    Back to Entity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-blue-50 dark:bg-blue-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Sidebar: Asset Details -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6 border-l-4 border-indigo-500 transition-all duration-200 hover:shadow-xl">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Asset Details</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->asset_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Business Entity</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->businessEntity->legal_name ?? 'Unknown Entity' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Acquisition Date</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->acquisition_date ? $asset->acquisition_date->format('d/m/Y') : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Acquisition Cost</dt>
                                <dd class="text-gray-900 dark:text-gray-200">${{ number_format($asset->acquisition_cost, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Value</dt>
                                <dd class="text-gray-900 dark:text-gray-200">${{ number_format($asset->current_value, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->status }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->description ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Right Content: Tabs and Details -->
                <div class="w-full lg:w-2/3">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6">
                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3 mb-6">
                            @if ($asset->asset_type === 'Car')
                                <button type="button" class="inline-flex items-center px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105" onclick="document.getElementById('upload-form').classList.toggle('hidden')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Document
                                </button>
                            @elseif (in_array($asset->asset_type, ['House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate']))
                                <a href="{{ route('business-entities.assets.tenants.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    Add Tenant
                                </a>
                                <a href="{{ route('business-entities.assets.leases.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Add Lease
                                </a>
                                <button type="button" class="inline-flex items-center px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105" onclick="document.getElementById('upload-form').classList.toggle('hidden')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Document
                                </button>
                            @else
                                <button type="button" class="inline-flex items-center px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105" onclick="document.getElementById('upload-form').classList.toggle('hidden')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Document
                                </button>
                            @endif
                            <form id="upload-form" class="hidden mt-4 w-full bg-gray-50 dark:bg-gray-800 p-4 rounded-lg shadow-inner" method="POST" action="{{ route('business-entities.assets.documents.store', [$businessEntity->id, $asset->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="grid gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Document</label>
                                        <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                                        <select name="document_type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                            <option value="">Select Document Type</option>
                                            <option value="legal">Legal</option>
                                            <option value="financial">Financial</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('document_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                                        <textarea name="description" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" rows="3" placeholder="Enter document description"></textarea>
                                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Name (optional)</label>
                                        <input type="text" name="file_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Enter custom file name (without extension)">
                                        @error('file_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                            Upload
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                            <nav class="flex flex-wrap gap-2" aria-label="Tabs" id="asset-tabs">
                                <a href="#tab_details" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Details</a>
                                @if ($asset->asset_type === 'Car')
                                    <a href="#tab_registration" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Registration</a>
                                    <a href="#tab_insurance" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Insurance</a>
                                    <a href="#tab_service" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Service History</a>
                                @elseif (in_array($asset->asset_type, ['House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate']))
                                    <a href="#tab_tenants" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Tenants</a>
                                    <a href="#tab_leases" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Leases</a>
                                    <a href="#tab_financials" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Financials</a>
                                @else
                                    <a href="#tab_financials" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Financials</a>
                                @endif
                                <a href="#tab_documents" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Documents</a>
                                <a href="#tab_notes" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Notes</a>
                                <a href="#tab_reminders" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Reminders</a>
                                <a href="#tab_emails" class="tab-link px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400 border-b-2 border-transparent hover:border-indigo-500 transition-all duration-200">Emails</a>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content-container">
                            <!-- Details Tab -->
                            <div id="tab_details" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">General Details</h3>
                                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Company</dt>
                                            <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_company ?? 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Due Date</dt>
                                            <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_due_date ? $asset->insurance_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Amount</dt>
                                            <dd class="text-gray-900 dark:text-gray-200">${{ $asset->insurance_amount ? number_format($asset->insurance_amount, 2) : 'N/A' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Car: Registration Tab -->
                            @if ($asset->asset_type === 'Car')
                                <div id="tab_registration" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Registration Details</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Number</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->registration_number ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->registration_due_date ? $asset->registration_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">VicRoads Updated</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->vic_roads_updated ? 'Yes' : 'No' }}</dd>
                                            </div>
                                        </dl>
                                        @if ($asset->registration_due_date)
                                            <div class="mt-4 flex space-x-2">
                                                <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'registration']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Finalize
                                                    </button>
                                                </form>
                                                <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'registration']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Extend (3 days)
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Car: Insurance Tab -->
                                <div id="tab_insurance" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Insurance Details</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Company</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_company ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_due_date ? $asset->insurance_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Amount</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->insurance_amount ? number_format($asset->insurance_amount, 2) : 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                        @if ($asset->insurance_due_date)
                                            <div class="mt-4 flex space-x-2">
                                                <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'insurance']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Finalize
                                                    </button>
                                                </form>
                                                <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'insurance']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Extend (3 days)
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Car: Service History Tab -->
                                <div id="tab_service" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Service History</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">VIN Number</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->vin_number ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mileage</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->mileage ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fuel Type</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->fuel_type ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Service Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->service_due_date ? $asset->service_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                        @if ($asset->service_due_date)
                                            <div class="mt-4 flex space-x-2">
                                                <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'service']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Finalize
                                                    </button>
                                                </form>
                                                <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'service']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Extend (3 days)
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif (in_array($asset->asset_type, ['House Owned', 'House Rented', 'Warehouse', 'Land', 'Office', 'Shop', 'Real Estate']))
                                <!-- Real Estate: Tenants Tab -->
                                <div id="tab_tenants" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tenants</h3>
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('business-entities.assets.leases.sync-from-tenants', [$businessEntity->id, $asset->id]) }}" class="inline" onsubmit="return confirm('Create lease rows from tenant lease/rent data for any tenant that does not have one yet?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200" title="Copies lease start, end, duration, rent, and notes into the Leases tab">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                        </svg>
                                                        Sync to Leases tab
                                                    </button>
                                                </form>
                                                <a href="{{ route('business-entities.assets.tenants.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                    </svg>
                                                    Add Tenant
                                                </a>
                                            </div>
                                        </div>
                                        @if ($asset->tenants->isEmpty())
                                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No tenants yet.</p>
                                        @else
                                            <div class="space-y-4">
                                                @foreach ($asset->tenants as $tenant)
                                                    <div class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">{{ $tenant->name }}</h4>
                                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->email ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->phone ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->address ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lease Start Date</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->move_in_date ? $tenant->move_in_date->format('d/m/Y') : 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lease Duration</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">
                                                                    @if ($tenant->lease_duration_value && $tenant->lease_duration_unit)
                                                                        {{ $tenant->lease_duration_value }} {{ $tenant->lease_duration_unit }}
                                                                    @else
                                                                        {{ $tenant->lease_duration ?? 'N/A' }}
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lease Expiry Date</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->lease_expiry_date ? $tenant->lease_expiry_date->format('d/m/Y') : 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reminder Before Expiry</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">
                                                                    {{ $tenant->lease_expiry_reminder_days !== null ? $tenant->lease_expiry_reminder_days . ' days' : 'N/A' }}
                                                                </dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rent</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">
                                                                    @if ($tenant->rent_amount !== null && $tenant->rent_frequency)
                                                                        ${{ number_format($tenant->rent_amount, 2) }} / {{ strtolower($tenant->rent_frequency) }}
                                                                    @else
                                                                        N/A
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->notes ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Managed by Real Estate</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->is_real_estate_managed ? 'Yes' : 'No' }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Real Estate Company</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->realEstateCompany->legal_name ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div class="col-span-2">
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Real Estate Contacts</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">
                                                                    @if ($tenant->realEstateCompany && $tenant->realEstateCompany->persons->isNotEmpty())
                                                                        @foreach ($tenant->realEstateCompany->persons as $relation)
                                                                            @if ($relation->person)
                                                                                <div class="mb-1">
                                                                                    {{ trim(($relation->person->first_name ?? '') . ' ' . ($relation->person->last_name ?? '')) }}
                                                                                    @if ($relation->person->email)
                                                                                        - {{ $relation->person->email }}
                                                                                    @endif
                                                                                    @if ($relation->person->phone_number)
                                                                                        - {{ $relation->person->phone_number }}
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @else
                                                                        N/A
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Real Estate: Leases Tab -->
                                <div id="tab_leases" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <div class="flex justify-between items-center mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Leases</h3>
                                            <a href="{{ route('business-entities.assets.leases.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Add Lease
                                            </a>
                                        </div>
                                        @if ($asset->leases->isEmpty())
                                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No leases yet.</p>
                                        @else
                                            <div class="space-y-4">
                                                @foreach ($asset->leases as $lease)
                                                    <div class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">Lease with {{ $lease->tenant ? $lease->tenant->name : 'No Tenant' }}</h4>
                                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rental Amount</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">${{ number_format($lease->rental_amount, 2) }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Frequency</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $lease->payment_frequency }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $lease->start_date->format('d/m/Y') }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $lease->end_date ? $lease->end_date->format('d/m/Y') : 'N/A' }}</dd>
                                                            </div>
                                                            <div class="col-span-2">
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Terms</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $lease->terms ?? 'N/A' }}</dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Real Estate: Financials Tab -->
                                <div id="tab_financials" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Financial Details</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->address ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Square Footage</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->square_footage ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Council Rates Amount</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->council_rates_amount ? number_format($asset->council_rates_amount, 2) : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Council Rates Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->council_rates_due_date ? $asset->council_rates_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Owners Corp Amount</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->owners_corp_amount ? number_format($asset->owners_corp_amount, 2) : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Owners Corp Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->owners_corp_due_date ? $asset->owners_corp_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Land Tax Amount</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->land_tax_amount ? number_format($asset->land_tax_amount, 2) : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Land Tax Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->land_tax_due_date ? $asset->land_tax_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SRO Updated</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->sro_updated ? 'Yes' : 'No' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Real Estate Percentage</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->real_estate_percentage ? number_format($asset->real_estate_percentage, 2) . '%' : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rental Income</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->rental_income ? number_format($asset->rental_income, 2) : 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                        @if ($asset->council_rates_due_date || $asset->owners_corp_due_date || $asset->land_tax_due_date)
                                            <div class="mt-4 flex space-x-2 flex-wrap gap-2">
                                                @if ($asset->council_rates_due_date)
                                                    <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'council_rates']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Finalize Council Rates
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'council_rates']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Extend Council Rates (3 days)
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($asset->owners_corp_due_date)
                                                    <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'owners_corp']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Finalize Owners Corp
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'owners_corp']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Extend Owners Corp (3 days)
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($asset->land_tax_due_date)
                                                    <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'land_tax']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Finalize Land Tax
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'land_tax']) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                            Extend Land Tax (3 days)
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- Generic: Financials Tab -->
                                <div id="tab_financials" class="tab-content hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Financial Details</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Company</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_company ?? 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Due Date</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">{{ $asset->insurance_due_date ? $asset->insurance_due_date->format('d/m/Y') : 'N/A' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Insurance Amount</dt>
                                                <dd class="text-gray-900 dark:text-gray-200">${{ $asset->insurance_amount ? number_format($asset->insurance_amount, 2) : 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                        @if ($asset->insurance_due_date)
                                            <div class="mt-4 flex space-x-2">
                                                <form action="{{ route('assets.finalize-due-date', [$businessEntity->id, $asset->id, 'insurance']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Finalize
                                                    </button>
                                                </form>
                                                <form action="{{ route('assets.extend-due-date', [$businessEntity->id, $asset->id, 'insurance']) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm transition-all duration-200">
                                                        Extend (3 days)
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Documents Tab -->
                            <div id="tab_documents" class="tab-content hidden">
                                <div id="document-data" data-entity-id="{{ $businessEntity->id }}" data-entity-name="{{ $businessEntity->legal_name }}" data-asset-id="{{ $asset->id }}" data-asset-name="{{ $asset->name }}" style="display:none;"></div>
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div id="upload-alerts"></div>
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <div class="lg:col-span-1 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                                            <div class="p-4">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Uploaded Documents</h4>
                                                <div id="files-container" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                                                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">Loading documents...</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                                            <div class="p-4">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Document Preview</h4>
                                                <div id="preview-container" class="w-full h-[500px] flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg">
                                                    <div class="text-center text-gray-500 dark:text-gray-400">
                                                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        <p>Select a document to preview</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div id="tab_notes" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notes</h3>
                                        <button type="button" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105" onclick="document.getElementById('note-form').classList.toggle('hidden')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Note
                                        </button>
                                    </div>

                                    <!-- Add Note Form -->
                                    <form id="note-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow" method="POST" action="{{ route('business-entities.assets.notes.store', [$businessEntity->id, $asset->id]) }}#tab_notes">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
                                            <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
                                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                                Save Note
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Notes List -->
                                    @if ($asset->notes->where('is_reminder', false)->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No notes yet.</p>
                                    @else
                                        <div class="space-y-4">
                                            @foreach ($asset->notes->where('is_reminder', false) as $note)
                                                <div class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md transition-all duration-200 hover:shadow-lg">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-grow">
                                                            <p class="text-gray-700 dark:text-gray-200">{{ $note->content }}</p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                                Added by {{ $note->user->name ?? 'Unknown' }} on {{ $note->created_at ? $note->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                            </p>
                                                        </div>
                                                        <form action="{{ route('business-entities.assets.notes.destroy', [$businessEntity->id, $asset->id, $note->id]) }}" method="POST" class="ml-4">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this note?')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Reminders Tab -->
                            <div id="tab_reminders" class="tab-content hidden">
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-md">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-yellow-700 dark:text-yellow-300">Reminders</h3>
                                        <button type="button" class="inline-flex items-center bg-yellow-100 hover:bg-yellow-200 text-yellow-700 dark:bg-yellow-900 dark:hover:bg-yellow-800 dark:text-yellow-200 px-3 py-1 rounded-md text-sm" onclick="document.getElementById('reminder-form').classList.toggle('hidden')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Reminder
                                        </button>
                                    </div>
                                    <form id="reminder-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow" method="POST" action="{{ route('business-entities.assets.notes.store', [$businessEntity->id, $asset->id]) }}#tab_reminders">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reminder</label>
                                            <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
                                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4">
                                            <input type="hidden" name="is_reminder" value="1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                            <input type="date" name="reminder_date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" min="{{ now()->format('Y-m-d') }}" value="{{ old('reminder_date') }}" required>
                                            @error('reminder_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repeat</label>
                                            <select name="repeat_type" id="repeat_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white">
                                                <option value="none">One-off (No repeat)</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="quarterly">Quarterly</option>
                                                <option value="annual">Annual</option>
                                            </select>
                                            @error('repeat_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4" id="repeat_end_date_container" style="display: none;">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date (Optional)</label>
                                            <input type="date" name="repeat_end_date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" min="{{ now()->format('Y-m-d') }}" value="{{ old('repeat_end_date') }}">
                                            @error('repeat_end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow-sm transition duration-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Save Reminder
                                            </button>
                                        </div>
                                    </form>
                                    @if (isset($asset->notes) && $asset->notes->where('is_reminder', true)->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400">No reminders yet.</p>
                                    @else
                                        <div class="space-y-3">
                                            @foreach ($asset->notes->where('is_reminder', true) as $reminder)
                                                <div class="bg-white dark:bg-gray-800 p-4 rounded-md shadow-sm border-l-4 border-yellow-400">
                                                    <p class="text-gray-700 dark:text-gray-200">{{ $reminder->content }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                        Added by {{ $reminder->user->name ?? 'Unknown' }} on {{ $reminder->created_at ? $reminder->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                    </p>
                                                    <div class="mt-2 flex items-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Due: {{ $reminder->reminder_date ? $reminder->reminder_date->format('d/m/Y') : 'N/A' }}
                                                        </span>
                                                        @if($reminder->repeat_type)
                                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                                </svg>
                                                                {{ ucfirst($reminder->repeat_type) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-3 flex space-x-2">
                                                        <form action="{{ route('notes.finalize', $reminder->id) }}#tab_reminders" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200 rounded text-xs">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Finalize
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('notes.extend', $reminder->id) }}#tab_reminders" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 rounded text-xs">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                                </svg>
                                                                Extend
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Emails Tab -->
                            <div id="tab_emails" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Allocated Emails</h3>
                                    @php($allocatedEmails = $asset->mailMessages()->latest('sent_date')->with('labels')->paginate(10))
                                    @if ($allocatedEmails->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No emails allocated yet.</p>
                                    @else
                                        @php($firstEmail = $allocatedEmails->first())
                                        <div class="flex gap-6">
                                            <div class="w-full lg:w-5/12">
                                                <div class="bg-white dark:bg-gray-900 rounded-xl shadow border border-blue-200 dark:border-blue-700 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($allocatedEmails as $email)
                                                        <a href="{{ route('emails.show', $email->id) }}" target="assetEmailViewer" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                            <div class="text-blue-900 dark:text-blue-200 font-semibold">{{ $email->subject ?: '(No subject)' }}</div>
                                                            <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $email->sender_name ?: $email->sender_email }} — {{ optional($email->sent_date)->format('Y-m-d H:i') }}</div>
                                                            <div class="mt-1 flex gap-2 flex-wrap">
                                                                @foreach ($email->labels as $label)
                                                                    <span class="text-xs px-2 py-1 rounded" style="background-color: {{ $label->color ?? '#e5e7eb' }}; color:#111827">{{ $label->name }}</span>
                                                                @endforeach
                                                            </div>
                                                        </a>
                                                    @endforeach
                                                </div>
                                                <div class="mt-4">{{ $allocatedEmails->withQueryString()->links() }}</div>
                                            </div>
                                            <div class="hidden lg:block w-7/12">
                                                <iframe name="assetEmailViewer" class="w-full h-[70vh] bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-700" src="{{ $firstEmail ? route('emails.show', $firstEmail->id) : '' }}"></iframe>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabs = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            function showTab(tabId) {
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                document.getElementById(tabId).classList.remove('hidden');
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('href').substring(1);
                    showTab(tabId);
                });
            });

            // Show tab from URL hash (e.g. after "Sync to Leases tab") or first tab by default
            if (tabs.length > 0) {
                const hash = window.location.hash ? window.location.hash.substring(1) : '';
                const hashEl = hash && document.getElementById(hash);
                if (hashEl && hash.startsWith('tab_')) {
                    showTab(hash);
                } else {
                    const firstTabId = tabs[0].getAttribute('href').substring(1);
                    showTab(firstTabId);
                }
            }

            // Initialize reminder logic
            function initializeReminderLogic() {
                const repeatTypeSelect = document.getElementById('repeat_type');
                const repeatEndDateContainer = document.getElementById('repeat_end_date_container');

                if (repeatTypeSelect && repeatEndDateContainer) {
                    // Set initial state
                    if (repeatTypeSelect.value !== 'none') {
                        repeatEndDateContainer.style.display = 'block';
                    } else {
                        repeatEndDateContainer.style.display = 'none';
                    }

                    // Add event listener
                    repeatTypeSelect.addEventListener('change', function() {
                        if (this.value !== 'none') {
                            repeatEndDateContainer.style.display = 'block';
                        } else {
                            repeatEndDateContainer.style.display = 'none';
                        }
                    });
                }
            }

            // Call the function directly
            initializeReminderLogic();

            // Document upload and preview functionality
            const documentData = document.getElementById('document-data');
            const filesContainer = document.getElementById('files-container');
            const previewContainer = document.getElementById('preview-container');
            const uploadAlerts = document.getElementById('upload-alerts');

            // Function to load documents
            function loadDocuments() {
                const entityId = documentData.dataset.entityId;
                const assetId = documentData.dataset.assetId;

                fetch(`/api/business-entities/${entityId}/assets/${assetId}/documents`)
                    .then(response => response.json())
                    .then(data => {
                        const documents = Array.isArray(data) ? data : (Array.isArray(data.files) ? data.files : []);

                        if (documents.length === 0) {
                            filesContainer.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No documents uploaded yet.</p>';
                            return;
                        }

                        filesContainer.innerHTML = documents.map(doc => `
                            <div class="py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" onclick="previewDocument('${doc.id}')">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">${doc.file_name || doc.name || 'Untitled file'}</span>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${doc.created_at || doc.uploaded || ''}</span>
                                </div>
                            </div>
                        `).join('');
                    })
                    .catch(error => {
                        console.error('Error loading documents:', error);
                        filesContainer.innerHTML = '<p class="text-red-500 text-center py-4">Error loading documents.</p>';
                    });
            }

            // Function to preview document
            window.previewDocument = function(documentId) {
                const entityId = documentData.dataset.entityId;
                const assetId = documentData.dataset.assetId;

                previewContainer.innerHTML = `
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500 mx-auto"></div>
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Loading document...</p>
                    </div>
                `;

                fetch(`/api/business-entities/${entityId}/assets/${assetId}/documents/${documentId}/preview`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.preview_url) {
                            previewContainer.innerHTML = `
                                <iframe src="${data.preview_url}" class="w-full h-full" frameborder="0"></iframe>
                            `;
                        } else {
                            previewContainer.innerHTML = `
                                <div class="text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p>Preview not available</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error previewing document:', error);
                        previewContainer.innerHTML = `
                            <div class="text-center text-red-500">
                                <p>Error loading document preview</p>
                            </div>
                        `;
                    });
            };

            // Load documents on page load
            loadDocuments();
        });
    </script>
    @endpush
</x-app-layout>