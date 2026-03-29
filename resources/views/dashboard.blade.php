<x-app-layout>
    <div class="py-6 lg:py-8 bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero / Greeting --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-6 lg:p-8 text-white shadow-xl">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-1/2 -mb-8 w-60 h-60 bg-white/5 rounded-full blur-3xl"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold">Welcome back, {{ Auth::user()->name }}</h1>
                        <p class="mt-1 text-blue-100 text-sm lg:text-base">{{ now()->format('l, F j, Y') }} &mdash; Here's your overview.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('business-entities.create') }}"
                           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            New Entity
                        </a>
                        <button id="add-transaction-btn"
                                class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Add Transaction
                        </button>
                        <a href="{{ route('emails.index') }}"
                           class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all duration-200 hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Emails
                        </a>
                    </div>
                </div>
            </div>

            {{-- Add Transaction Section (Collapsible) --}}
            <div id="add-transaction-section" class="{{ session('keep_open') ? '' : 'hidden' }} bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transition-all duration-300">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Add Transaction
                    </h3>
                    <button id="cancel-transaction-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-xl text-sm border border-green-200 dark:border-green-800">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-xl text-sm border border-red-200 dark:border-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('business-entities.transactions.store', ['businessEntity' => $businessEntities->first() ? $businessEntities->first()->id : 0]) }}" id="store-transaction-form" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Entity</label>
                            <select name="business_entity_id" id="business_entity_id"
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" required>
                                <option value="">Select Entity</option>
                                @foreach ($businessEntities as $entity)
                                    <option value="{{ $entity->id }}" {{ old('business_entity_id', session('transactionData.business_entity_id')) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('business_entity_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asset <span class="text-gray-400 font-normal">(optional)</span></label>
                            <select name="asset_id" id="transaction_asset_id"
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">None — entity only</option>
                                @foreach ($assets as $asset)
                                    <option value="{{ $asset->id }}" data-entity-id="{{ $asset->business_entity_id }}"
                                        {{ (string) old('asset_id', session('transactionData.asset_id')) === (string) $asset->id ? 'selected' : '' }}>{{ $asset->name }}</option>
                                @endforeach
                            </select>
                            @error('asset_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                            <input type="date" name="date" value="{{ old('date', session('transactionData.date', now()->toDateString())) }}"
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" required>
                            @error('date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                            <input type="number" name="amount" step="0.01" value="{{ old('amount', session('transactionData.amount')) }}"
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" required>
                            @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <input type="text" name="description" value="{{ old('description', session('transactionData.description')) }}"
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Transaction::$transactionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transaction_type', session('transactionData.transaction_type')) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('transaction_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div id="related_entity_field" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related Entity</label>
                            <select name="related_entity_id" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">Select Related Entity</option>
                                @foreach($businessEntities->sortBy('legal_name') as $entity)
                                    <option value="{{ $entity->id }}" {{ old('related_entity_id', session('transactionData.related_entity_id')) == $entity->id ? 'selected' : '' }}>{{ $entity->legal_name }}</option>
                                @endforeach
                            </select>
                            @error('related_entity_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GST Amount</label>
                            <input type="number" name="gst_amount" step="0.01" value="{{ old('gst_amount', session('transactionData.gst_amount')) }}"
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            @error('gst_amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GST Status</label>
                            <select name="gst_status" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="included" {{ old('gst_status', session('transactionData.gst_status')) == 'included' ? 'selected' : '' }}>Included</option>
                                <option value="excluded" {{ old('gst_status', session('transactionData.gst_status')) == 'excluded' ? 'selected' : '' }}>Excluded</option>
                            </select>
                            @error('gst_status') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Receipt</label>
                            <input type="file" name="document"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-blue-300"
                                   accept="image/*,application/pdf">
                            @error('document') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Name</label>
                            <input type="text" name="document_name" value="{{ old('document_name') }}"
                                   class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                   placeholder="e.g., Invoice123">
                            @error('document_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <input type="hidden" name="receipt_path" value="{{ old('receipt_path', session('transactionData.receipt_path')) }}">
                    <div class="flex gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-xl text-sm shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Add Transaction
                        </button>
                    </div>
                </form>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $businessEntities->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Entities</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $assets->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Assets</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $persons->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Persons</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $allReminders->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Reminders</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $assetDueDates->count() + $entityDueDates->count() }}</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Due Soon</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">0</div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Transactions</div>
                </div>
            </div>

            {{-- Main Content: Two-Column Layout --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                {{-- Left Column (2/3 width) --}}
                <div class="xl:col-span-2 space-y-6">

                    {{-- Reminders --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                Reminders
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Next 15 days</span>
                            </h3>
                            <button type="button" class="inline-flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:hover:bg-amber-900/30 dark:text-amber-300 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" onclick="document.getElementById('reminder-form').classList.toggle('hidden')">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Add Reminder
                            </button>
                        </div>

                        {{-- Reminder Form --}}
                        <form id="reminder-form" class="hidden p-5 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700" method="POST" action="{{ route('reminders.store') }}">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Business Entity</label>
                                    <select name="business_entity_id" id="reminder_business_entity_id" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm">
                                        <option value="">Select Entity (Optional)</option>
                                        @foreach ($businessEntities as $entity)
                                            <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('business_entity_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asset</label>
                                    <select name="asset_id" id="reminder_asset_id" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" disabled>
                                        <option value="">Select Asset (Optional)</option>
                                        @foreach ($assets as $asset)
                                            <option value="{{ $asset->id }}" data-entity-id="{{ $asset->business_entity_id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                                        @endforeach
                                    </select>
                                    @error('asset_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reminder</label>
                                    <textarea name="content" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" rows="2" required>{{ old('content') }}</textarea>
                                    @error('content') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                                    <input type="date" name="next_due_date" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" min="{{ now()->format('Y-m-d') }}" value="{{ old('next_due_date') }}" required>
                                    @error('next_due_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Repeat</label>
                                    <select name="repeat_type" id="repeat_type" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm">
                                        <option value="none">One-off (No repeat)</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annual">Annual</option>
                                    </select>
                                    @error('repeat_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div id="repeat_end_date_container" style="display: none;">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date (Optional)</label>
                                    <input type="date" name="repeat_end_date" class="block w-full border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white text-sm" min="{{ now()->format('Y-m-d') }}" value="{{ old('repeat_end_date') }}">
                                    @error('repeat_end_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold shadow-sm transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Reminder
                                </button>
                            </div>
                        </form>

                        <div class="p-5">
                            @if ($allReminders->isEmpty())
                                <div class="text-center py-6">
                                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No reminders due in the next 15 days.</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($allReminders as $reminder)
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-600 hover:border-amber-200 dark:hover:border-amber-700 transition-colors">
                                            <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-amber-400"></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $reminder->content }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $reminder->asset ? $reminder->asset->name : ($reminder->businessEntity->legal_name ?? 'Unknown') }}
                                                    &middot; {{ $reminder->user->name ?? 'Unknown' }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        {{ $reminder->next_due_date ? $reminder->next_due_date->format('d/m/Y') : 'N/A' }}
                                                    </span>
                                                    @if($reminder->repeat_type && $reminder->repeat_type !== 'none')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                            {{ ucfirst($reminder->repeat_type) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 flex gap-1.5">
                                                <form action="{{ $reminder->is_note ? route('notes.finalize', $reminder->id) : route('reminders.complete', $reminder->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400 transition-colors" title="Finalize">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </form>
                                                <form action="{{ $reminder->is_note ? route('notes.extend', $reminder->id) : route('reminders.extend', $reminder->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 transition-colors" title="Extend">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Due Dates --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Upcoming Due Dates
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Next 15 days</span>
                            </h3>
                        </div>
                        <div class="p-5">
                            @if ($assetDueDates->isNotEmpty() || $entityDueDates->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach ($assetDueDates as $asset)
                                        @if ($asset->registration_due_date)
                                            <div class="flex items-start gap-4 p-4 rounded-xl bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30">
                                                <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-red-400"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Registration Due &mdash; {{ $asset->name }} ({{ $asset->asset_type }})</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $asset->businessEntity->legal_name ?? 'Unknown Entity' }} &middot; {{ $asset->registration_due_date->format('d/m/Y') }}</p>
                                                    @if ($asset->business_entity_id && $asset->businessEntity)
                                                        <div class="mt-2 flex gap-2">
                                                            <form action="{{ route('assets.finalize-due-date', [$asset->business_entity_id, $asset->id, 'registration']) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                            </form>
                                                            <form action="{{ route('assets.extend-due-date', [$asset->business_entity_id, $asset->id, 'registration']) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend (3 days)</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach

                                    @foreach ($entityDueDates as $entityDueDate)
                                        @if ($entityDueDate->asic_due_date)
                                            <div class="flex items-start gap-4 p-4 rounded-xl bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30">
                                                <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-red-400"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">ASIC Due &mdash; {{ $entityDueDate->businessEntity->legal_name }} ({{ $entityDueDate->role ?? 'Unknown Role' }})</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $entityDueDate->asic_due_date->format('d/m/Y') }}</p>
                                                    <div class="mt-2 flex gap-2">
                                                        <form action="{{ route('entity-persons.finalize-due-date', $entityDueDate->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                        </form>
                                                        <form action="{{ route('entity-persons.extend-due-date', $entityDueDate->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend (3 days)</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-6">
                                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming due dates.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Recent Items --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Business Entities --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Entities</h3>
                                <a href="{{ route('business-entities.index') }}" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($businessEntities->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No entities yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($businessEntities->take(3) as $entity)
                                            <a href="{{ route('business-entities.show', $entity->id) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $entity->legal_name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entity->entity_type ?? 'N/A' }}</p>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Assets --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Assets</h3>
                                <a href="{{ route('assets.index') }}" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($assets->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No assets yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($assets->take(3) as $asset)
                                            <a href="{{ route('business-entities.assets.show', [$asset->business_entity_id, $asset->id]) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $asset->name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $asset->asset_type }}</p>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-emerald-500 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Persons --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Persons</h3>
                                <a href="{{ route('persons.index') }}" class="text-xs font-medium text-violet-600 dark:text-violet-400 hover:underline">View All</a>
                            </div>
                            <div class="p-4">
                                @if ($uniquePersons->isEmpty())
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No persons yet.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($uniquePersons->take(3) as $personData)
                                            <a href="{{ route('persons.show', $personData['person']->id) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $personData['person']->first_name }} {{ $personData['person']->last_name }}</p>
                                                    <div class="flex gap-1.5 mt-1">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $personData['totalRoles'] }} role{{ $personData['totalRoles'] != 1 ? 's' : '' }}</span>
                                                        @if($personData['activeRoles'] > 0)
                                                            <span class="text-xs text-emerald-600 dark:text-emerald-400">&middot; {{ $personData['activeRoles'] }} active</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400 group-hover:text-violet-500 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column (1/3 width) --}}
                <div class="space-y-6">

                    {{-- Quick Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-1.5">
                            <a href="{{ route('business-entities.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">Add Entity</span>
                            </a>
                            <a href="{{ route('business-entities.assets.create', $businessEntities->first()?->id ?? 0) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">Add Asset</span>
                            </a>
                            <a href="{{ route('persons.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-violet-700 dark:group-hover:text-violet-300 transition-colors">Add Person</span>
                            </a>
                            <a href="{{ route('emails.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">Emails</span>
                            </a>
                        </div>
                    </div>

                    {{-- Accounting & Finance --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Accounting & Finance</h3>
                        </div>
                        <div class="p-4 space-y-1.5">
                            <a href="{{ route('chart-of-accounts.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">Chart of Accounts</span>
                            </a>
                            <a href="{{ route('bank-accounts.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">Bank Accounts</span>
                            </a>
                            <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition-colors">Transactions</span>
                            </a>
                            <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group">
                                <div class="w-9 h-9 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-700 dark:group-hover:text-orange-300 transition-colors">Invoices</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transactionBtn = document.getElementById('add-transaction-btn');
            const transactionSection = document.getElementById('add-transaction-section');
            const cancelTransactionBtn = document.getElementById('cancel-transaction-btn');
            const entitySelect = document.getElementById('business_entity_id');
            const relatedEntityField = document.getElementById('related_entity_field');
            const transactionAssetSelect = document.getElementById('transaction_asset_id');

            if (transactionBtn && transactionSection) {
                transactionBtn.addEventListener('click', () => {
                    transactionSection.classList.toggle('hidden');
                });
            }

            if (cancelTransactionBtn && transactionSection) {
                cancelTransactionBtn.addEventListener('click', () => {
                    if (!{{ session()->has('success') ? 'true' : 'false' }}) {
                        transactionSection.classList.add('hidden');
                    }
                });
            }

            if (entitySelect) {
                const storeForm = document.getElementById('store-transaction-form');

                function syncTransactionFormFromEntitySelect() {
                    const entityId = entitySelect.value;
                    if (entityId && storeForm) {
                        storeForm.action = `{{ url('business-entities') }}/${entityId}/transactions/store`;
                    }
                    const relatedSel = relatedEntityField ? relatedEntityField.querySelector('select') : null;
                    if (relatedSel) {
                        Array.from(relatedSel.options).forEach(opt => {
                            if (!opt.value) return;
                            opt.disabled = opt.value === entityId;
                        });
                    }
                    if (transactionAssetSelect) {
                        let keepValue = transactionAssetSelect.value;
                        Array.from(transactionAssetSelect.options).forEach(opt => {
                            if (!opt.value) return;
                            const match = opt.dataset.entityId === entityId;
                            opt.hidden = !match;
                            opt.disabled = !match;
                        });
                        const stillValid = Array.from(transactionAssetSelect.options).some(
                            o => o.value === keepValue && !o.disabled
                        );
                        if (!stillValid) {
                            transactionAssetSelect.value = '';
                        }
                    }
                }

                entitySelect.addEventListener('change', syncTransactionFormFromEntitySelect);
                syncTransactionFormFromEntitySelect();
            }

            @if (session('error') || session('transactionData'))
                if (transactionSection) transactionSection.classList.remove('hidden');
            @endif

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

            const transactionTypeSelect = document.getElementById('transaction_type');

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

                if (relatedPartyTypes.includes(transactionTypeSelect.value)) {
                    relatedEntityField.style.display = 'block';
                    relatedEntityField.querySelector('select').required = true;
                }
            }
        });
    </script>
</x-app-layout>
