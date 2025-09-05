@php
    use App\Models\Transaction;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $businessEntity->legal_name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $businessEntity->entity_type }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit Entity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-2 bg-gray-50 dark:bg-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Left Sidebar: Business Details -->
                <div class="w-full lg:w-72 flex-shrink-0">
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Business Details</h3>
                            <a href="{{ route('business-entities.edit', $businessEntity->id) }}" class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Edit
                            </a>
                        </div>
                        <dl class="space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Legal Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-200 font-medium">{{ $businessEntity->legal_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Entity Type</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->entity_type }}</dd>
                                </div>
                            </div>
                            
                            @if($businessEntity->trading_name)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Trading Name</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->trading_name }}</dd>
                            </div>
                            @endif
                            
                            <div class="grid grid-cols-2 gap-2">
                                @if($businessEntity->abn)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">ABN</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-200 font-mono">{{ $businessEntity->abn }}</dd>
                                </div>
                                @endif
                                @if($businessEntity->acn)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">ACN</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-200 font-mono">{{ $businessEntity->acn }}</dd>
                                </div>
                                @endif
                            </div>
                            
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <div class="space-y-2">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Address</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->registered_address }}</dd>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</dt>
                                            <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->registered_email }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</dt>
                                            <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->phone_number }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($businessEntity->asic_renewal_date)
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">ASIC Renewal</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-200">{{ $businessEntity->asic_renewal_date->format('d/m/Y') }}</dd>
                                </div>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Right Content: Tabs and Details -->
                <div class="flex-1 min-w-0">
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3">
                        <!-- Combined Actions and Navigation -->
                        <div class="mb-4">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                @php
                                    $label = (isset($businessEntity->entity_type) && $businessEntity->entity_type == 'Trust') 
                                        ? 'Add Person/Company' 
                                        : 'Add Person';
                                @endphp
                                
                                <!-- Primary Action Buttons -->
                                <a href="{{ route('entity-persons.create', $businessEntity->id) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    {{ $label }}
                                </a>
                                <a href="{{ route('business-entities.assets.create', $businessEntity->id) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Asset
                                </a>
                                
                                <!-- Divider -->
                                <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                                
                                <!-- Tab Navigation -->
                                <nav class="flex flex-wrap gap-1" aria-label="Tabs" id="entity-tabs">
                                    <a href="#tab_assets" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Assets</a>
                                    <a href="#tab_persons" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Persons</a>
                                    <a href="#tab_documents" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Documents</a>
                                    <a href="#tab_notes" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Notes</a>
                                    <a href="#tab_reminders" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Reminders</a>
                                    <a href="#tab_contact_lists" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Contact Lists</a>
                                    <a href="#tab_compose_email" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Compose Email</a>
                                    <a href="#tab_emails" class="tab-link px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Emails</a>
                                </nav>
                            </div>
                            
                            <!-- Secondary Actions Row -->
                            <div class="flex flex-wrap gap-2">
                                <a href="#tab_notes" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Add Note
                                </a>
                                <a href="#tab_documents" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload
                                </a>
                                <a href="#tab_contact_lists" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Contacts
                                </a>
                                <a href="#tab_compose_email" class="inline-flex items-center px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs font-medium transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Email
                                </a>
                            </div>
                            
                            <!-- Upload Form (Hidden by default) -->
                            <form id="upload-form" class="hidden w-full bg-gray-50 dark:bg-gray-800 p-4 rounded-lg shadow-inner mt-3" method="POST" action="{{ route('business-entities.upload-document', $businessEntity->id) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="grid gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Document</label>
                                        <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                                        <select name="document_type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                                            <option value="">Select Document Type</option>
                                            <option value="legal">Legal</option>
                                            <option value="financial">Financial</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('document_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                                        <textarea name="description" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" rows="3" placeholder="Enter document description"></textarea>
                                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Name (optional)</label>
                                        <input type="text" name="file_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Enter custom file name (without extension)">
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

                        <!-- Quick Accounting Links -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Quick Access</h3>
                                <a href="{{ route('dashboard') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200">View Full Dashboard →</a>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('chart-of-accounts.index') }}" class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-700 dark:text-green-300 rounded text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    Chart of Accounts
                                </a>
                                <a href="{{ route('business-entities.tracking-categories.index', $businessEntity) }}" class="inline-flex items-center px-2 py-1 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Tracking Categories
                                </a>
                                <a href="{{ route('bank-accounts.index') }}" class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Bank Accounts
                                </a>
                                <a href="{{ route('transactions.index') }}" class="inline-flex items-center px-2 py-1 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Transactions
                                </a>
                                <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-2 py-1 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Invoices
                                </a>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content-container">
                            <!-- Assets Tab -->
                            <div id="tab_assets" class="tab-content hidden">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Assets</h3>
                                        <a href="{{ route('business-entities.assets.create', $businessEntity->id) }}#tab_assets" class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-medium transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Asset
                                        </a>
                                    </div>
                                    @if (isset($assets) && $assets->isEmpty())
                                        <div class="text-center py-6">
                                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No assets yet</p>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach ($assets as $asset)
                                                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $asset->id]) }}#tab_assets" class="block">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $asset->name }}</div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $asset->asset_type }}</div>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Persons Tab -->
                            <div id="tab_persons" class="tab-content hidden">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Persons</h3>
                                        <a href="{{ route('entity-persons.create', $businessEntity->id) }}#tab_persons" class="inline-flex items-center px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                            Add Person
                                        </a>
                                    </div>
                                    @if (isset($persons) && $persons->isEmpty())
                                        <div class="text-center py-6">
                                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No persons yet</p>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach ($persons as $entityPerson)
                                                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                    <a href="{{ route('entity-persons.show', $entityPerson->id) }}#tab_persons" class="block">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            @if ($entityPerson->person)
                                                                {{ $entityPerson->person->first_name }} {{ $entityPerson->person->last_name }}
                                                            @elseif ($entityPerson->trusteeEntity)
                                                                {{ $entityPerson->trusteeEntity->legal_name }} (Trustee)
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                                {{ $entityPerson->role }}
                                                            </span>
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entityPerson->role_status }}</span>
                                                        </div>
                                                        @if ($entityPerson->asic_due_date)
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ASIC Due: {{ $entityPerson->asic_due_date->format('d/m/Y') }}</div>
                                                        @endif
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>


                            <!-- Transactions Tab -->
                            <div id="tab_transactions" class="tab-content hidden">
                                <div class="space-y-3">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Transactions</h3>
                                    @if ($transactions->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No transactions yet.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
                                                <thead class="bg-indigo-50 dark:bg-indigo-900/50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Date</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Amount</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Description</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Type</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Status</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($transactions as $transaction)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->date->format('d/m/Y') }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->amount }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->description }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ Transaction::$transactionTypes[$transaction->transaction_type] ?? 'Unknown' }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                                @if ($transaction->bankStatementEntries()->exists())
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                        Matched
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                                        Unmatched
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                                <div class="flex flex-wrap gap-2">
                                                                    <a href="{{ route('business-entities.bank-accounts.transactions.show', [$businessEntity->id, $bankAccounts->first()->id ?? 0, $transaction->id]) }}" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                        </svg>
                                                                        Edit
                                                                    </a>
                                                                    <a href="{{ route('business-entities.show', [$businessEntity->id, 'transaction_id' => $transaction->id]) }}#tab_transactions" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                        </svg>
                                                                        View
                                                                    </a>
                                                                    @if (!$transaction->bankStatementEntries()->exists())
                                                                        <form action="{{ route('business-entities.transactions.match', [$businessEntity->id, $transaction->id]) }}" method="POST" class="inline-flex items-center">
                                                                            @csrf
                                                                            <select name="bank_statement_entry_id" class="border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs mr-1 focus:ring-indigo-500 focus:border-indigo-500">
                                                                                <option value="">Match to Entry</option>
                                                                                @foreach ($bankAccounts as $bankAccount)
                                                                                    @foreach ($bankAccount->bankStatementEntries()->whereNull('transaction_id')->get() as $entry)
                                                                                        <option value="{{ $entry->id }}">{{ $entry->description }} ({{ $entry->amount }}) - {{ $bankAccount->bank_name }}</option>
                                                                                    @endforeach
                                                                                @endforeach
                                                                            </select>
                                                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs">
                                                                                Match
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                    @if ($transaction->receipt_path)
                                                                        <a href="{{ $transaction->receiptUrl }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                            </svg>
                                                                            Receipt
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @if (request()->has('transaction_id'))
                                            @php $selectedTransaction = $transactions->firstWhere('id', request('transaction_id')); @endphp
                                            @if ($selectedTransaction)
                                                <div class="mt-4 p-4 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Transaction Details</h4>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">Date:</span> {{ $selectedTransaction->date->format('d/m/Y') }}</p>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">Amount:</span> {{ $selectedTransaction->amount }}</p>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">Description:</span> {{ $selectedTransaction->description }}</p>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">Type:</span> {{ Transaction::$transactionTypes[$selectedTransaction->transaction_type] ?? 'N/A' }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">GST Amount:</span> {{ $selectedTransaction->gst_amount ?? 'N/A' }}</p>
                                                            <p class="mb-2"><span class="font-medium text-gray-700 dark:text-gray-300">GST Status:</span> {{ $selectedTransaction->gst_status ?? 'N/A' }}</p>
                                                            @if ($selectedTransaction->receipt_path)
                                                                <p class="mb-2">
                                                                    <span class="font-medium text-gray-700 dark:text-gray-300">Receipt:</span>
                                                                    <a href="{{ $transaction->receiptUrl }}" target="_blank" class="text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">View Receipt</a>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Chart of Accounts Tab -->
                            <div id="tab_chart_of_accounts" class="tab-content hidden">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Chart of Accounts</h3>
                                        <a href="{{ route('business-entities.chart-of-accounts.create', $businessEntity->id) }}#tab_chart_of_accounts" class="inline-flex items-center px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Account
                                        </a>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                                        <div class="text-center">
                                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Chart of Accounts management is available. Click "Add Account" to get started.</p>
                                            <a href="{{ route('business-entities.chart-of-accounts.index', $businessEntity) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-medium transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Manage Chart of Accounts
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoices Tab -->
                            <div id="tab_invoices" class="tab-content hidden">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Invoices</h3>
                                        <a href="{{ route('business-entities.invoices.create', $businessEntity->id) }}#tab_invoices" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Create Invoice
                                        </a>
                                    </div>
                                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-4">
                                        <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Invoice management is available. Create, edit, and track your invoices.
                                        </p>
                                        <div class="text-center">
                                            <a href="{{ route('business-entities.invoices.index', $businessEntity->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Manage Invoices
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Reports Tab -->
                            <div id="tab_financial_reports" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Financial Reports</h3>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Profit & Loss</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">View income and expenses</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.profit-loss', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Balance Sheet</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Assets, liabilities & equity</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.balance-sheet', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cash Flow</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Cash inflows and outflows</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.cash-flow', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900 dark:hover:bg-purple-800 dark:text-purple-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tracking Categories</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Owner & Property reports</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.tracking-categories', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 dark:bg-orange-900 dark:hover:bg-orange-800 dark:text-orange-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div id="tab_documents" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Documents</h3>
                                        <button onclick="document.getElementById('upload-form').classList.toggle('hidden')" class="inline-flex items-center px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Add Document
                                        </button>
                                    </div>

                                    <!-- Upload Form -->
                                    <form id="upload-form" class="hidden w-full bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md mb-6" method="POST" action="{{ route('business-entities.upload-document', $businessEntity->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="grid gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Document</label>
                                                <input type="file" name="document" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                                @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Type</label>
                                                <select name="document_type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                                                    <option value="">Select Document Type</option>
                                                    <option value="legal">Legal</option>
                                                    <option value="financial">Financial</option>
                                                    <option value="other">Other</option>
                                                </select>
                                                @error('document_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                                                <textarea name="description" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" rows="3" placeholder="Enter document description"></textarea>
                                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Name (optional)</label>
                                                <input type="text" name="file_name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Enter custom file name (without extension)">
                                                @error('file_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                                    Upload
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    @if (!isset($documents) || $documents->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No documents yet.</p>
                                    @else
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                            <div class="lg:col-span-1 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                                                <div class="p-4">
                                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Uploaded Documents</h4>
                                                    <div id="entity-files-container" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                                                        @foreach ($documents as $document)
                                                            <div class="py-3 px-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" onclick="previewEntityDocumentByPath('{{ $document->path }}', '{{ addslashes($document->file_name) }}')">
                                                                <div class="flex items-start justify-between">
                                                                    <div class="flex items-start space-x-3">
                                                                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                        </svg>
                                                                        <div>
                                                                            <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $document->file_name }}</div>
                                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                                @php
                                                                                    $size = null;
                                                                                    try { $size = \Illuminate\Support\Facades\Storage::disk('s3')->size($document->path); } catch (Exception $e) { $size = null; }
                                                                                @endphp
                                                                                @if($size)
                                                                                    {{ number_format($size/1024, 2) }} KB
                                                                                @endif
                                                                                <span class="ml-2">{{ strtolower($document->type) }}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $document->created_at->format('Y-m-d H:i') }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                                    <div class="flex items-center justify-between">
                                                        <h4 id="entity-document-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Document Preview</h4>
                                                    </div>
                                                    <div class="mt-3 flex gap-3">
                                                        <a id="entity-document-download" href="#" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg shadow-md transition-all duration-200 disabled:opacity-50" aria-disabled="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                            </svg>
                                                            Download
                                                        </a>
                                                        <button id="entity-document-delete" type="button" class="inline-flex items-center px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-md transition-all duration-200 disabled:opacity-50" disabled>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            Delete Document
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="h-[520px]">
                                                    <iframe id="entity-document-frame" class="w-full h-full" frameborder="0"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div id="tab_notes" class="tab-content hidden">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-md">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300">Notes</h3>
                                        <button type="button" class="inline-flex items-center bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-md text-sm" onclick="document.getElementById('note-form').classList.toggle('hidden')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Note
                                        </button>
                                    </div>
                                    <form id="note-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow" method="POST" action="{{ route('business-entities.notes.store', $businessEntity->id) }}#tab_notes">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
                                            <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
                                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md shadow-sm transition duration-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Save Note
                                            </button>
                                        </div>
                                    </form>
                                    @if (isset($notes) && $notes->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400">No notes yet.</p>
                                    @else
                                        <div class="space-y-3">
                                            @foreach ($notes as $note)
                                                <div class="bg-white dark:bg-gray-800 p-4 rounded-md shadow-sm">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-grow">
                                                            <p class="text-gray-700 dark:text-gray-200">{{ $note->content }}</p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                                Added by {{ $note->user->name ?? 'Unknown' }} on {{ $note->created_at ? $note->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                            </p>
                                                        </div>
                                                        <form action="{{ route('business-entities.notes.destroy', [$businessEntity->id, $note->id]) }}" method="POST" class="ml-4">
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

                            <!-- Reminders Tab (Missing) -->
                            <div id="tab_reminders" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Reminders</h3>
                                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">Reminders content will go here.</p>
                                </div>
                            </div>

                            <!-- Contact Lists Tab -->
                            <div id="tab_contact_lists" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contact Lists</h3>
                                        <a href="{{ route('business-entities.contact-lists.create', $businessEntity->id) }}#tab_contact_lists" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Add Contact
                                        </a>
                                    </div>
                                    @if ($contactLists->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No contact lists yet.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
                                                <thead class="bg-indigo-50 dark:bg-indigo-900/50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">First Name</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Last Name</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Email</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Phone</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($contactLists as $contactList)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->first_name }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->last_name }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->email }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $contactList->phone_no }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                                <a href="{{ route('business-entities.contact-lists.edit', [$businessEntity->id, $contactList->id]) }}#tab_contact_lists" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 dark:bg-indigo-900 dark:hover:bg-indigo-800 dark:text-indigo-200 rounded text-xs">
                                                                    Edit
                                                                </a>
                                                                <form action="{{ route('business-entities.contact-lists.destroy', [$businessEntity->id, $contactList->id]) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="inline-flex items-center px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900 dark:hover:bg-red-800 dark:text-red-200 rounded text-xs ml-2">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        {{ $contactLists->links() }}
                                    @endif
                                </div>
                            </div>

                            <!-- Compose Email Tab -->
                            <div id="tab_compose_email" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Compose Email</h3>
                                    <form id="compose-email-form" enctype="multipart/form-data">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label for="to_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To *</label>
                                                <input type="email" id="to_email" name="to_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" value="{{ $businessEntity->registered_email }}" readonly required>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label for="cc_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CC</label>
                                                <input type="email" id="cc_email" name="cc_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject *</label>
                                            <input type="text" id="subject" name="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message *</label>
                                            <textarea id="message" name="message" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label for="attachment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attachment</label>
                                            <input type="file" id="attachment" name="attachments[]" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" multiple>
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                                Send Email
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Emails Tab -->
                            <div id="tab_emails" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Allocated Emails</h3>
                                    @php($allocatedEmails = $businessEntity->mailMessages()->latest('sent_date')->with('labels')->paginate(10))
                                    @if ($allocatedEmails->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No emails allocated yet.</p>
                                    @else
                                        @php($firstEmail = $allocatedEmails->first())
                                        <div class="flex gap-6">
                                            <div class="w-full lg:w-5/12">
                                                <div class="bg-white dark:bg-gray-900 rounded-xl shadow border border-blue-200 dark:border-blue-700 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($allocatedEmails as $email)
                                                        <a href="{{ route('emails.show', $email->id) }}" target="beEmailViewer" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                                <iframe name="beEmailViewer" class="w-full h-[70vh] bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-700" src="{{ $firstEmail ? route('emails.show', $firstEmail->id) : '' }}"></iframe>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Bank Import Tab -->
                            <div id="tab_bank_import" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bank Statement Import</h3>
                                        <button id="upload-statement-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-md transition-all duration-200 transform hover:scale-105">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            Upload Statement
                                        </button>
                                    </div>

                                    <!-- Upload Form (Hidden by default) -->
                                    <div id="upload-form" class="hidden mb-6 bg-white dark:bg-gray-900 p-6 rounded-lg shadow-md">
                                        <form id="bank-import-form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Bank Account *</label>
                                                    <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                                                        <option value="">Choose a bank account...</option>
                                                        @foreach($businessEntity->bankAccounts as $bankAccount)
                                                            <option value="{{ $bankAccount->id }}">{{ $bankAccount->bank_name }} - {{ $bankAccount->account_number }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="statement_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statement File *</label>
                                                    <input type="file" id="statement_file" name="statement_file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Supported formats: Excel (.xlsx, .xls) or CSV files</p>
                                                </div>
                                            </div>
                                            <div class="flex justify-end space-x-3">
                                                <button type="button" id="cancel-upload" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-colors">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                                    Process File
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Imported Statements List -->
                                    <div id="imported-statements" class="space-y-4">
                                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Recent Imports</h4>
                                        <div id="statements-list" class="space-y-2">
                                            <!-- Statements will be loaded here via AJAX -->
                                        </div>
                                    </div>

                                    <!-- Statement Entries Matching Interface -->
                                    <div id="matching-interface" class="hidden mt-6">
                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6">
                                            <div class="flex justify-between items-center mb-4">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Match Bank Entries</h4>
                                                <div class="flex space-x-2">
                                                    <button id="auto-match-btn" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm transition-colors">
                                                        Auto Match
                                                    </button>
                                                    <button id="save-matches-btn" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm transition-colors">
                                                        Save Matches
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                <!-- Bank Entries Column -->
                                                <div>
                                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Bank Statement Entries</h5>
                                                    <div id="bank-entries-list" class="space-y-2 max-h-96 overflow-y-auto">
                                                        <!-- Bank entries will be loaded here -->
                                                    </div>
                                                </div>
                                                
                                                <!-- Chart of Accounts Column -->
                                                <div>
                                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Chart of Accounts</h5>
                                                    <div class="mb-3">
                                                        <input type="text" id="account-search" placeholder="Search accounts..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    </div>
                                                    <div id="chart-accounts-list" class="space-y-1 max-h-96 overflow-y-auto">
                                                        <!-- Chart of accounts will be loaded here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> {{-- End of tab-content-container --}}
                    </div> {{-- End of right content card --}}
                </div> {{-- End of right content column --}}
            </div> {{-- End of main flex container --}}
        </div> {{-- End of max-w-7xl container --}}
    </div> {{-- End of py-8 background div --}}

    <!-- Summernote CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            const composeEmailForm = document.getElementById('compose-email-form');
            const fromEmailSelect = document.getElementById('from_email');
            const subjectInput = document.getElementById('subject');
            // const messageTextarea = document.getElementById('message'); // No longer needed directly for getting/setting content after Summernote init

            // Function to switch tabs
            function switchTab(targetId) {
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });

                // Show only the selected tab content
                const selectedTab = document.getElementById(targetId);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                }

                // Update tab styles
                tabs.forEach(tab => {
                    tab.classList.remove('border-indigo-500', 'text-indigo-600', 'dark:text-indigo-400');
                    tab.classList.add('border-transparent', 'text-gray-600', 'dark:text-gray-300');
                });

                const activeTab = document.querySelector(`.tab-link[href="#${targetId}"]`);
                if (activeTab) {
                    activeTab.classList.add('border-indigo-500', 'text-indigo-600', 'dark:text-indigo-400');
                    activeTab.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-300');
                }
            }

            // Event listeners for tabs
            tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    switchTab(targetId);
                    // Update URL hash without triggering page reload
                    history.pushState(null, null, `#${targetId}`);
                });
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                const targetId = window.location.hash.substring(1) || 'tab_assets';
                switchTab(targetId);
            });

            // Activate tab based on URL hash on page load
            const initialTab = window.location.hash ? window.location.hash.substring(1) : 'tab_assets';
            switchTab(initialTab);

            // Fetch data for Compose Email tab
            if (document.getElementById('tab_compose_email')) {
                fetch("{{ route('business-entities.compose-email-data', $businessEntity->id) }}")
                    .then(response => response.json())
                    .then(data => {
                        // Populate 'From' select
                        data.senderEmails.forEach(email => {
                            const option = document.createElement('option');
                            option.value = email;
                            option.textContent = email;
                            fromEmailSelect.appendChild(option);
                        });

                        // Populate 'Templates' select
                        // data.emailTemplates.forEach(template => { // Removed
                        //     const option = document.createElement('option');
                        //     option.value = template.id;
                        //     option.textContent = template.name;
                        //     templateSelect.appendChild(option);
                        // });

                        // Store templates data for easy lookup
                        // templateSelect.dataset.templates = JSON.stringify(data.emailTemplates); // Removed
                    })
                    .catch(error => console.error('Error fetching email compose data:', error));
            }

            // Template selection change listener
            // templateSelect.addEventListener('change', function () { // Removed
            //     const selectedTemplateId = this.value;
            //     const templates = JSON.parse(this.dataset.templates);
            //     const selectedTemplate = templates.find(template => template.id == selectedTemplateId);

            //     if (selectedTemplate) {
            //         subjectInput.value = selectedTemplate.subject;
            //         $('#message').summernote('code', selectedTemplate.description); // Set content using Summernote API
            //     } else {
            //         subjectInput.value = '';
            //         $('#message').summernote('code', ''); // Clear content using Summernote API
            //     }
            // });

            // Handle form submission
            composeEmailForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.set('message', $('#message').summernote('code')); // Get content from Summernote

                // Add business entity ID to form data
                formData.append('_method', 'POST');
                formData.append('business_entity_id', "{{ $businessEntity->id }}");

                fetch("{{ route('business-entities.send-email', $businessEntity->id) }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    alert(data.message);
                    composeEmailForm.reset();
                    $('#message').summernote('code', ''); // Clear Summernote editor after submission
                })
                .catch(error => {
                    console.error('Error sending email:', error);
                    alert('Error sending email.');
                });
            });

            // Initialize Summernote
            $('#message').summernote({
                placeholder: 'Write your message here...',
                tabsize: 2,
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'code', 'help']]
                ]
            });

            // Bank Import functionality
            const uploadStatementBtn = document.getElementById('upload-statement-btn');
            const uploadForm = document.getElementById('upload-form');
            const cancelUploadBtn = document.getElementById('cancel-upload');
            const bankImportForm = document.getElementById('bank-import-form');
            const matchingInterface = document.getElementById('matching-interface');
            const autoMatchBtn = document.getElementById('auto-match-btn');
            const saveMatchesBtn = document.getElementById('save-matches-btn');

            // Show/hide upload form
            uploadStatementBtn.addEventListener('click', function() {
                uploadForm.classList.remove('hidden');
                uploadStatementBtn.classList.add('hidden');
            });

            cancelUploadBtn.addEventListener('click', function() {
                uploadForm.classList.add('hidden');
                uploadStatementBtn.classList.remove('hidden');
                bankImportForm.reset();
            });

            // Handle bank import form submission
            bankImportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;

                fetch('{{ route("business-entities.bank-import.process", $businessEntity->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        uploadForm.classList.add('hidden');
                        uploadStatementBtn.classList.remove('hidden');
                        bankImportForm.reset();
                        
                        // Show matching interface
                        matchingInterface.classList.remove('hidden');
                        loadBankEntries(data.bankAccountId);
                        loadChartOfAccounts();
                        
                        alert('File processed successfully! ' + data.entriesCount + ' entries found.');
                    } else {
                        alert('Error processing file: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error processing file. Please try again.');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            });

            // Load bank entries for matching
            function loadBankEntries(bankAccountId) {
                fetch(`{{ route("business-entities.bank-import.entries", $businessEntity->id) }}?bank_account_id=${bankAccountId}`)
                .then(response => response.json())
                .then(data => {
                    const bankEntriesList = document.getElementById('bank-entries-list');
                    bankEntriesList.innerHTML = '';
                    
                    data.entries.forEach(entry => {
                        const entryDiv = document.createElement('div');
                        entryDiv.className = 'p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 bank-entry';
                        entryDiv.dataset.entryId = entry.id;
                        entryDiv.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${entry.description}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">${entry.date} • ${entry.transaction_type}</div>
                                </div>
                                <div class="text-sm font-semibold ${entry.amount >= 0 ? 'text-green-600' : 'text-red-600'}">
                                    ${entry.amount >= 0 ? '+' : ''}$${Math.abs(entry.amount).toFixed(2)}
                                </div>
                            </div>
                            <div class="mt-2">
                                <select class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 account-select">
                                    <option value="">Select account...</option>
                                </select>
                            </div>
                        `;
                        bankEntriesList.appendChild(entryDiv);
                    });
                })
                .catch(error => {
                    console.error('Error loading bank entries:', error);
                });
            }

            // Load chart of accounts
            function loadChartOfAccounts() {
                fetch(`{{ route("business-entities.chart-of-accounts.api", $businessEntity) }}`)
                .then(response => response.json())
                .then(data => {
                    const chartAccountsList = document.getElementById('chart-accounts-list');
                    chartAccountsList.innerHTML = '';
                    
                    data.accounts.forEach(account => {
                        const accountDiv = document.createElement('div');
                        accountDiv.className = 'p-2 border border-gray-200 dark:border-gray-600 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 chart-account';
                        accountDiv.dataset.accountId = account.id;
                        accountDiv.innerHTML = `
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${account.account_name}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${account.account_code} • ${account.account_type}</div>
                        `;
                        chartAccountsList.appendChild(accountDiv);
                    });

                    // Populate account selects in bank entries
                    populateAccountSelects(data.accounts);
                })
                .catch(error => {
                    console.error('Error loading chart of accounts:', error);
                });
            }

            // Populate account select dropdowns
            function populateAccountSelects(accounts) {
                const accountSelects = document.querySelectorAll('.account-select');
                accountSelects.forEach(select => {
                    accounts.forEach(account => {
                        const option = document.createElement('option');
                        option.value = account.id;
                        option.textContent = `${account.account_code} - ${account.account_name}`;
                        select.appendChild(option);
                    });
                });
            }

            // Auto match functionality
            autoMatchBtn.addEventListener('click', function() {
                // Simple auto-matching logic based on amount and description keywords
                const bankEntries = document.querySelectorAll('.bank-entry');
                const chartAccounts = document.querySelectorAll('.chart-account');
                
                bankEntries.forEach(entry => {
                    const amount = parseFloat(entry.querySelector('.text-green-600, .text-red-600').textContent.replace(/[+$]/g, ''));
                    const description = entry.querySelector('.text-sm.font-medium').textContent.toLowerCase();
                    const select = entry.querySelector('.account-select');
                    
                    // Simple matching logic - can be enhanced
                    let matchedAccount = null;
                    
                    if (amount > 0) {
                        // Income accounts for positive amounts
                        matchedAccount = Array.from(chartAccounts).find(acc => 
                            acc.textContent.toLowerCase().includes('income') || 
                            acc.textContent.toLowerCase().includes('revenue')
                        );
                    } else {
                        // Expense accounts for negative amounts
                        matchedAccount = Array.from(chartAccounts).find(acc => 
                            acc.textContent.toLowerCase().includes('expense') || 
                            acc.textContent.toLowerCase().includes('cost')
                        );
                    }
                    
                    if (matchedAccount) {
                        select.value = matchedAccount.dataset.accountId;
                    }
                });
            });

            // Save matches functionality
            saveMatchesBtn.addEventListener('click', function() {
                const matches = [];
                const bankEntries = document.querySelectorAll('.bank-entry');
                
                bankEntries.forEach(entry => {
                    const select = entry.querySelector('.account-select');
                    if (select.value) {
                        matches.push({
                            bank_entry_id: entry.dataset.entryId,
                            chart_account_id: select.value
                        });
                    }
                });

                if (matches.length === 0) {
                    alert('Please select accounts for at least one bank entry.');
                    return;
                }

                fetch('{{ route("business-entities.bank-import.save-matches", $businessEntity->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ matches: matches })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Matches saved successfully! ' + data.transactionsCreated + ' transactions created.');
                        matchingInterface.classList.add('hidden');
                        // Refresh the page or update UI as needed
                        location.reload();
                    } else {
                        alert('Error saving matches: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving matches. Please try again.');
                });
            });

            // Account search functionality
            document.getElementById('account-search').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const chartAccounts = document.querySelectorAll('.chart-account');
                
                chartAccounts.forEach(account => {
                    const text = account.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        account.style.display = 'block';
                    } else {
                        account.style.display = 'none';
                    }
                });
            });

            // Documents tab preview helpers
            window.previewEntityDocument = function(url, title) {
                const frame = document.getElementById('entity-document-frame');
                const heading = document.getElementById('entity-document-title');
                const downloadBtn = document.getElementById('entity-document-download');
                const deleteBtn = document.getElementById('entity-document-delete');
                if (!frame || !heading || !downloadBtn || !deleteBtn) return window.open(url, '_blank');
                frame.src = url;
                heading.textContent = title || 'Document preview';
                downloadBtn.href = url;
                downloadBtn.setAttribute('aria-disabled', 'false');
                deleteBtn.disabled = false;
            }

            // Preview by S3 path (uses backend to generate signed URL)
            window.previewEntityDocumentByPath = function(path, title) {
                fetch(`{{ route('documents.getLink') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path })
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.url) {
                        window.previewEntityDocument(data.url, title);
                        // store last url on delete button for later
                        const deleteBtn = document.getElementById('entity-document-delete');
                        if (deleteBtn) deleteBtn.dataset.url = data.url;
                    }
                })
                .catch(err => console.error('Failed to get file link', err));
            }

            // Delete current document
            const deleteBtn = document.getElementById('entity-document-delete');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const url = this.dataset.url;
                    if (!url) return;
                    if (!confirm('Delete this document?')) return;
                    fetch(`{{ route('documents.delete') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ url })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            // clear preview
                            const frame = document.getElementById('entity-document-frame');
                            const heading = document.getElementById('entity-document-title');
                            const downloadBtn = document.getElementById('entity-document-download');
                            if (frame) frame.src = '';
                            if (heading) heading.textContent = 'Document Preview';
                            if (downloadBtn) downloadBtn.setAttribute('aria-disabled', 'true');
                            this.disabled = true;
                            this.dataset.url = '';
                            // optionally refresh page to update list
                            location.reload();
                        } else if (data && data.error) {
                            alert(data.error);
                        }
                    })
                    .catch(err => {
                        console.error('Delete failed', err);
                        alert('Failed to delete document');
                    });
                });
            }
        });
    </script>
    

</x-app-layout>