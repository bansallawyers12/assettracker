<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Invoice;
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
            'linked_invoices' => $this->linkedInvoiceQuery($transactionIds)->count(),
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

            $invoicesReset = $this->resetLinkedInvoices($transactionIds);
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
     */
    private function linkedInvoiceQuery(array $transactionIds)
    {
        if ($transactionIds === []) {
            return Invoice::query()->whereRaw('1 = 0');
        }

        return Invoice::query()->whereIn('payment_transaction_id', $transactionIds);
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
    private function resetLinkedInvoices(array $transactionIds): int
    {
        if ($transactionIds === []) {
            return 0;
        }

        return $this->linkedInvoiceQuery($transactionIds)->update([
            'payment_transaction_id' => null,
            'paid_at' => null,
            'payment_method' => null,
            'payment_reference' => null,
            'status' => 'approved',
        ]);
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
