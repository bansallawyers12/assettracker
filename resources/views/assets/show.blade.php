@php
    use App\Models\Transaction;
    use App\Models\Asset as AssetModel;
    $isLeasable = in_array($asset->asset_type, AssetModel::LEASABLE_ASSET_TYPES);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $asset->name }} ({{ $asset->asset_type }})
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('business-entities.assets.edit', [$asset->business_entity_id, $asset->id]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    <x-lucide-pencil class="h-5 w-5 mr-2" />
                    Edit Asset
                </a>
                <a href="{{ route('business-entities.show', $asset->business_entity_id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg shadow-md transition-all duration-200 ease-in-out transform hover:scale-105">
                    Back to Entity
                </a>
            </div>
        </div>
    </x-slot>

    <div
        class="asset-show-page py-8 bg-linear-to-br from-gray-50 via-white to-indigo-50/40 dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 min-h-screen"
        data-entity-id="{{ $businessEntity->id }}"
        data-asset-id="{{ $asset->id }}"
    >
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
                @include('assets.partials.asset-details-sidebar', compact('asset'))

                <!-- Right Content: Tabs and Details -->
                <div class="flex-1 min-w-0">
                    <div class="asset-main-card bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-200/80 dark:border-gray-700 overflow-hidden">
                        {{-- Quick actions --}}
                        <div class="border-b border-gray-100 dark:border-gray-800 px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                            @if ($asset->asset_type === 'Car')
                                <a href="#tab_documents" class="asset-quick-action bg-violet-600 hover:bg-violet-700 text-white">
                                    <x-lucide-upload class="h-4 w-4" />
                                    Documents
                                </a>
                            @elseif ($isLeasable)
                                <a href="{{ route('assets.financials', [$businessEntity->id, $asset->id]) }}" class="asset-quick-action bg-teal-600 hover:bg-teal-700 text-white">
                                    <x-lucide-bar-chart-3 class="h-4 w-4" />
                                    Financials
                                </a>
                                <a href="{{ route('business-entities.assets.tenants.create', [$businessEntity->id, $asset->id]) }}" class="asset-quick-action bg-emerald-600 hover:bg-emerald-700 text-white">
                                    <x-lucide-user-plus class="h-4 w-4" />
                                    Add Tenant
                                </a>
                                <a href="{{ route('business-entities.assets.leases.create', [$businessEntity->id, $asset->id]) }}" class="asset-quick-action bg-blue-600 hover:bg-blue-700 text-white">
                                    <x-lucide-file-text class="h-4 w-4" />
                                    Add Lease
                                </a>
                                <a href="#tab_documents" class="asset-quick-action bg-violet-600 hover:bg-violet-700 text-white">
                                    <x-lucide-upload class="h-4 w-4" />
                                    Documents
                                </a>
                            @else
                                <a href="#tab_documents" class="asset-quick-action bg-violet-600 hover:bg-violet-700 text-white">
                                    <x-lucide-upload class="h-4 w-4" />
                                    Documents
                                </a>
                            @endif
                            </div>
                        </div>

                        {{-- Tabs --}}
                        <div class="px-5 pt-4 pb-2">
                            <nav class="asset-tab-nav flex flex-wrap" aria-label="Asset sections" id="asset-tabs">
                                <a href="#tab_details" class="tab-link">Details</a>
                                @if ($asset->asset_type === 'Car')
                                    <a href="#tab_registration" class="tab-link">Registration</a>
                                    <a href="#tab_insurance" class="tab-link">Insurance</a>
                                    <a href="#tab_service" class="tab-link">Service History</a>
                                @elseif ($isLeasable)
                                    <a href="#tab_tenants" class="tab-link">Tenants</a>
                                    <a href="#tab_leases" class="tab-link">Leases</a>
                                    <a href="#tab_financials" class="tab-link">Financials</a>
                                    <a href="#tab_invoices" class="tab-link">Invoices</a>
                                @else
                                    <a href="#tab_financials" class="tab-link">Financials</a>
                                @endif
                                <a href="#tab_transactions" class="tab-link">Transactions</a>
                                <a href="#tab_documents" class="tab-link">Documents</a>
                                <a href="#tab_compliance" class="tab-link">Compliance</a>
                                <a href="#tab_notes" class="tab-link">Notes</a>
                                <a href="#tab_reminders" class="tab-link">Reminders</a>
                                <a href="#tab_emails" class="tab-link">Emails</a>
                            </nav>
                        </div>

                        {{-- Tab Content --}}
                        <div class="tab-content-container px-5 pb-6">
                            <!-- Details Tab -->
                            <div id="tab_details" class="tab-content hidden">
                                <div class="asset-panel">
                                    @if ($asset->isPropertyType())
                                        @include('assets.partials.loan-banking-show')
                                    @endif
                                    @include('assets.partials.linked-bank-accounts-show', compact('businessEntity', 'asset'))
                                </div>
                            </div>

                            <!-- Car: Registration Tab -->
                            @if ($asset->asset_type === 'Car')
                                <div id="tab_registration" class="tab-content hidden">
                                    <div class="asset-panel">
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
                                    <div class="asset-panel">
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
                                    <div class="asset-panel">
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
                            @elseif ($isLeasable)
                                <!-- Real Estate: Tenants Tab -->
                                <div id="tab_tenants" class="tab-content hidden">
                                    <div class="asset-panel">
                                        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tenants</h3>
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('business-entities.assets.leases.sync-from-tenants', [$businessEntity->id, $asset->id]) }}" class="inline" onsubmit="return confirm('Create lease rows from tenant lease/rent data for any tenant that does not have one yet?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200" title="Copies lease start, end, duration, rent, and notes into the Leases tab">
                                                        <x-lucide-arrow-left-right class="h-4 w-4 mr-1" />
                                                        Sync to Leases tab
                                                    </button>
                                                </form>
                                                <a href="{{ route('business-entities.assets.tenants.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                                    <x-lucide-user-plus class="h-4 w-4 mr-1" />
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
                                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">{{ $tenant->name }}</h4>
                                                            <button type="button"
                                                                    data-tenant-edit
                                                                    data-tenant-id="{{ $tenant->id }}"
                                                                    class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm shadow-md transition-all duration-200">
                                                                <x-lucide-pencil class="h-4 w-4 mr-1" />
                                                                Edit
                                                            </button>
                                                        </div>
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
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Real Estate Agency</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">{{ $tenant->realEstateCompany->name ?? 'N/A' }}</dd>
                                                            </div>
                                                            <div class="col-span-2">
                                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agency Contacts</dt>
                                                                <dd class="text-gray-900 dark:text-gray-200">
                                                                    @if ($tenant->realEstateCompany && $tenant->realEstateCompany->contacts->isNotEmpty())
                                                                        @foreach ($tenant->realEstateCompany->contacts as $contact)
                                                                            <div class="mb-1">
                                                                                {{ $contact->contact_person_name }}
                                                                                @if ($contact->email)
                                                                                    - {{ $contact->email }}
                                                                                @endif
                                                                                @if ($contact->phone)
                                                                                    - {{ $contact->phone }}
                                                                                @endif
                                                                            </div>
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
                                    <div class="asset-panel">
                                        <div class="flex justify-between items-center mb-4">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Leases</h3>
                                            <a href="{{ route('business-entities.assets.leases.create', [$businessEntity->id, $asset->id]) }}" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                                <x-lucide-file-text class="h-4 w-4 mr-1" />
                                                Add Lease
                                            </a>
                                        </div>
                                        @if ($asset->leases->isEmpty())
                                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No leases yet.</p>
                                        @else
                                            <div class="space-y-4">
                                                @foreach ($asset->leases as $lease)
                                                    <div class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100">Lease with {{ $lease->tenant ? $lease->tenant->name : 'No Tenant' }}</h4>
                                                            <button type="button"
                                                                    data-lease-edit
                                                                    data-lease-id="{{ $lease->id }}"
                                                                    class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm shadow-md transition-all duration-200">
                                                                <x-lucide-pencil class="h-4 w-4 mr-1" />
                                                                Edit
                                                            </button>
                                                        </div>
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
                                    <div class="asset-panel">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Insurance</h3>
                                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
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

                                @include('assets.partials.invoices-tab', [
                                    'businessEntity' => $businessEntity,
                                    'asset' => $asset,
                                    'assetInvoices' => $assetInvoices,
                                    'invoiceSummary' => $invoiceSummary,
                                ])
                            @else
                                <!-- Generic: Financials Tab -->
                                <div id="tab_financials" class="tab-content hidden">
                                    <div class="asset-panel">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Insurance</h3>
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

                            <!-- Transactions (entity-level tx linked to this asset) -->
                            <div id="tab_transactions" class="tab-content hidden">
                                <div class="asset-panel">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Transactions</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Book transactions tagged to this asset also appear on the business entity. Leave asset blank when adding a transaction to keep it entity-wide only.</p>
                                    @if ($asset->transactions->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-6">No transactions linked to this asset yet.</p>
                                    @else
                                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                                <thead class="bg-indigo-50 dark:bg-indigo-900/40">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Date</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Amount</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Description</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase">Type</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($asset->transactions as $tx)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80">
                                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $tx->date->format('d/m/Y') }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">${{ number_format($tx->amount, 2) }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $tx->description }}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ Transaction::allTypes()[$tx->transaction_type] ?? $tx->transaction_type }}</td>
                                                            <td class="px-4 py-2 text-sm">
                                                                <div class="flex flex-wrap gap-2 items-center">
                                                                    <a href="{{ route('business-entities.transactions.edit', [$businessEntity->id, $tx->id]) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-medium">Edit</a>
                                                                    @if ($tx->receipt_path)
                                                                        <a href="{{ $tx->receiptUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-medium">Receipt</a>
                                                                    @endif
                                                                    <form action="{{ route('business-entities.transactions.destroy', [$businessEntity->id, $tx->id]) }}" method="POST" class="inline-flex items-center" onsubmit="return confirmDeleteTransaction(this, @json((bool) $tx->document_id));">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="delete_linked_document" value="0" />
                                                                        <button type="submit" class="text-red-700 hover:text-red-900 dark:text-red-400 text-xs font-medium">Delete</button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div id="tab_documents" class="tab-content hidden">
                                <div class="asset-panel">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Asset documents are separate from entity-level documents. Add categories and checklist rows here.</p>
                                    @include('business-entities.partials.documents-workspace', [
                                        'businessEntity' => $businessEntity,
                                        'asset' => $asset,
                                        'documentCategories' => $documentCategories ?? collect(),
                                    ])
                                </div>
                            </div>

                            <!-- Compliance Tab -->
                            <div id="tab_compliance" class="tab-content hidden">
                                <div class="asset-panel">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Property compliance documents by financial year (land tax, council rates, insurance, etc.).</p>
                                    @include('business-entities.partials.compliance-workspace', [
                                        'businessEntity' => $businessEntity,
                                        'asset' => $asset,
                                    ])
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div id="tab_notes" class="tab-content hidden">
                                <div class="asset-panel">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notes</h3>
                                        <button type="button" class="inline-flex items-center px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105" onclick="document.getElementById('note-form').classList.toggle('hidden')">
                                            <x-lucide-plus class="h-4 w-4 mr-1" />
                                            Add Note
                                        </button>
                                    </div>

                                    <!-- Add Note Form -->
                                    <form id="note-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs" method="POST" action="{{ route('business-entities.assets.notes.store', [$businessEntity->id, $asset->id]) }}#tab_notes">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
                                            <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
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
                                                        <div class="grow">
                                                            <p class="text-gray-700 dark:text-gray-200">{{ $note->content }}</p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                                Added by {{ $note->user->name ?? 'Unknown' }} on {{ $note->created_at ? $note->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                            </p>
                                                        </div>
                                                        <form action="{{ route('business-entities.assets.notes.destroy', [$businessEntity->id, $asset->id, $note->id]) }}" method="POST" class="ml-4">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this note?')">
                                                                <x-lucide-trash-2 class="h-5 w-5" />
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
                                <div class="asset-panel border-amber-200/60 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-yellow-700 dark:text-yellow-300">Reminders</h3>
                                        <button type="button" class="inline-flex items-center bg-yellow-100 hover:bg-yellow-200 text-yellow-700 dark:bg-yellow-900 dark:hover:bg-yellow-800 dark:text-yellow-200 px-3 py-1 rounded-md text-sm" onclick="document.getElementById('reminder-form').classList.toggle('hidden')">
                                            <x-lucide-plus class="h-4 w-4 mr-1" />
                                            Add Reminder
                                        </button>
                                    </div>
                                    <form id="reminder-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-xs" method="POST" action="{{ route('business-entities.assets.notes.store', [$businessEntity->id, $asset->id]) }}#tab_reminders">
                                        @csrf
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reminder</label>
                                            <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
                                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4">
                                            <input type="hidden" name="is_reminder" value="1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                            <x-date-input  name="reminder_date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" min="{{ now()->format('Y-m-d') }}" value="{{ old('reminder_date') }}" required />
                                            @error('reminder_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repeat</label>
                                            <select name="repeat_type" id="repeat_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white">
                                                <option value="none">One-off (No repeat)</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="quarterly">Quarterly</option>
                                                <option value="annual">Annual</option>
                                            </select>
                                            @error('repeat_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-4" id="repeat_end_date_container" style="display: none;">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date (Optional)</label>
                                            <x-date-input  name="repeat_end_date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-xs focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" min="{{ now()->format('Y-m-d') }}" value="{{ old('repeat_end_date') }}" />
                                            @error('repeat_end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow-xs transition duration-200">
                                                <x-lucide-check class="h-4 w-4 mr-1" />
                                                Save Reminder
                                            </button>
                                        </div>
                                    </form>
                                    @if (isset($asset->notes) && $asset->notes->where('is_reminder', true)->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400">No reminders yet.</p>
                                    @else
                                        <div class="space-y-3">
                                            @foreach ($asset->notes->where('is_reminder', true) as $reminder)
                                                <div class="bg-white dark:bg-gray-800 p-4 rounded-md shadow-xs border-l-4 border-yellow-400">
                                                    <p class="text-gray-700 dark:text-gray-200">{{ $reminder->content }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                        Added by {{ $reminder->user->name ?? 'Unknown' }} on {{ $reminder->created_at ? $reminder->created_at->format('d/m/Y H:i') : 'N/A' }}
                                                    </p>
                                                    <div class="mt-2 flex items-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                            <x-lucide-clock class="h-3 w-3 mr-1" />
                                                            Due: {{ $reminder->reminder_date ? $reminder->reminder_date->format('d/m/Y') : 'N/A' }}
                                                        </span>
                                                        @if($reminder->repeat_type)
                                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                                <x-lucide-refresh-cw class="h-3 w-3 mr-1" />
                                                                {{ ucfirst($reminder->repeat_type) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-3 flex space-x-2">
                                                        <form action="{{ route('notes.finalize', $reminder->id) }}#tab_reminders" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200 rounded-sm text-xs">
                                                                <x-lucide-check class="h-3 w-3 mr-1" />
                                                                Finalize
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('notes.extend', $reminder->id) }}#tab_reminders" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 rounded-sm text-xs">
                                                                <x-lucide-plus class="h-3 w-3 mr-1" />
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
                                <div class="asset-panel">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Allocated Emails</h3>
                                    @php($allocatedEmails = $asset->mailMessages()->latest('sent_date')->with('labels')->paginate(10))
                                    @if ($allocatedEmails->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No emails allocated yet.</p>
                                    @else
                                        @php($firstEmail = $allocatedEmails->first())
                                        <div class="flex gap-6">
                                            <div class="w-full lg:w-5/12">
                                                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xs border border-blue-200 dark:border-blue-700 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($allocatedEmails as $email)
                                                        <a href="{{ route('emails.show', $email->id) }}" target="assetEmailViewer" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                            <div class="text-blue-900 dark:text-blue-200 font-semibold">{{ $email->subject ?: '(No subject)' }}</div>
                                                            <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $email->sender_name ?: $email->sender_email }} — {{ optional($email->sent_date)->format('Y-m-d H:i') }}</div>
                                                            <div class="mt-1 flex gap-2 flex-wrap">
                                                                @foreach ($email->labels as $label)
                                                                    <span class="text-xs px-2 py-1 rounded-sm" style="background-color: {{ $label->color ?? '#e5e7eb' }}; color:#111827">{{ $label->name }}</span>
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
            const tabsRoot = document.getElementById('asset-tabs');
            const tabLinks = tabsRoot ? tabsRoot.querySelectorAll('a.tab-link') : [];
            const tabContents = document.querySelectorAll('.tab-content-container > .tab-content');
            const hashTabLinks = document.querySelectorAll('.asset-show-page a[href^="#tab_"]');

            function escapeHashId(id) {
                return (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(id) : id.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            }

            function defaultTabId() {
                return tabLinks.length > 0 ? tabLinks[0].getAttribute('href').substring(1) : 'tab_details';
            }

            function switchTab(targetId, options) {
                const opts = options || {};
                const candidate = document.getElementById(targetId);
                let resolvedId = targetId;

                if (!candidate || !candidate.classList.contains('tab-content')) {
                    resolvedId = defaultTabId();
                    if (opts.fixInvalidHash && window.location.hash !== '#' + resolvedId) {
                        history.replaceState(null, '', '#' + resolvedId);
                    }
                }

                tabContents.forEach(content => content.classList.add('hidden'));

                const selectedTab = document.getElementById(resolvedId);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                }

                tabLinks.forEach(tab => tab.classList.remove('active'));

                if (tabsRoot) {
                    const activeLink = tabsRoot.querySelector('a.tab-link[href="#' + escapeHashId(resolvedId) + '"]');
                    if (activeLink) {
                        activeLink.classList.add('active');
                    }
                }

                if (resolvedId === 'tab_compliance') {
                    window.dispatchEvent(new CustomEvent('compliance-tab-activated'));
                }
            }

            function openTabFromLink(link, pushHistory) {
                const href = link.getAttribute('href');
                if (!href || !href.startsWith('#tab_')) {
                    return;
                }

                const targetId = href.substring(1);
                switchTab(targetId);
                if (pushHistory && window.location.hash !== href) {
                    history.pushState(null, '', href);
                }
            }

            tabLinks.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    openTabFromLink(this, true);
                });
            });

            hashTabLinks.forEach(link => {
                if (link.classList.contains('tab-link')) {
                    return;
                }

                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    openTabFromLink(this, true);
                });
            });

            window.addEventListener('popstate', function() {
                const targetId = window.location.hash ? window.location.hash.substring(1) : defaultTabId();
                switchTab(targetId);
            });

            window.addEventListener('hashchange', function() {
                const targetId = window.location.hash ? window.location.hash.substring(1) : defaultTabId();
                switchTab(targetId);
            });

            const initialTab = window.location.hash ? window.location.hash.substring(1) : defaultTabId();
            switchTab(initialTab, { fixInvalidHash: true });

            function initializeReminderLogic() {
                const repeatTypeSelect = document.getElementById('repeat_type');
                const repeatEndDateContainer = document.getElementById('repeat_end_date_container');

                if (repeatTypeSelect && repeatEndDateContainer) {
                    repeatEndDateContainer.style.display = repeatTypeSelect.value !== 'none' ? 'block' : 'none';

                    repeatTypeSelect.addEventListener('change', function() {
                        repeatEndDateContainer.style.display = this.value !== 'none' ? 'block' : 'none';
                    });
                }
            }

            initializeReminderLogic();
        });

        function confirmDeleteTransaction(form, hasLinkedReceiptDoc) {
            if (!confirm('Delete this transaction?')) {
                return false;
            }
            const input = form.querySelector('input[name="delete_linked_document"]');
            if (hasLinkedReceiptDoc && confirm('Also delete the attached receipt from Documents?')) {
                input.value = '1';
            } else {
                input.value = '0';
            }
            return true;
        }
    </script>
    @endpush
</x-app-layout>