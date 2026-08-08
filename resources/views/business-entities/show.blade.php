@php
    use App\Models\BankAccount;
    use App\Models\BusinessEntity;
    use App\Models\Transaction;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white" data-entity-page-title>
                    {{ $businessEntity->legal_name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" data-entity-page-type>{{ $businessEntity->entity_type }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-entity-profile-edit class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition-colors">
                    <x-lucide-pencil class="h-4 w-4 mr-1.5" />
                    Edit company profile
                </button>
                @unless ($businessEntity->isClosed())
                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'close-business-entity')"
                        class="inline-flex items-center px-3 py-1.5 border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-gray-900 dark:hover:bg-rose-950/30 dark:text-rose-300 rounded-md text-sm font-medium transition-colors"
                    >
                        <x-lucide-archive class="h-4 w-4 mr-1.5" />
                        Close entity
                    </button>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="entity-show-page py-6 bg-gray-50 dark:bg-gray-800 min-h-screen" data-profile-form-url="{{ route('entities.profile.form', $businessEntity) }}">
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
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($businessEntity->isTenancyContactOnly())
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 px-4 py-3 text-sm text-amber-900 dark:text-amber-100" role="status">
                    <p class="font-medium">{{ __('Tenancy / property manager contact') }}</p>
                    <p class="mt-1 text-amber-800 dark:text-amber-200">{{ __('This company is treated as a contact for managing rentals, not as one of your operating entities. It is hidden from the main entity list, reports, and accounting pickers. Prefer adding agencies when you add a tenant on an asset.') }}</p>
                </div>
            @endif
            <div class="flex flex-col lg:flex-row gap-6">
                <div data-entity-sidebar>
                    @include('business-entities.partials.entity-details-sidebar', compact('businessEntity'))
                </div>

                <!-- Right Content: Tabs and Details -->
                <div class="flex-1 min-w-0">
                    <div class="entity-main-card bg-white dark:bg-gray-900 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
                        <!-- Combined Actions and Navigation -->
                        <div class="entity-toolbar mb-5">
                            <div class="entity-toolbar-top overflow-x-auto -mx-1 px-1 pb-0.5">
                                <div class="entity-tab-nav entity-tab-nav--combined flex flex-nowrap sm:flex-wrap items-center gap-1 sm:gap-1.5 min-w-min sm:min-w-0" id="entity-tabs">
                                    <nav class="flex flex-nowrap sm:flex-wrap items-center gap-1 sm:gap-1.5 shrink-0" aria-label="Entity sections">
                                        <a href="#tab_assets" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Assets</a>
                                        <a href="#tab_persons" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Persons</a>
                                        <a href="#tab_documents" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Documents</a>
                                        <a href="#tab_compliance" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Compliance</a>
                                        <a href="#tab_notes" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Notes</a>
                                        <a href="#tab_contact_lists" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Contact Lists</a>
                                        <a href="#tab_compose_email" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Compose Email</a>
                                        <a href="#tab_emails" class="tab-link entity-tab-link px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">Emails</a>
                                    </nav>
                                    <span class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-600 shrink-0 self-center" aria-hidden="true"></span>
                                    <nav class="flex flex-nowrap sm:flex-wrap items-center gap-1 sm:gap-1.5 shrink-0" aria-label="Accounting and finance">
                                        <a href="{{ route('business-entities.tracking-categories.index', $businessEntity) }}" class="entity-tab-link entity-external-nav inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-archive class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" aria-hidden="true" />
                                            Tracking Categories
                                        </a>
                                        <a href="#tab_bank_accounts" class="tab-link entity-tab-link inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-credit-card class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 shrink-0" aria-hidden="true" />
                                            Bank Accounts
                                        </a>
                                        <a href="#tab_bank_import" class="tab-link entity-tab-link inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-cloud-upload class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" aria-hidden="true" />
                                            Bank Import
                                        </a>
                                        <a href="#tab_transactions" class="tab-link entity-tab-link inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-clipboard class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400 shrink-0" aria-hidden="true" />
                                            Transactions
                                        </a>
                                        <a href="{{ route('commitments.index', ['entity' => $businessEntity->id]) }}" class="entity-tab-link entity-external-nav inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-file-text class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0" aria-hidden="true" />
                                            Commitments
                                        </a>
                                        <a href="#tab_invoices" class="tab-link entity-tab-link inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white rounded-md hover:bg-gray-100/80 dark:hover:bg-gray-800/80 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-gray-900">
                                            <x-lucide-file-text class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400 shrink-0" aria-hidden="true" />
                                            Invoices
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="tab-content-container">
                            <!-- Assets Tab -->
                            <div id="tab_assets" class="tab-content hidden">
                                @include('business-entities.partials.assets-workspace', [
                                    'businessEntity' => $businessEntity,
                                    'assets' => $assets ?? collect(),
                                ])
                            </div>

                            <!-- Persons Tab -->
                            <div id="tab_persons" class="tab-content hidden">
                                @include('business-entities.partials.persons-workspace', [
                                    'businessEntity' => $businessEntity,
                                    'persons' => $persons ?? collect(),
                                ])
                            </div>


                            <!-- Transactions Tab -->
                            <div id="tab_transactions" class="tab-content hidden">
                                @include('business-entities.partials.transactions-summary', [
                                    'businessEntity' => $businessEntity,
                                    'transactions' => $transactions,
                                    'bankAccounts' => $bankAccounts ?? collect(),
                                ])
                            </div>

                            <!-- Invoices Tab -->
                            <div id="tab_invoices" class="tab-content hidden">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Invoices</h3>
                                        <a href="{{ route('business-entities.invoices.create', $businessEntity->id) }}" class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm shadow-md transition-all duration-200 transform hover:scale-105">
                                            <x-lucide-plus class="h-4 w-4 mr-1" />
                                            Create Invoice
                                        </a>
                                    </div>
                                    @if ($invoices->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No invoices yet.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
                                                <thead class="bg-indigo-50 dark:bg-indigo-900/50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Number</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Asset</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Customer</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Issue</th>
                                                        <th class="px-6 py-3 text-right text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Total</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Status</th>
                                                        <th class="px-6 py-3 text-right text-xs font-medium text-indigo-800 dark:text-indigo-200 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($invoices as $invoice)
                                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                            <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $invoice->invoice_number }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                                @if ($invoice->asset_id)
                                                                    <a href="{{ route('business-entities.assets.show', [$businessEntity->id, $invoice->asset_id]) }}#tab_invoices" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ $invoice->asset?->name ?? 'Property #'.$invoice->asset_id }}</a>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $invoice->customer_name }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100 text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ ucfirst($invoice->status) }}</td>
                                                            <td class="px-6 py-4 text-sm text-right">
                                                                <a href="{{ route('business-entities.invoices.show', [$businessEntity->id, $invoice]) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">View</a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Financial Reports Tab -->
                            <div id="tab_financial_reports" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    @if ($businessEntity->isTenancyContactOnly())
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Financial reports are not available for tenancy or property-manager contacts.') }}</p>
                                    @else
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Financial Reports</h3>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                                        {{-- Account Transactions --}}
                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-clipboard class="h-8 w-8 text-blue-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Account Transactions</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Line-level movements by account</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.account-transactions', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-bar-chart-3 class="h-8 w-8 text-green-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Profit & Loss</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">View income and expenses</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.profit-loss', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-calculator class="h-8 w-8 text-blue-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Balance Sheet</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Assets, liabilities & equity</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.balance-sheet', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 dark:text-blue-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-trending-up class="h-8 w-8 text-purple-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cash Flow</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Cash inflows and outflows</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.cash-flow', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900 dark:hover:bg-purple-800 dark:text-purple-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-archive class="h-8 w-8 text-orange-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tracking Categories</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Owner & Property reports</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('business-entities.financial-reports.tracking-categories', $businessEntity->id) }}" class="inline-flex items-center px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 dark:bg-orange-900 dark:hover:bg-orange-800 dark:text-orange-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-truck class="h-8 w-8 text-sky-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Car Register</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Rego, insurance &amp; service due dates</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <a href="{{ route('financial-reports.car-register', ['scope' => 'selected', 'entity_ids' => [$businessEntity->id]]) }}" class="inline-flex items-center px-3 py-2 bg-sky-100 hover:bg-sky-200 text-sky-700 dark:bg-sky-900 dark:hover:bg-sky-800 dark:text-sky-200 rounded-lg text-sm transition-all duration-200">
                                                    View Report
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>

                                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-200">
                                            <div class="flex items-center">
                                                <div class="shrink-0">
                                                    <x-lucide-shield-check class="h-8 w-8 text-violet-500" />
                                                </div>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Compliance</h4>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">ITR, BAS and FY compliance documents</p>
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                @php $fyStart = \App\Support\FinancialYear::currentStart()->toDateString(); @endphp
                                                <a href="{{ route('business-entities.show', $businessEntity->id) }}?fy_start={{ $fyStart }}#tab_compliance" class="inline-flex items-center px-3 py-2 bg-violet-100 hover:bg-violet-200 text-violet-700 dark:bg-violet-900 dark:hover:bg-violet-800 dark:text-violet-200 rounded-lg text-sm transition-all duration-200">
                                                    Open compliance tab
                                                    <x-lucide-chevron-right class="h-4 w-4 ml-1" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div id="tab_documents" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Documents</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Organise files by category and checklist. Upload per row or use bulk upload.</p>
                                    </div>
                                    @include('business-entities.partials.documents-workspace', [
                                        'businessEntity' => $businessEntity,
                                        'asset' => null,
                                        'documentCategories' => $documentCategories ?? collect(),
                                    ])
                                </div>
                            </div>

                            <!-- Compliance Tab -->
                            <div id="tab_compliance" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Compliance</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Financial-year records such as ITR, BAS, land tax, and council rates.</p>
                                    </div>
                                    @include('business-entities.partials.compliance-workspace', [
                                        'businessEntity' => $businessEntity,
                                        'asset' => null,
                                    ])
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div id="tab_notes" class="tab-content hidden">
                                @include('business-entities.partials.notes-workspace', [
                                    'businessEntity' => $businessEntity,
                                    'notes' => $notes ?? collect(),
                                ])
                            </div>

                            <!-- Contact Lists Tab -->
                            <div id="tab_contact_lists" class="tab-content hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    @include('business-entities.partials.contact-lists-workspace', [
                                        'businessEntity' => $businessEntity,
                                        'contactLists' => $contactLists ?? collect(),
                                    ])
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
                                                @if ($businessEntity->registeredEmailIsPlaceholder())
                                                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">No company email on file. Enter a recipient below or <a href="{{ route('business-entities.edit', $businessEntity->id) }}#registered_email" class="font-medium underline hover:no-underline">add one in the company profile</a>.</p>
                                                    <input type="email" id="to_email" name="to_email" class="mt-2 block w-full rounded-md border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" value="{{ old('to_email') }}" autocomplete="email" placeholder="recipient@example.com" required>
                                                @else
                                                    <input type="email" id="to_email" name="to_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" value="{{ $businessEntity->registered_email }}" readonly required>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label for="cc_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CC</label>
                                                <input type="email" id="cc_email" name="cc_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject *</label>
                                            <input type="text" id="subject" name="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" required>
                                        </div>

                                        <div class="mb-4">
                                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message *</label>
                                            <x-rich-text-editor id="message" name="message" :rows="8" :height="200" defer required />
                                        </div>

                                        <div class="mb-4">
                                            <label for="attachment" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attachment</label>
                                            <input type="file" id="attachment" name="attachments[]" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 focus:outline-hidden focus:ring-2 focus:ring-indigo-500" multiple>
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
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Allocated Emails</h3>
                                        <a href="{{ route('email-templates.index') }}" class="inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors shrink-0">
                                            <x-lucide-layout-grid class="w-4 h-4 shrink-0" aria-hidden="true" />
                                            {{ __('Email templates') }}
                                        </a>
                                    </div>
                                    @php($allocatedEmails = $businessEntity->mailMessages()->latest('sent_date')->with('labels')->paginate(10))
                                    @if ($allocatedEmails->isEmpty())
                                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No emails allocated yet.</p>
                                    @else
                                        @php($firstEmail = $allocatedEmails->first())
                                        <div class="flex gap-6">
                                            <div class="w-full lg:w-5/12">
                                                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xs border border-blue-200 dark:border-blue-700 divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach ($allocatedEmails as $email)
                                                        <a href="{{ route('emails.show', $email->id) }}" target="beEmailViewer" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                                <iframe name="beEmailViewer" class="w-full h-[70vh] bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-700" src="{{ $firstEmail ? route('emails.show', $firstEmail->id) : '' }}"></iframe>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Bank Accounts Tab -->
                            <div id="tab_bank_accounts" class="tab-content hidden">
                                <div
                                    class="bank-accounts-workspace space-y-3"
                                    data-entity-id="{{ $businessEntity->id }}"
                                    data-list-url="{{ route('entities.bank-accounts.workspace', $businessEntity) }}"
                                >
                                    <div class="flex flex-wrap justify-between items-center gap-2">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Bank Accounts</h3>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('bank-accounts.index') }}" class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                                                Portfolio registry
                                            </a>
                                            <button
                                                type="button"
                                                data-open-add-bank-account
                                                class="entity-btn-primary"
                                            >
                                                <x-lucide-plus class="h-4 w-4 mr-1" aria-hidden="true" />
                                                Add Account
                                            </button>
                                        </div>
                                    </div>

                                    <div data-bank-accounts-list>
                                        @include('business-entities.partials.bank-accounts.list', [
                                            'businessEntity' => $businessEntity,
                                            'holderGroups' => $entityBankAccountGroups ?? [],
                                        ])
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Import Tab -->
                            <div id="tab_bank_import" class="tab-content hidden">
                                @include('business-entities.partials.bank-import-summary', [
                                    'businessEntity' => $businessEntity,
                                    'bankAccounts' => $bankAccounts ?? collect(),
                                ])
                            </div>

                        </div> {{-- End of tab-content-container --}}
                    </div> {{-- End of right content card --}}
                </div> {{-- End of right content column --}}
            </div> {{-- End of main flex container --}}
        </div> {{-- End of max-w-7xl container --}}
    </div> {{-- End of py-8 background div --}}

    @include('bank-accounts.partials.add-account-modal', [
        'businessEntity' => $businessEntity,
    ])

    @unless ($businessEntity->isClosed())
        @include('business-entities.partials.close-entity-modal', compact('businessEntity'))
    @endunless

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabsRoot = document.getElementById('entity-tabs');
            const tabs = tabsRoot ? tabsRoot.querySelectorAll('a.tab-link') : [];
            const tabContents = document.querySelectorAll('.tab-content-container > .tab-content');
            const composeEmailForm = document.getElementById('compose-email-form');
            const fromEmailSelect = document.getElementById('from_email');
            const subjectInput = document.getElementById('subject');

            function escapeHashId(id) {
                return (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(id) : id.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            }

            // Function to switch tabs
            function switchTab(targetId, options) {
                const opts = options || {};
                const candidate = document.getElementById(targetId);
                let resolvedId = targetId;
                if (!candidate || !candidate.classList.contains('tab-content')) {
                    resolvedId = 'tab_assets';
                    if (opts.fixInvalidHash && window.location.hash !== '#' + resolvedId) {
                        history.replaceState(null, '', '#' + resolvedId);
                    }
                }

                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });

                const selectedTab = document.getElementById(resolvedId);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                }

                if (resolvedId === 'tab_compose_email') {
                    window.initRichTextEditors?.(selectedTab, { includeDeferred: true });
                }

                tabs.forEach(tab => {
                    tab.classList.remove('active');
                });

                if (tabsRoot) {
                    const activeTab = tabsRoot.querySelector('a.tab-link[href="#' + escapeHashId(resolvedId) + '"]');
                    if (activeTab) {
                        activeTab.classList.add('active');
                    }
                }

                if (resolvedId === 'tab_compliance') {
                    window.dispatchEvent(new CustomEvent('compliance-tab-activated'));
                }
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    switchTab(targetId);
                    history.pushState(null, '', '#' + targetId);
                });
            });

            document.querySelectorAll('a.js-entity-tab-jump[href^="#"]').forEach((link) => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    switchTab(targetId);
                    history.pushState(null, '', '#' + targetId);
                });
            });

            window.addEventListener('popstate', function () {
                const targetId = window.location.hash.substring(1) || 'tab_assets';
                switchTab(targetId);
            });

            window.addEventListener('hashchange', function () {
                const targetId = window.location.hash.substring(1) || 'tab_assets';
                switchTab(targetId);
            });

            const initialTab = window.location.hash ? window.location.hash.substring(1) : 'tab_assets';
            switchTab(initialTab, { fixInvalidHash: true });

            const urlParams = new URLSearchParams(window.location.search);
            // Legacy bank-import deep link: ?bank_account_id=#tab_bank_import
            const legacyImportAccountId = urlParams.get('bank_account_id');
            if (legacyImportAccountId && window.openBankAccountTransactionsPanel) {
                const transactionsUrl = @json(url('/bank-accounts')) + '/' + encodeURIComponent(legacyImportAccountId)
                    + '/transactions?business_entity_id=' + encodeURIComponent(@json($businessEntity->id));
                window.openBankAccountTransactionsPanel(transactionsUrl, {
                    title: 'Import & match',
                    subtitle: 'Reconcile statement lines on this account.',
                });
                urlParams.delete('bank_account_id');
                const cleanedSearch = urlParams.toString();
                const cleanedUrl = window.location.pathname
                    + (cleanedSearch ? '?' + cleanedSearch : '')
                    + (window.location.hash || '#tab_bank_import');
                history.replaceState(null, '', cleanedUrl);
            }

            // Fetch data for Compose Email tab
            if (document.getElementById('tab_compose_email') && fromEmailSelect) {
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

                    })
                    .catch(error => console.error('Error fetching email compose data:', error));
            }

            if (composeEmailForm) {
                composeEmailForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (window.isRichTextEmpty?.('message')) {
                        alert('Please enter a message.');
                        return;
                    }

                    const formData = new FormData(this);
                    formData.set('message', window.getRichTextContent?.('message') ?? (document.getElementById('message')?.value ?? ''));

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
                        window.setRichTextContent?.('message', '');
                    })
                    .catch(error => {
                        console.error('Error sending email:', error);
                        alert('Error sending email.');
                    });
                });
            }

        });
    </script>
    <script>
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

</x-app-layout>
