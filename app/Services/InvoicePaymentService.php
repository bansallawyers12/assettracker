<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoicePaymentAllocation;
use App\Models\Transaction;
use App\Support\DocumentUploadValidation;
use App\Support\InvoicePaymentAllocator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoicePaymentService
{
    public function __construct(
        private DocumentUploadService $documentUploadService,
        private InvoicePaymentAllocator $allocator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        $fileRules = DocumentUploadValidation::rules('payment_document');
        $paymentDocumentRules = $fileRules['payment_document'] ?? ['file'];
        // Optional receipt: keep type/size checks, drop required.
        $paymentDocumentRules = array_values(array_filter(
            $paymentDocumentRules,
            static fn ($rule) => $rule !== 'required'
        ));
        array_unshift($paymentDocumentRules, 'nullable');

        return [
            'paid_at' => 'required|date',
            'amount' => 'nullable|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'payment_channel' => [
                'nullable',
                'string',
                Rule::in([
                    Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
                    Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
                ]),
            ],
            'bank_account_id' => [
                'nullable',
                'integer',
                'exists:bank_accounts,id',
                'required_if:payment_channel,'.Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
                'prohibited_if:payment_channel,'.Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
            ],
            'bank_statement_entry_id' => [
                'nullable',
                'integer',
                'exists:bank_statement_entries,id',
                'prohibited_if:payment_channel,'.Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS,
            ],
            'payment_document_name' => 'nullable|string|max:255',
            'payment_document' => $paymentDocumentRules,
        ];
    }

    public function record(
        Request $request,
        BusinessEntity $businessEntity,
        Invoice $invoice
    ): Transaction {
        if (! $request->filled('payment_channel')) {
            $request->merge([
                'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
            ]);
        }

        $data = $request->validate($this->validationRules());
        $channel = (string) ($data['payment_channel'] ?? Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT);

        $bankAccount = null;
        $statementEntryId = null;

        if ($channel === Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT) {
            $bankAccount = BankAccount::query()->findOrFail((int) $data['bank_account_id']);
            if (! $bankAccount->canUseForTransaction($businessEntity)) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'The selected bank account is not linked to this entity.',
                ]);
            }

            $statementEntryId = ! empty($data['bank_statement_entry_id'])
                ? (int) $data['bank_statement_entry_id']
                : null;
        }

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
            ? round((float) $data['amount'], 2)
            : null;

        return DB::transaction(function () use (
            $request,
            $businessEntity,
            $invoice,
            $data,
            $bankAccount,
            $channel,
            $statementEntryId,
            $amount
        ) {
            return $this->persistPayment(
                $businessEntity,
                $invoice,
                $bankAccount,
                $channel,
                (string) $data['paid_at'],
                $data['payment_method'] ?? null,
                $data['payment_reference'] ?? null,
                $statementEntryId,
                $amount,
                $request
            );
        });
    }

    public function recordFromStatementEntry(
        BusinessEntity $businessEntity,
        Invoice $invoice,
        BankAccount $bankAccount,
        BankStatementEntry $statementEntry
    ): Transaction {
        $amount = round(abs((float) $statementEntry->amount), 2);

        return $this->recordAllocatedFromStatementEntry(
            $businessEntity,
            $bankAccount,
            $statementEntry,
            [['invoice_id' => (int) $invoice->id, 'amount' => $amount]]
        );
    }

    /**
     * One invoice_payment for the full statement credit; N allocation rows.
     *
     * @param  list<array{invoice_id: int|string, amount: float|int|string}>  $allocations
     */
    public function recordAllocatedFromStatementEntry(
        BusinessEntity $businessEntity,
        BankAccount $bankAccount,
        BankStatementEntry $statementEntry,
        array $allocations
    ): Transaction {
        if (! $bankAccount->canUseForTransaction($businessEntity)) {
            throw ValidationException::withMessages([
                'matches' => 'The selected bank account is not linked to this entity.',
            ]);
        }

        $paidAt = $statementEntry->date?->toDateString() ?? now()->toDateString();
        $reference = Str::limit(trim((string) $statementEntry->description), 255, '');
        $credit = round(abs((float) $statementEntry->amount), 2);

        return DB::transaction(function () use (
            $businessEntity,
            $bankAccount,
            $statementEntry,
            $allocations,
            $paidAt,
            $reference,
            $credit
        ) {
            return $this->persistAllocatedPayment(
                $businessEntity,
                $bankAccount,
                $statementEntry,
                $allocations,
                $paidAt,
                $reference !== '' ? $reference : null,
                $credit
            );
        });
    }

    /**
     * @param  list<array{invoice_id: int|string, amount: float|int|string}>  $allocations
     */
    private function persistAllocatedPayment(
        BusinessEntity $businessEntity,
        BankAccount $bankAccount,
        BankStatementEntry $statementEntry,
        array $allocations,
        string $paidAt,
        ?string $paymentReference,
        float $credit
    ): Transaction {
        $normalized = $this->normalizeAllocations($allocations);

        $lockedEntry = BankStatementEntry::query()
            ->whereKey($statementEntry->id)
            ->lockForUpdate()
            ->first();

        if (! $lockedEntry
            || (int) $lockedEntry->bank_account_id !== (int) $bankAccount->id
            || $lockedEntry->transaction_id !== null) {
            throw ValidationException::withMessages([
                'matches' => 'The selected statement line is not available on this account.',
            ]);
        }

        if ((float) $lockedEntry->amount < 0) {
            throw ValidationException::withMessages([
                'matches' => 'Invoice payments must match an incoming (credit) statement line.',
            ]);
        }

        $entryCredit = round(abs((float) $lockedEntry->amount), 2);
        if (abs($entryCredit - $credit) > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            $credit = $entryCredit;
        }

        $allocationSum = round(array_sum(array_column($normalized, 'amount')), 2);
        if (abs($allocationSum - $credit) > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            throw ValidationException::withMessages([
                'matches' => 'Allocated amounts must sum exactly to the statement credit ('.$credit.').',
            ]);
        }

        $invoiceIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['invoice_id'],
            $normalized
        )));
        sort($invoiceIds);

        /** @var Collection<int, Invoice> $lockedInvoices */
        $lockedInvoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($lockedInvoices->count() !== count($invoiceIds)) {
            throw ValidationException::withMessages([
                'matches' => 'One or more invoices could not be found for payment.',
            ]);
        }

        foreach ($lockedInvoices as $invoice) {
            // Fresh remaining; ignore any stale eager-loaded allocations from candidate lists.
            $invoice->unsetRelation('paymentAllocations');

            if ((int) $invoice->business_entity_id !== (int) $businessEntity->id) {
                throw ValidationException::withMessages([
                    'matches' => 'Selected invoice does not belong to the booking entity.',
                ]);
            }

            if (! in_array($invoice->status, ['approved', 'partial'], true)) {
                throw ValidationException::withMessages([
                    'matches' => 'Only approved or partially paid invoices can receive a payment.',
                ]);
            }
        }

        if (! $this->allocator->invoicesSharePool($lockedInvoices->values())) {
            throw ValidationException::withMessages([
                'matches' => 'All invoices in a split must belong to the same lease or customer pool.',
            ]);
        }

        foreach ($normalized as $row) {
            $invoice = $lockedInvoices->get((int) $row['invoice_id']);
            $remaining = $invoice->amountDue();
            if ($row['amount'] - $remaining > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
                throw ValidationException::withMessages([
                    'matches' => 'Payment amount cannot exceed the invoice balance remaining ('.$remaining.').',
                ]);
            }
        }

        /** @var Invoice $primaryInvoice */
        $primaryInvoice = $lockedInvoices->get((int) $normalized[0]['invoice_id']);
        $invoiceNumbers = $lockedInvoices
            ->sortBy('id')
            ->pluck('invoice_number')
            ->filter()
            ->values()
            ->all();

        $description = count($normalized) === 1
            ? (
                ($normalized[0]['amount'] >= ($primaryInvoice->amountDue() - BankStatementMatchSuggester::AMOUNT_TOLERANCE)
                    ? 'Payment received for Invoice '
                    : 'Partial payment for Invoice ')
                .$primaryInvoice->invoice_number
            )
            : 'Payment received for invoices '.implode(', ', $invoiceNumbers);

        $transaction = Transaction::create([
            'business_entity_id' => $businessEntity->id,
            'asset_id' => $primaryInvoice->asset_id,
            'bank_account_id' => $bankAccount->id,
            'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
            'date' => $paidAt,
            'amount' => $credit,
            'description' => $description,
            'transaction_type' => Transaction::TYPE_INVOICE_PAYMENT,
            'invoice_number' => $primaryInvoice->invoice_number,
            'payment_status' => 'paid',
            'paid_at' => $paidAt,
            'payment_method' => null,
            'payment_document_id' => null,
            'gst_amount' => null,
            'gst_status' => 'gst_free',
            'gst_basis' => null,
        ]);

        $lockedEntry->update(['transaction_id' => $transaction->id]);

        foreach ($normalized as $row) {
            InvoicePaymentAllocation::create([
                'transaction_id' => $transaction->id,
                'invoice_id' => (int) $row['invoice_id'],
                'amount' => $row['amount'],
            ]);
        }

        foreach ($invoiceIds as $invoiceId) {
            /** @var Invoice $invoice */
            $invoice = $lockedInvoices->get($invoiceId);
            $invoice->unsetRelation('paymentAllocations');
            $invoice->syncPaymentStateFromAllocations($paidAt, null, $paymentReference);
        }

        return $transaction;
    }

    /**
     * @param  list<array{invoice_id: int|string, amount: float|int|string}>  $allocations
     * @return list<array{invoice_id: int, amount: float}>
     */
    private function normalizeAllocations(array $allocations): array
    {
        if ($allocations === []) {
            throw ValidationException::withMessages([
                'matches' => 'Invoice match requires at least one allocation.',
            ]);
        }

        $normalized = [];
        $seen = [];

        foreach ($allocations as $row) {
            $invoiceId = (int) ($row['invoice_id'] ?? 0);
            $amount = round((float) ($row['amount'] ?? 0), 2);

            if ($invoiceId <= 0) {
                throw ValidationException::withMessages([
                    'matches' => 'Each allocation requires an invoice id.',
                ]);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'matches' => 'Each allocation amount must be greater than zero.',
                ]);
            }

            if (isset($seen[$invoiceId])) {
                throw ValidationException::withMessages([
                    'matches' => 'Duplicate invoice in allocation split.',
                ]);
            }

            $seen[$invoiceId] = true;
            $normalized[] = [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    private function persistPayment(
        BusinessEntity $businessEntity,
        Invoice $invoice,
        ?BankAccount $bankAccount,
        string $paymentChannel,
        string $paidAt,
        ?string $paymentMethod,
        ?string $paymentReference,
        ?int $statementEntryId,
        ?float $requestedAmount = null,
        ?Request $request = null,
        string $invoiceErrorKey = 'paid_at',
        string $statementErrorKey = 'bank_statement_entry_id'
    ): Transaction {
        if ($paymentChannel === Transaction::PAYMENT_CHANNEL_DIRECTOR_FUNDS) {
            $bankAccount = null;
            $statementEntryId = null;
        } elseif ($bankAccount === null) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'A bank account is required for bank payments.',
            ]);
        }

        $lockedInvoice = Invoice::query()
            ->whereKey($invoice->id)
            ->lockForUpdate()
            ->first();

        if (! $lockedInvoice || (int) $lockedInvoice->business_entity_id !== (int) $businessEntity->id) {
            throw ValidationException::withMessages([
                $invoiceErrorKey => 'Invoice could not be found for payment.',
            ]);
        }

        if (! in_array($lockedInvoice->status, ['approved', 'partial'], true)) {
            throw ValidationException::withMessages([
                $invoiceErrorKey => 'Only approved or partially paid invoices can receive a payment.',
            ]);
        }

        $remaining = $lockedInvoice->amountDue();
        if ($remaining <= BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            throw ValidationException::withMessages([
                $invoiceErrorKey => 'This invoice is already fully paid.',
            ]);
        }

        $statementEntry = null;
        if ($statementEntryId) {
            if ($bankAccount === null) {
                throw ValidationException::withMessages([
                    $statementErrorKey => 'Statement matching requires a bank account payment.',
                ]);
            }

            $statementEntry = BankStatementEntry::query()
                ->where('id', $statementEntryId)
                ->lockForUpdate()
                ->first();

            if (! $statementEntry
                || (int) $statementEntry->bank_account_id !== (int) $bankAccount->id
                || $statementEntry->transaction_id !== null) {
                throw ValidationException::withMessages([
                    $statementErrorKey => 'The selected statement line is not available on this account.',
                ]);
            }

            if ((float) $statementEntry->amount < 0) {
                throw ValidationException::withMessages([
                    $statementErrorKey => 'Invoice payments must match an incoming (credit) statement line.',
                ]);
            }

            $requestedAmount = round(abs((float) $statementEntry->amount), 2);
        }

        $paymentAmount = $requestedAmount ?? $remaining;
        $paymentAmount = round($paymentAmount, 2);

        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                $invoiceErrorKey === 'matches' ? $invoiceErrorKey : 'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        if ($paymentAmount - $remaining > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            throw ValidationException::withMessages([
                $statementEntry
                    ? $statementErrorKey
                    : ($invoiceErrorKey === 'matches' ? $invoiceErrorKey : 'amount') => 'Payment amount cannot exceed the invoice balance remaining ('.$remaining.').',
            ]);
        }

        $settlesRemaining = $paymentAmount >= ($remaining - BankStatementMatchSuggester::AMOUNT_TOLERANCE);
        $allocatedAmount = $settlesRemaining ? $remaining : $paymentAmount;

        if ($statementEntry
            && abs(abs((float) $statementEntry->amount) - $allocatedAmount) > BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
            throw ValidationException::withMessages([
                $statementErrorKey => 'Statement line amount does not match the payment amount.',
            ]);
        }

        $paymentDocumentId = null;
        if ($request?->hasFile('payment_document')) {
            $lockedInvoice->loadMissing('asset');
            /** @var UploadedFile $payFile */
            $payFile = $request->file('payment_document');
            $displayName = $request->filled('payment_document_name')
                ? trim((string) $request->input('payment_document_name'))
                : $payFile->getClientOriginalName();
            $labelBase = $request->filled('payment_document_name')
                ? trim((string) $request->input('payment_document_name'))
                : (pathinfo($payFile->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Payment Receipt');

            $document = $this->documentUploadService->createTransactionReceiptDocumentFromUpload(
                $businessEntity,
                $lockedInvoice->asset,
                $payFile,
                $displayName,
                $labelBase ?: 'Payment Receipt',
                'Payment receipt for Invoice '.$lockedInvoice->invoice_number
            );
            $paymentDocumentId = $document->id;
        }

        $transaction = Transaction::create([
            'business_entity_id' => $businessEntity->id,
            'asset_id' => $lockedInvoice->asset_id,
            'bank_account_id' => $bankAccount?->id,
            'payment_channel' => $paymentChannel,
            'date' => $paidAt,
            'amount' => $allocatedAmount,
            'description' => ($settlesRemaining ? 'Payment received for Invoice ' : 'Partial payment for Invoice ')
                .$lockedInvoice->invoice_number,
            'transaction_type' => Transaction::TYPE_INVOICE_PAYMENT,
            'invoice_number' => $lockedInvoice->invoice_number,
            'payment_status' => 'paid',
            'paid_at' => $paidAt,
            'payment_method' => $paymentMethod,
            'payment_document_id' => $paymentDocumentId,
            'gst_amount' => null,
            'gst_status' => 'gst_free',
            'gst_basis' => null,
        ]);

        if ($statementEntry) {
            $statementEntry->update(['transaction_id' => $transaction->id]);
        }

        InvoicePaymentAllocation::create([
            'transaction_id' => $transaction->id,
            'invoice_id' => $lockedInvoice->id,
            'amount' => $allocatedAmount,
        ]);

        $lockedInvoice->unsetRelation('paymentAllocations');
        $lockedInvoice->syncPaymentStateFromAllocations(
            $paidAt,
            $paymentMethod,
            $paymentReference
        );

        return $transaction;
    }
}
