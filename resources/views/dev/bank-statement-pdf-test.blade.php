<x-app-layout :skip-workspace-panels="true">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Bank statement PDF parser (dev test)
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-100">
                <p class="font-semibold">Local dev tool</p>
                <p class="mt-1 text-amber-800 dark:text-amber-200/90">
                    Upload a CBA, NAB, Macquarie, or Westpac PDF statement. Python extracts transaction rows and skips opening/closing balance and summary lines where possible.
                    Requires <code class="font-mono text-xs">pip install pdfplumber pypdf</code> in your Python environment.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <form method="POST" action="{{ route('dev.bank-statement-pdf-test.parse') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank hint</label>
                        <select
                            id="bank_name"
                            name="bank_name"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100"
                        >
                            @foreach ($bankHints ?? [] as $hintValue => $hintLabel)
                                <option value="{{ $hintValue }}" @selected(old('bank_name', $bankName) === $hintValue)>{{ $hintLabel }}</option>
                            @endforeach
                        </select>
                        @error('bank_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="statement_pdf" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statement PDF</label>
                        <input
                            type="file"
                            id="statement_pdf"
                            name="statement_pdf"
                            accept=".pdf,application/pdf"
                            required
                            class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-500"
                        >
                        @error('statement_pdf')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
                    >
                        Parse PDF
                    </button>
                </form>
            </div>

            @if ($error)
                <div class="rounded-xl border border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30 p-4 text-sm text-red-800 dark:text-red-200">
                    <p class="font-semibold">Parser error</p>
                    <p class="mt-1">{{ $error }}</p>
                </div>
            @endif

            @if ($metadata)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-sm text-gray-700 dark:text-gray-300">
                    <p>
                        <span class="font-semibold">Detected bank:</span>
                        {{ $metadata['detected_bank'] ?? 'unknown' }}
                        <span class="mx-2">·</span>
                        <span class="font-semibold">Pages:</span>
                        {{ $metadata['pages'] ?? '—' }}
                        <span class="mx-2">·</span>
                        <span class="font-semibold">Transactions:</span>
                        {{ $metadata['entry_count'] ?? count($entries) }}
                    </p>
                </div>
            @endif

            @if (count($entries) > 0)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Parsed transactions ({{ count($entries) }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Description</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Balance</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($entries as $entry)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                            {{ $entry['date'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-md">
                                            {{ $entry['description'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap {{ ($entry['amount'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ number_format((float) ($entry['amount'] ?? 0), 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap text-gray-600 dark:text-gray-400">
                                            @if (array_key_exists('balance', $entry) && $entry['balance'] !== null)
                                                {{ number_format((float) $entry['balance'], 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $entry['transaction_type'] ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif (($parsed ?? false) && ! $error)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-sm text-gray-600 dark:text-gray-400">
                    No transaction rows were extracted. Try another PDF or a different bank hint.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
