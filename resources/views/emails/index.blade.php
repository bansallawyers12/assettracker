<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
                {{ __('Emails') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('emails.sync') }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync Gmail
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-4 py-2 rounded-lg border border-blue-200 dark:border-blue-700">{{ session('status') }}</div>
            @endif

            <!-- Email Interface -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <form method="GET" class="flex gap-2 flex-wrap">
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search emails..."
                                   class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                                   class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                                   class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <select name="label_id" class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Labels</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}" {{ ($filters['label_id'] ?? '') == $label->id ? 'selected' : '' }}>{{ $label->name }}</option>
                                @endforeach
                            </select>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Filter</button>
                        </form>
                    </div>

                    @php $firstMessage = $messages->first(); @endphp

                    <div class="flex gap-6">
                        <div class="w-full lg:w-5/12">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700">
                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($messages as $message)
                                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                            <div class="flex items-start justify-between gap-3">
                                                <a href="{{ route('emails.show', $message->id) }}" target="emailViewer" class="flex-1 group">
                                                    <div class="text-blue-900 dark:text-blue-200 font-semibold group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">{{ $message->subject ?: '(No subject)' }}</div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $message->sender_name ?: $message->sender_email }} — {{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                                                    <div class="mt-1 flex gap-2 flex-wrap">
                                                        @foreach ($message->labels as $label)
                                                            <span class="text-xs px-2 py-1 rounded-full" style="background-color: {{ $label->color ?? '#e5e7eb' }}; color:#111827">{{ $label->name }}</span>
                                                        @endforeach
                                                    </div>
                                                </a>
                                                <div class="shrink-0">
                                                    <div class="flex space-x-2">
                                                        <a href="{{ route('emails.reply', $message->id) }}" 
                                                           class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                            </svg>
                                                            Reply
                                                        </a>
                                                        <details class="relative">
                                                            <summary class="cursor-pointer select-none inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs transition-colors">Allocate</summary>
                                                            <div class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3 z-10">
                                                                <form method="POST" action="{{ route('emails.allocate.entity', $message->id) }}" class="space-y-2">
                                                                    @csrf
                                                                    <label class="block text-xs text-gray-600 dark:text-gray-300">Business Entity</label>
                                                                    <select name="business_entity_id" class="w-full border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                                        <option value="">Select entity...</option>
                                                                        @php($entities = \App\Models\BusinessEntity::where('user_id', auth()->id())->orderBy('legal_name')->get())
                                                                        @foreach ($entities as $entity)
                                                                            <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs transition-colors">Allocate to Entity</button>
                                                                </form>
                                                                <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>
                                                                <form method="POST" action="{{ route('emails.allocate.asset', $message->id) }}" class="space-y-2">
                                                                    @csrf
                                                                    <label class="block text-xs text-gray-600 dark:text-gray-300">Asset</label>
                                                                    <select name="asset_id" class="w-full border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                                        <option value="">Select asset...</option>
                                                                        @php($assets = \App\Models\Asset::where('user_id', auth()->id())->orderBy('name')->get())
                                                                        @foreach ($assets as $asset)
                                                                            <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors">Allocate to Asset</button>
                                                                </form>
                                                            </div>
                                                        </details>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-6 text-gray-500 dark:text-gray-400 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-lg font-medium">No emails found</p>
                                            <p class="text-sm">Try adjusting your search criteria or sync with Gmail to get started.</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="mt-4">
                                    {{ $messages->links() }}
                                </div>
                            </div>
                        </div>

                        <div class="hidden lg:block w-7/12">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 overflow-hidden">
                                @if ($firstMessage)
                                    <iframe name="emailViewer" src="{{ route('emails.show', $firstMessage->id) }}" class="w-full" style="height: calc(100vh - 260px);"></iframe>
                                @else
                                    <div class="p-6 text-gray-500 dark:text-gray-400 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">Select an email to preview</p>
                                        <p class="text-sm">Choose an email from the list to view its contents here.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


