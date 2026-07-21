<div id="loan-banking">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Loan &amp; Banking</h3>
        <button type="button"
                data-loan-banking-edit
                class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm shadow-md transition-all duration-200">
            <x-lucide-pencil class="h-4 w-4 mr-1" />
            Edit
        </button>
    </div>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Provider</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $asset->loan_provider ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Interest Rate</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                @if($asset->loan_interest_rate !== null)
                    {{ rtrim(rtrim(number_format((float) $asset->loan_interest_rate, 4, '.', ''), '0'), '.') }}%
                @else
                    —
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Payment</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                {{ $asset->loan_payment_amount !== null ? '$'.number_format($asset->loan_payment_amount, 2) : '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Frequency</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $asset->loan_payment_frequency ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Loan Balance</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                {{ $asset->loan_balance !== null ? '$'.number_format($asset->loan_balance, 2) : '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Need to Put</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                {{ $asset->equity_required !== null ? '$'.number_format($asset->equity_required, 2) : '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Direct Debit</dt>
            <dd class="text-gray-900 dark:text-gray-200">
                {{ $asset->direct_debit_amount !== null ? '$'.number_format($asset->direct_debit_amount, 2) : '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rent Paid By</dt>
            <dd class="text-gray-900 dark:text-gray-200">{{ $asset->rent_paid_by ?: '—' }}</dd>
        </div>
    </dl>
</div>
