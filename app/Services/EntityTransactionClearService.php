<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class EntityTransactionClearService
{
    /**
     * @return array{
     *     transactions: int,
     *     manual_journals: int,
     *     linked_invoices: int,
     *     bank_statement_entries: int
     * }
     */
    public function preview(BusinessEntity $businessEntity): array
    {
        $transactionIds = $this->transactionIdsForEntity($businessEntity);

        return [
            'transactions' => count($transactionIds),
            'manual_journals' => $this->manualJournalQuery($businessEntity)->count(),
            'linked_invoices' => $this->linkedInvoiceQuery($transactionIds)->count(),
            'bank_statement_entries' => $this->matchedBankStatementEntryCount($transactionIds),
        ];
    }

    /**
     * @return array{
     *     transactions_deleted: int,
     *     manual_journals_deleted: int,
     *     invoices_reset: int
     * }
     */
    public function clear(BusinessEntity $businessEntity, bool $includeManualJournals = false): array
    {
        return DB::transaction(function () use ($businessEntity, $includeManualJournals) {
            $transactionIds = $this->transactionIdsForEntity($businessEntity);

            $invoicesReset = $this->resetLinkedInvoices($transactionIds);

            $transactionsDeleted = $this->deleteTransactions($transactionIds);

            $manualJournalsDeleted = 0;
            if ($includeManualJournals) {
                $manualJournalsDeleted = $this->deleteManualJournals($businessEntity);
            }

            return [
                'transactions_deleted' => $transactionsDeleted,
                'manual_journals_deleted' => $manualJournalsDeleted,
                'invoices_reset' => $invoicesReset,
            ];
        });
    }

    /**
     * @return list<int>
     */
    private function transactionIdsForEntity(BusinessEntity $businessEntity): array
    {
        return Transaction::query()
            ->where('business_entity_id', $businessEntity->id)
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

    private function manualJournalQuery(BusinessEntity $businessEntity)
    {
        return JournalEntry::query()
            ->where('business_entity_id', $businessEntity->id)
            ->whereNull('source_type')
            ->whereNull('source_id');
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

    private function deleteManualJournals(BusinessEntity $businessEntity): int
    {
        $entries = $this->manualJournalQuery($businessEntity)->get(['id']);
        if ($entries->isEmpty()) {
            return 0;
        }

        $entryIds = $entries->pluck('id')->all();

        JournalLine::query()->whereIn('journal_entry_id', $entryIds)->delete();
        JournalEntry::query()->whereIn('id', $entryIds)->delete();

        return count($entryIds);
    }
}
