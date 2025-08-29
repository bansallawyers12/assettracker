<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-blue-900 dark:text-blue-200 leading-tight">
            {{ __('Emails') }}
        </h2>
    </x-slot>

    <div class="py-8 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-4 py-2 rounded">{{ session('status') }}</div>
            @endif

            <div class="flex items-center justify-between">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search"
                           class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                    <select name="label_id" class="border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white px-3 py-2">
                        <option value="">All Labels</option>
                        @foreach ($labels as $label)
                            <option value="{{ $label->id }}" {{ ($filters['label_id'] ?? '') == $label->id ? 'selected' : '' }}>{{ $label->name }}</option>
                        @endforeach
                    </select>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md">Filter</button>
                </form>
                <div class="flex gap-2">
                    <a href="{{ route('emails.sync') }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md">Sync Gmail</a>
                </div>
            </div>

            @php $firstMessage = $messages->first(); @endphp

            <div class="flex gap-6">
                <div class="w-full lg:w-5/12">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($messages as $message)
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex items-start justify-between gap-3">
                                        <a href="{{ route('emails.show', $message->id) }}" target="emailViewer" class="flex-1">
                                            <div class="text-blue-900 dark:text-blue-200 font-semibold">{{ $message->subject ?: '(No subject)' }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-300">From: {{ $message->sender_name ?: $message->sender_email }} — {{ optional($message->sent_date)->format('Y-m-d H:i') }}</div>
                                            <div class="mt-1 flex gap-2 flex-wrap">
                                                @foreach ($message->labels as $label)
                                                    <span class="text-xs px-2 py-1 rounded" style="background-color: {{ $label->color ?? '#e5e7eb' }}; color:#111827">{{ $label->name }}</span>
                                                @endforeach
                                            </div>
                                        </a>
                                        <div class="shrink-0">
                                            <details class="relative">
                                                <summary class="cursor-pointer select-none inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs">Allocate</summary>
                                                <div class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow p-3 z-10">
                                                    <form method="POST" action="{{ route('emails.allocate.entity', $message->id) }}" class="space-y-2">
                                                        @csrf
                                                        <label class="block text-xs text-gray-600 dark:text-gray-300">Business Entity</label>
                                                        <select name="business_entity_id" class="w-full border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white text-sm">
                                                            <option value="">Select entity...</option>
                                                            @php($entities = \App\Models\BusinessEntity::where('user_id', auth()->id())->orderBy('legal_name')->get())
                                                            @foreach ($entities as $entity)
                                                                <option value="{{ $entity->id }}">{{ $entity->legal_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Allocate to Entity</button>
                                                    </form>
                                                    <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>
                                                    <form method="POST" action="{{ route('emails.allocate.asset', $message->id) }}" class="space-y-2">
                                                        @csrf
                                                        <label class="block text-xs text-gray-600 dark:text-gray-300">Asset</label>
                                                        <select name="asset_id" class="w-full border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white text-sm">
                                                            <option value="">Select asset...</option>
                                                            @php($assets = \App\Models\Asset::where('user_id', auth()->id())->orderBy('name')->get())
                                                            @foreach ($assets as $asset)
                                                                <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->asset_type }})</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">Allocate to Asset</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-gray-500 dark:text-gray-400">No emails found.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4">
                        {{ $messages->links() }}
                    </div>
                </div>

                <div class="hidden lg:block w-7/12">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 overflow-hidden">
                        @if ($firstMessage)
                            <iframe name="emailViewer" src="{{ route('emails.show', $firstMessage->id) }}" class="w-full" style="height: calc(100vh - 260px);"></iframe>
                        @else
                            <div class="p-6 text-gray-500 dark:text-gray-400">Select an email to preview.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


