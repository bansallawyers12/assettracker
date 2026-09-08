<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoicePaymentAllocation;
use App\Models\Transaction;
use App\Support\DocumentUploadValidation;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoicePaymentService
{
    public function __construct(
        private DocumentUploadService $documentUploadService
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
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'bank_statement_entry_id' => 'nullable|integer|exists:bank_statement_entries,id',
            'payment_document_name' => 'nullable|string|max:255',
            'payment_document' => $paymentDocumentRules,
        ];
    }

    public function record(
        Request $request,
        BusinessEntity $businessEntity,
        Invoice $invoice
    ): Transaction {
        $data = $request->validate($this->validationRules());

        $bankAccount = BankAccount::query()->findOrFail((int) $data['bank_account_id']);
        if (! $bankAccount->canUseForTransaction($businessEntity)) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'The selected bank account is not linked to this entity.',
            ]);
        }

        $statementEntryId = ! empty($data['bank_statement_entry_id'])
            ? (int) $data['bank_statement_entry_id']
            : null;

        $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
            ? round((float) $data['amount'], 2)
            : null;

        return DB::transaction(function () use (
            $request,
            $businessEntity,
            $invoice,
            $data,
            $bankAccount,
            $statementEntryId,
            $amount
        ) {
            return $this->persistPayment(
                $businessEntity,
                $invoice,
                $bankAccount,
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
        if (! $bankAccount->canUseForTransaction($businessEntity)) {
            throw ValidationException::withMessages([
                'matches' => 'The selected bank account is not linked to this entity.',
            ]);
        }

        $paidAt = $statementEntry->date?->toDateString() ?? now()->toDateString();
        $reference = Str::limit(trim((string) $statementEntry->description), 255, '');
        $amount = round(abs((float) $statementEntry->amount), 2);

        return DB::transaction(function () use (
            $businessEntity,
            $invoice,
            $bankAccount,
            $paidAt,
            $reference,
            $statementEntry,
            $amount
        ) {
            return $this->persistPayment(
                $businessEntity,
                $invoice,
                $bankAccount,
                $paidAt,
                null,
                $reference !== '' ? $reference : null,
                (int) $statementEntry->id,
                $amount,
                null,
                'matches',
                'matches'
            );
        });
    }

    private function persistPayment(
        BusinessEntity $businessEntity,
        Invoice $invoice,
        BankAccount $bankAccount,
        string $paidAt,
        ?string $paymentMethod,
        ?string $paymentReference,
        ?int $statementEntryId,
        ?float $requestedAmount = null,
        ?Request $request = null,
        string $invoiceErrorKey = 'paid_at',
        string $statementErrorKey = 'bank_statement_entry_id'
    ): Transaction {
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
            'bank_account_id' => $bankAccount->id,
            'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
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
