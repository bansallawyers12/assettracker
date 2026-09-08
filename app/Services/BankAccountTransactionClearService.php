<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoicePaymentAllocation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BankAccountTransactionClearService
{
    /**
     * @return array{
     *     transactions: int,
     *     linked_invoices: int,
     *     bank_statement_entries: int
     * }
     */
    public function preview(BusinessEntity $businessEntity, BankAccount $bankAccount): array
    {
        $transactionIds = $this->transactionIdsForScope($businessEntity, $bankAccount);

        return [
            'transactions' => count($transactionIds),
            'linked_invoices' => count($this->linkedInvoiceIds($transactionIds)),
            'bank_statement_entries' => $this->matchedBankStatementEntryCount($transactionIds),
        ];
    }

    /**
     * @return array{
     *     transactions_deleted: int,
     *     invoices_reset: int
     * }
     */
    public function clear(BusinessEntity $businessEntity, BankAccount $bankAccount): array
    {
        return DB::transaction(function () use ($businessEntity, $bankAccount) {
            $transactionIds = $this->transactionIdsForScope($businessEntity, $bankAccount);
            $invoiceIds = $this->linkedInvoiceIds($transactionIds);

            if ($transactionIds !== []) {
                InvoicePaymentAllocation::query()
                    ->whereIn('transaction_id', $transactionIds)
                    ->delete();
            }

            $invoicesReset = 0;
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
                if (! $invoice) {
                    continue;
                }
                $invoice->syncPaymentStateFromAllocations();
                $invoicesReset++;
            }

            $transactionsDeleted = $this->deleteTransactions($transactionIds);

            return [
                'transactions_deleted' => $transactionsDeleted,
                'invoices_reset' => $invoicesReset,
            ];
        });
    }

    /**
     * @return list<int>
     */
    private function transactionIdsForScope(BusinessEntity $businessEntity, BankAccount $bankAccount): array
    {
        return Transaction::query()
            ->where('business_entity_id', $businessEntity->id)
            ->where('bank_account_id', $bankAccount->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
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

    /**
     * @param  list<int>  $transactionIds
     */
    private function matchedBankStatementEntryCount(array $transactionIds): int
    {
        if ($transactionIds === []) {
            return 0;
        }

        return (int) DB::table('bank_statement_entries')
            ->whereIn('transaction_id', $transactionIds)
            ->count();
    }

    /**
     * @param  list<int>  $transactionIds
     */
    private function deleteTransactions(array $transactionIds): int
    {
        if ($transactionIds === []) {
            return 0;
        }

        $deleted = 0;

        Transaction::query()
            ->whereIn('id', $transactionIds)
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use (&$deleted) {
                foreach ($transactions as $transaction) {
                    $transaction->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
