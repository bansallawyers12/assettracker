<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Support\DocumentUploadValidation;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use (
            $request,
            $businessEntity,
            $invoice,
            $data,
            $bankAccount,
            $statementEntryId
        ) {
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedInvoice || (int) $lockedInvoice->business_entity_id !== (int) $businessEntity->id) {
                throw ValidationException::withMessages([
                    'paid_at' => 'Invoice could not be found for payment.',
                ]);
            }

            if ($lockedInvoice->status !== 'approved') {
                throw ValidationException::withMessages([
                    'paid_at' => 'Only approved (posted) invoices can be marked paid.',
                ]);
            }

            if ($lockedInvoice->paid_at || $lockedInvoice->payment_transaction_id) {
                throw ValidationException::withMessages([
                    'paid_at' => 'This invoice is already recorded as paid.',
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
                        'bank_statement_entry_id' => 'The selected statement line is not available on this account.',
                    ]);
                }

                if (abs(abs((float) $statementEntry->amount) - (float) $lockedInvoice->total_amount) > 0.005) {
                    throw ValidationException::withMessages([
                        'bank_statement_entry_id' => 'Statement line amount does not match the invoice total.',
                    ]);
                }

                if ((float) $statementEntry->amount < 0) {
                    throw ValidationException::withMessages([
                        'bank_statement_entry_id' => 'Invoice payments must match an incoming (credit) statement line.',
                    ]);
                }
            }

            $paymentDocumentId = null;
            if ($request->hasFile('payment_document')) {
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
                'date' => $data['paid_at'],
                'amount' => $lockedInvoice->total_amount,
                'description' => 'Payment received for Invoice '.$lockedInvoice->invoice_number,
                'transaction_type' => Transaction::TYPE_INVOICE_PAYMENT,
                'invoice_number' => $lockedInvoice->invoice_number,
                'payment_status' => 'paid',
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'] ?? null,
                'payment_document_id' => $paymentDocumentId,
                'gst_amount' => null,
                'gst_status' => 'gst_free',
                'gst_basis' => null,
            ]);

            if ($statementEntry) {
                $statementEntry->update(['transaction_id' => $transaction->id]);
            }

            $lockedInvoice->update([
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'status' => 'paid',
                'payment_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }
}
