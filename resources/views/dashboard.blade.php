<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Top Action Buttons -->
            <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
                <a href="{{ route('business-entities.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                    Create Business Entity
                </a>
                <button id="add-transaction-btn" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg flex items-center gap-2">
                    Add Transaction
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <a href="{{ route('emails.index') }}" 
                   class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                    Emails
                </a>
            </div>

            <!-- Add Transaction Section -->
            <div id="add-transaction-section" class="{{ session('keep_open') ? '' : 'hidden' }} bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700 mb-6 transition-all duration-300">
                <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-4">Add Transaction</h3>
                @if (session('success'))
                    <div class="mb-4 p-3 bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Transaction Creation Form -->
                <form method="POST" action="{{ route('business-entities.transactions.store', ['businessEntity' => $businessEntities->first() ? $businessEntities->first()->id : 0]) }}" id="store-transaction-form" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Business Entity</label>
                            <select name="business_entity_id" id="business_entity_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" required>
                                <option value="">Select Entity</option>
                                @foreach ($businessEntities as $entity)
                                    <option value="{{ $entity->id }}" {{ old('business_entity_id', session('transactionData.business_entity_id')) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input type="date" name="date" value="{{ old('date', session('transactionData.date', now()->toDateString())) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                            <input type="number" name="amount" step="0.01" value="{{ old('amount', session('transactionData.amount')) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" name="description" value="{{ old('description', session('transactionData.description')) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Transaction::$transactionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transaction_type', session('transactionData.transaction_type')) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4" id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Entity</label>
                            <select name="related_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Related Entity</option>
                                @foreach(\App\Models\BusinessEntity::where('id', '!=', $businessEntities->first()?->id)->get() as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', session('transactionData.related_entity_id')) == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                                @endforeach
                            </select>
                            @error('related_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST Amount</label>
                            <input type="number" name="gst_amount" step="0.01" value="{{ old('gst_amount', session('transactionData.gst_amount')) }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('gst_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST Status</label>
                            <select name="gst_status" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="included" {{ old('gst_status', session('transactionData.gst_status')) == 'included' ? 'selected' : '' }}>Included</option>
                                <option value="excluded" {{ old('gst_status', session('transactionData.gst_status')) == 'excluded' ? 'selected' : '' }}>Excluded</option>
                            </select>
                            @error('gst_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Receipt (Optional)</label>
                            <input type="file" name="document" 
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-blue-300" 
                                   accept="image/*,application/pdf">
                            @error('document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Name (Optional)</label>
                            <input type="text" name="document_name" value="{{ old('document_name') }}" 
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white" 
                                   placeholder="e.g., Invoice123">
                            @error('document_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <input type="hidden" name="receipt_path" value="{{ old('receipt_path', session('transactionData.receipt_path')) }}">
                    <div class="flex gap-4">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Add Transaction</button>
                        <button type="button" id="cancel-transaction-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 font-semibold py-2 px-4 rounded-md shadow-sm transition duration-200">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Quick Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $businessEntities->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Entities</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $assets->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Assets</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $persons->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Persons</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $allReminders->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Reminders</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $assetDueDates->count() + $entityDueDates->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Due Soon</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Transactions</div>
                </div>
            </div>

            <!-- Reminders Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-yellow-700 dark:text-yellow-300">Reminders (Due in Next 15 Days)</h3>
                    <button type="button" class="inline-flex items-center bg-yellow-100 hover:bg-yellow-200 text-yellow-700 dark:bg-yellow-900 dark:hover:bg-yellow-800 dark:text-yellow-200 px-3 py-1 rounded-md text-sm" onclick="document.getElementById('reminder-form').classList.toggle('hidden')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Reminder
                    </button>
                </div>
                <form id="reminder-form" class="hidden mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow" method="POST" action="{{ route('reminders.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Business Entity</label>
                        <select name="business_entity_id" id="reminder_business_entity_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Entity (Optional)</option>
                            @foreach ($businessEntities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                            @endforeach
                        </select>
                        @error('business_entity_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset</label>
                        <select name="asset_id" id="reminder_asset_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" disabled>
                            <option value="">Select Asset (Optional)</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" data-entity-id="{{ $asset->business_entity_id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                            @endforeach
                        </select>
                        @error('asset_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reminder</label>
                        <textarea name="content" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" rows="3" required>{{ old('content') }}</textarea>
                        @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                        <input type="date" name="next_due_date" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:text-white" min="{{ now()->format('Y-m-d') }}" value="{{ old('next_due_date') }}" required>
                        @error('next_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                @if ($allReminders->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400">No reminders due in the next 15 days.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($allReminders as $reminder)
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-md shadow-sm border-l-4 border-yellow-400">
                                <p class="text-gray-700 dark:text-gray-200">{{ $reminder->content }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    For: {{ $reminder->asset ? $reminder->asset->name : ($reminder->businessEntity->legal_name ?? 'Unknown') }} - 
                                    By: {{ $reminder->user->name ?? 'Unknown' }}
                                </p>
                                <div class="mt-2 flex items-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Due: {{ $reminder->next_due_date ? $reminder->next_due_date->format('d/m/Y') : 'N/A' }}
                                    </span>
                                    @if($reminder->repeat_type && $reminder->repeat_type !== 'none')
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ ucfirst($reminder->repeat_type) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-3 flex space-x-2">
                                    <form action="{{ $reminder->is_note ? route('notes.finalize', $reminder->id) : route('reminders.complete', $reminder->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900 dark:hover:bg-green-800 dark:text-green-200 rounded text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Finalize
                                        </button>
                                    </form>
                                    <form action="{{ $reminder->is_note ? route('notes.extend', $reminder->id) : route('reminders.extend', $reminder->id) }}" method="POST" class="inline">
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

            <!-- Due Dates Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-4">Upcoming Due Dates (Next 15 Days)</h3>
                @if ($assetDueDates->isNotEmpty() || $entityDueDates->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($assetDueDates as $asset)
                            @if ($asset->registration_due_date)
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <p class="text-gray-900 dark:text-gray-100">Registration Due for {{ $asset->name }} ({{ $asset->asset_type }})</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">For: {{ $asset->businessEntity->legal_name ?? 'Unknown Entity' }} - Due: {{ $asset->registration_due_date->format('d/m/Y') }}</p>
                                    @if ($asset->business_entity_id && $asset->businessEntity)
                                        <div class="mt-2 flex gap-4">
                                            <form action="{{ route('assets.finalize-due-date', [$asset->business_entity_id, $asset->id, 'registration']) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline text-sm">Finalize</button>
                                            </form>
                                            <form action="{{ route('assets.extend-due-date', [$asset->business_entity_id, $asset->id, 'registration']) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline text-sm">Extend (3 days)</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">No valid business entity</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        @foreach ($entityDueDates as $entityDueDate)
                            @if ($entityDueDate->asic_due_date)
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <p class="text-gray-900 dark:text-gray-100">ASIC Due for {{ $entityDueDate->businessEntity->legal_name }} (Role: {{ $entityDueDate->role ?? 'Unknown Role' }})</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Due: {{ $entityDueDate->asic_due_date->format('d/m/Y') }}</p>
                                    <div class="mt-2 flex gap-4">
                                        <form action="{{ route('entity-persons.finalize-due-date', $entityDueDate->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline text-sm">Finalize</button>
                                        </form>
                                        <form action="{{ route('entity-persons.extend-due-date', $entityDueDate->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline text-sm">Extend (3 days)</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">No upcoming due dates.</p>
                @endif
            </div>

            <!-- Recent Items -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Recent Business Entities -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300">Business Entities</h3>
                        <a href="{{ route('business-entities.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">View All</a>
                    </div>
                    @if ($businessEntities->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400 text-sm">No business entities yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($businessEntities->take(3) as $entity)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <a href="{{ route('business-entities.show', $entity->id) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $entity->legal_name }}
                                        </a>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entity->entity_type ?? 'N/A' }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $entity->user->name ?? 'Unknown' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Recent Assets -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-green-700 dark:text-green-300">Recent Assets</h3>
                        <a href="{{ route('business-entities.assets.index', $businessEntities->first()?->id ?? 0) }}" class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">View All</a>
                    </div>
                    @if ($assets->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400 text-sm">No assets yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($assets->take(3) as $asset)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <a href="{{ route('business-entities.assets.show', [$asset->business_entity_id, $asset->id]) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-green-600 dark:hover:text-green-400">
                                            {{ $asset->name }}
                                        </a>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->asset_type }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $asset->businessEntity->legal_name ?? 'Unknown' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Recent Persons -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-indigo-200 dark:border-indigo-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-indigo-700 dark:text-indigo-300">Recent Persons</h3>
                        <a href="{{ route('persons.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">View All</a>
                    </div>
                    @if ($uniquePersons->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400 text-sm">No persons yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($uniquePersons->take(3) as $personData)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <a href="{{ route('persons.show', $personData['person']->id) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $personData['person']->first_name }} {{ $personData['person']->last_name }}
                                        </a>
                                        <div class="flex gap-1 mt-1">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $personData['totalRoles'] }} role{{ $personData['totalRoles'] != 1 ? 's' : '' }}
                                            </span>
                                            @if($personData['activeRoles'] > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    {{ $personData['activeRoles'] }} active
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions & Accounting -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('business-entities.create') }}" class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Add Entity</span>
                        </a>
                        <a href="{{ route('business-entities.assets.create', $businessEntities->first()?->id ?? 0) }}" class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">Add Asset</span>
                        </a>
                        <a href="{{ route('persons.create') }}" class="flex items-center p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Add Person</span>
                        </a>
                        <a href="{{ route('emails.index') }}" class="flex items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Emails</span>
                        </a>
                    </div>
                </div>

                <!-- Accounting Tools -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-green-200 dark:border-green-700">
                    <h3 class="text-lg font-semibold text-green-700 dark:text-green-300 mb-4">Accounting & Finance</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('chart-of-accounts.index') }}" class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">Chart of Accounts</span>
                        </a>
                        <a href="{{ route('bank-accounts.index') }}" class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Bank Accounts</span>
                        </a>
                        <a href="{{ route('transactions.index') }}" class="flex items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Transactions</span>
                        </a>
                        <a href="{{ route('invoices.index') }}" class="flex items-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Invoices</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-700 text-center">
                <p class="text-xl font-medium text-blue-900 dark:text-blue-200">Welcome, {{ Auth::user()->name }}!</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage your business entities, assets, and transactions with ease.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Transaction form toggle
            const transactionBtn = document.getElementById('add-transaction-btn');
            const transactionSection = document.getElementById('add-transaction-section');
            const cancelTransactionBtn = document.getElementById('cancel-transaction-btn');
            const entitySelect = document.getElementById('business_entity_id');

            transactionBtn.addEventListener('click', () => {
                transactionSection.classList.toggle('hidden');
            });

            cancelTransactionBtn.addEventListener('click', () => {
                if (!{{ session()->has('success') ? 'true' : 'false' }}) {
                    transactionSection.classList.add('hidden');
                }
            });

            entitySelect.addEventListener('change', (e) => {
                const entityId = e.target.value;
                if (entityId) {
                    document.getElementById('store-transaction-form').action = `{{ url('business-entities') }}/${entityId}/transactions/store`;
                }
            });

            @if (session('error') || session('transactionData'))
                transactionSection.classList.remove('hidden');
            @endif

            // Reminder form logic
            function initializeReminderLogic() {
                const repeatTypeSelect = document.getElementById('repeat_type');
                const repeatEndDateContainer = document.getElementById('repeat_end_date_container');
                const entitySelect = document.getElementById('reminder_business_entity_id');
                const assetSelect = document.getElementById('reminder_asset_id');

                if (repeatTypeSelect && repeatEndDateContainer) {
                    if (repeatTypeSelect.value !== 'none') {
                        repeatEndDateContainer.style.display = 'block';
                    } else {
                        repeatEndDateContainer.style.display = 'none';
                    }

                    repeatTypeSelect.addEventListener('change', function() {
                        repeatEndDateContainer.style.display = this.value !== 'none' ? 'block' : 'none';
                    });
                }

                if (entitySelect && assetSelect) {
                    entitySelect.addEventListener('change', function() {
                        assetSelect.disabled = !this.value;
                        Array.from(assetSelect.options).forEach(option => {
                            if (option.value && option.dataset.entityId) {
                                option.style.display = option.dataset.entityId === entitySelect.value || !entitySelect.value ? 'block' : 'none';
                            }
                        });
                    });
                }
            }

            initializeReminderLogic();

            // Related Entity field visibility
            const transactionTypeSelect = document.getElementById('transaction_type');
            const relatedEntityField = document.getElementById('related_entity_field');
            
            if (transactionTypeSelect && relatedEntityField) {
                const relatedPartyTypes = [
                    'rent_to_related_party',
                    'purchases_from_related_party', 
                    'sales_to_related_party',
                    'directors_loans_to_company',
                    'company_loans_to_directors',
                    'repayment_directors_loans'
                ];
                
                transactionTypeSelect.addEventListener('change', function() {
                    if (relatedPartyTypes.includes(this.value)) {
                        relatedEntityField.style.display = 'block';
                        relatedEntityField.querySelector('select').required = true;
                    } else {
                        relatedEntityField.style.display = 'none';
                        relatedEntityField.querySelector('select').required = false;
                        relatedEntityField.querySelector('select').value = '';
                    }
                });
                
                // Check on page load
                if (relatedPartyTypes.includes(transactionTypeSelect.value)) {
                    relatedEntityField.style.display = 'block';
                    relatedEntityField.querySelector('select').required = true;
                }
            }
        });
    </script>
</x-app-layout>