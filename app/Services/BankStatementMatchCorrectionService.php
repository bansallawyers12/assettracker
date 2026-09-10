<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoicePaymentAllocation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankStatementMatchCorrectionService
{
    /**
     * Unlink this account's statement line(s) from the transaction without deleting the booking.
     *
     * @return array{unlinked_entries: int, transaction_id: int}
     */
    public function unmatch(BankAccount $bankAccount, BusinessEntity $businessEntity, Transaction $transaction): array
    {
        $this->assertTransactionOnAccount($bankAccount, $businessEntity, $transaction);

        return DB::transaction(function () use ($bankAccount, $businessEntity, $transaction) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $this->assertTransactionOnAccount($bankAccount, $businessEntity, $locked);

            $entries = $locked->bankStatementEntries()
                ->where('bank_account_id', $bankAccount->id)
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'This transaction is not linked to a statement line on this account.',
                ]);
            }

            foreach ($entries as $entry) {
                $entry->transaction_id = null;
                $entry->save();
            }

            return [
                'unlinked_entries' => $entries->count(),
                'transaction_id' => (int) $locked->id,
            ];
        });
    }

    /**
     * Delete the booking and return this account's statement line(s) to unmatched.
     * Does not delete linked receipt documents. Leaves transfer siblings intact.
     *
     * @return array{transaction_id: int, invoices_reset: int, unlinked_entries: int, had_transfer_sibling: bool}
     */
    public function removeAndRedo(BankAccount $bankAccount, BusinessEntity $businessEntity, Transaction $transaction): array
    {
        $this->assertTransactionOnAccount($bankAccount, $businessEntity, $transaction);

        return DB::transaction(function () use ($bankAccount, $businessEntity, $transaction) {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $this->assertTransactionOnAccount($bankAccount, $businessEntity, $locked);

            $entries = $locked->bankStatementEntries()
                ->where('bank_account_id', $bankAccount->id)
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'This transaction is not linked to a statement line on this account.',
                ]);
            }

            $hadTransferSibling = $this->hasTransferSibling($locked);

            $invoiceIds = $this->linkedInvoiceIds([(int) $locked->id]);

            InvoicePaymentAllocation::query()
                ->where('transaction_id', $locked->id)
                ->delete();

            $invoicesReset = 0;
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
                if (! $invoice) {
                    continue;
                }
                $invoice->syncPaymentStateFromAllocations();
                $invoicesReset++;
            }

            foreach ($entries as $entry) {
                $entry->transaction_id = null;
                $entry->save();
            }

            $transactionId = (int) $locked->id;
            $unlinkedEntries = $entries->count();
            $locked->delete();

            return [
                'transaction_id' => $transactionId,
                'invoices_reset' => $invoicesReset,
                'unlinked_entries' => $unlinkedEntries,
                'had_transfer_sibling' => $hadTransferSibling,
            ];
        });
    }

    public function hasTransferSibling(Transaction $transaction): bool
    {
        $groupId = $transaction->transfer_group_id;
        if ($groupId === null || $groupId === '') {
            return false;
        }

        return Transaction::query()
            ->where('transfer_group_id', $groupId)
            ->where('id', '!=', $transaction->id)
            ->exists();
    }

    private function assertTransactionOnAccount(
        BankAccount $bankAccount,
        BusinessEntity $businessEntity,
        Transaction $transaction
    ): void {
        if ((int) $transaction->business_entity_id !== (int) $businessEntity->id) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Transaction does not belong to the selected entity.',
            ]);
        }

        if ((int) $transaction->bank_account_id !== (int) $bankAccount->id) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Transaction is not on this bank account.',
            ]);
        }
    }

    /**
     * @param  list<int>  $transactionIds
     * @return list<int>
     */
    private function linkedInvoiceIds(array $transactionIds): array
    {
        if ($transactionIds === []) {
            return [];
        }

        $fromAllocations = InvoicePaymentAllocation::query()
            ->whereIn('transaction_id', $transactionIds)
            ->pluck('invoice_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fromLegacyFk = Invoice::query()
            ->whereIn('payment_transaction_id', $transactionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($fromAllocations, $fromLegacyFk)));
    }
}
