<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ $businessEntity->legal_name }}
            </p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Email this file</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $primaryName }} is attached. Add more stored files or a file from this computer.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('business-entities.email-file.send', $businessEntity) }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                @csrf
                @if ($primaryDocumentId)
                    <input type="hidden" name="primary_document_id" value="{{ $primaryDocumentId }}">
                @endif
                @if ($primaryComplianceFileId)
                    <input type="hidden" name="primary_compliance_file_id" value="{{ $primaryComplianceFileId }}">
                @endif
                @if ($assetId)
                    <input type="hidden" name="asset_id" value="{{ $assetId }}">
                @endif
                <input type="hidden" name="from" value="{{ $from }}">

                <div>
                    <label for="to_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
                    <input type="email" id="to_email" name="to_email" required list="recipient-options" value="{{ $toEmail }}" autocomplete="email"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
                           placeholder="Choose a contact or type an address">
                    <datalist id="recipient-options">
                        @foreach ($recipients as $recipient)
                            <option value="{{ $recipient['email'] }}">{{ $recipient['label'] }}</option>
                        @endforeach
                    </datalist>
                    @error('to_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                    <input type="text" id="subject" name="subject" required maxlength="255" value="{{ $subjectLine }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea id="message" name="message" rows="5" required
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">{{ $messageBody }}</textarea>
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Attach from documents</legend>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $primaryName }}</p>
                    @if ($extraFiles !== [])
                        @php
                            $selectedDocumentIds = array_map('intval', (array) old('document_ids', []));
                            $selectedComplianceIds = array_map('intval', (array) old('compliance_file_ids', []));
                        @endphp
                        <div class="mt-2 max-h-48 space-y-2 overflow-y-auto">
                            @foreach ($extraFiles as $file)
                                @php
                                    $fileSelected = $file['kind'] === 'compliance'
                                        ? in_array((int) $file['id'], $selectedComplianceIds, true)
                                        : in_array((int) $file['id'], $selectedDocumentIds, true);
                                @endphp
                                <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                    <input type="checkbox" name="{{ $file['kind'] === 'compliance' ? 'compliance_file_ids[]' : 'document_ids[]' }}" value="{{ $file['id'] }}" @checked($fileSelected)
                                           class="rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>{{ $file['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('document_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('compliance_file_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div>
                    <label for="attachments" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From this computer</label>
                    <input type="file" id="attachments" name="attachments[]" multiple
                           class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ $cancelUrl }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Cancel</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        <x-lucide-mail class="h-4 w-4" aria-hidden="true" />
                        Send email
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
