<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ $businessEntity->legal_name }}
            </p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Email invoice {{ $invoice->invoice_number }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The invoice PDF is always attached.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('business-entities.invoices.send', [$businessEntity, $invoice]) }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                @csrf

                <div>
                    <label for="to_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
                    <input type="email" id="to_email" name="to_email" required value="{{ $toEmail }}" autocomplete="email"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                    @error('to_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                    <input type="text" id="subject" name="subject" required maxlength="255" value="{{ $subjectLine }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">
                    @error('subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                    <textarea id="message" name="message" rows="5" required
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm">{{ $messageBody }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Files on this invoice</legend>
                    <input type="hidden" name="files_reviewed" value="1">
                    @if ($files->isEmpty())
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No extra files are stored on this invoice.</p>
                    @else
                        @php
                            $reviewedFileSelection = old('files_reviewed') !== null;
                            $selectedDocumentIds = array_map('intval', (array) old('document_ids', []));
                        @endphp
                        <div class="mt-2 space-y-2">
                            @foreach ($files as $file)
                                <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                    <input type="checkbox" name="document_ids[]" value="{{ $file->id }}" @checked(! $reviewedFileSelection || in_array((int) $file->id, $selectedDocumentIds, true))
                                           class="rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>{{ $file->file_name ?: 'Attachment' }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('document_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Cancel</a>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        <x-lucide-mail class="h-4 w-4" aria-hidden="true" />
                        Send invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
